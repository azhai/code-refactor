<?php
namespace CodeRefactor\Code;

use PhpParser\Builder;
use PhpParser\Node\Stmt;
use PhpParser\Node\Const_;


class ClassCode extends Builder\Class_
{
    use \CodeRefactor\CodeMixin;
    
    protected $stmts = [];
    
    public function __construct(Stmt\ClassLike $node)
    {
        $this->node = $node;
        parent::__construct($node->name);
    }
    
    /**
     * Adds a statement.
     */
    public function addStmt($stmt)
    {
        if (method_exists($stmt, 'isMixinCode')) {
            $name = $stmt->getName();
        } else {
            $stmt = $this->normalizeNode($stmt);
            if ($stmt instanceof Stmt\ClassConst) {
                $name = $stmt->consts[0]->name;
            } elseif ($stmt instanceof Stmt\Property) {
                $name = $stmt->props[0]->name;
            } elseif (property_exists($stmt, 'name')) {
                $name = $stmt->name;
            }
        }
        $type = $stmt->getType();
        switch ($type) {
            case 'Stmt_Use':
                $this->uses = $stmt->uses + $this->uses;
                break;
            case 'Stmt_ClassConst':
                $this->constants[$name] = $stmt;
                break;
            case 'Stmt_Property':
                $stmt = new PropertyCode($stmt);
            case 'PropertyCode':
                $this->properties[$name] = $stmt;
                break;
            case 'Stmt_ClassMethod':
                $stmt = new MethodCode($stmt);
            case 'MethodCode':
                $name = strtolower($name);
                $this->methods[$name] = $stmt;
                break;
        }
        $this->stmts[] = $stmt;
        return $this;
    }
    
    /**
     * Duplicate node attributes to this
     */
    protected function _duplicate()
    {
        $this->dupAllDocComment();
        if ($this->node instanceof Stmt\Class_) {
            $this->setModifier($this->node->flags);
            if ($this->node->extends) {
                $this->extend($this->node->extends);
            }
            exec_method_array($this, 'implement', $this->node->implements);
        }
        $this->addStmts($this->node->stmts);
    }
    
    public function removeCode($name, $type = 'properties')
    {
        if (isset($this->$type) && is_array($this->$type)) {
            $this->duplicate();
            $components = & $this->$type;
            if (isset($components[$name])) {
                unset($components[$name]);
                $this->node = parent::getNode();
            }
        }
        return $this;
    }
    
    public function getConst($name = false)
    {
        if (empty($name)) {
            return $this->constants;
        } elseif (isset($this->constants[$name])) {
            return $this->constants[$name];
        }
    }
    
    public function setConst($name, $node)
    {
        $this->duplicate();
        if ($node instanceof Stmt\ClassConst) {
            $node->consts[0]->name = $name;
        } elseif ($node instanceof Const_) {
            $node->name = $name;
            $node = new Stmt\ClassConst([$node]);
        } else {
            $expr = $this->normalizeValue($node);
            $node = new Const_($name, $expr);
            $node = new Stmt\ClassConst([$node]);
        }
        $this->addStmt($node);
        $this->node = parent::getNode();
        return $this;
    }
    
    public function getProperty($name = false)
    {
        $this->duplicate();
        if (empty($name)) {
            return $this->properties;
        } elseif (isset($this->properties[$name])) {
            return $this->properties[$name];
        }
    }
    
    public function setProperty($name, $node = null)
    {
        $this->duplicate();
        if ($node instanceof PropertyCode) {
            $node->setName($name);
        } elseif ($node instanceof Stmt\Property) {
            $node->name = $name;
            $node = new PropertyCode($node);
        } else {
            $stmt = new Builder\Property($name);
            if (is_scalar($node) || is_array($node)) {
                $stmt->setDefault($node);
            }
            $node = new PropertyCode($stmt->getNode());
        }
        $this->addStmt($node);
        $this->node = parent::getNode();
        return $this;
    }
    
    public function getMethod($name = false)
    {
        $this->duplicate();
        if (empty($name)) {
            return $this->methods;
        }
        $name = strtolower($name);
        if (isset($this->methods[$name])) {
            return $this->methods[$name];
        }
    }
    
    public function setMethod($name, $node = null)
    {
        $this->duplicate();
        if ($node instanceof MethodCode) {
            $node->setName($name);
        } elseif ($node instanceof Stmt\ClassMethod) {
            $node->name = $name;
            $node = new MethodCode($node);
        } else {
            $stmt = new Builder\Method($name);
            $node = new MethodCode($stmt->getNode());
        }
        $this->addStmt($node);
        $this->node = parent::getNode();
        return $this;
    }
}
