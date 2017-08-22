<?php
namespace CodeRefactor;

use PhpParser\Builder;
use PhpParser\Node\Stmt;


class ClassCode extends Builder\Class_
{
    protected $node = null;
    
    public function __construct(Stmt\ClassLike $node)
    {
        parent::__construct($node->name);
        $this->node = $node;
    }
    
    /**
     * Returns the built node.
     */
    public function getNode()
    {
        return $this->node;
    }
    
    /**
     * Adds a statement.
     */
    public function addStmt($stmt) {
        $stmt = $this->normalizeNode($stmt);
        if ($stmt instanceof Stmt\Use_) {
            $this->uses = array_merge($this->uses, $stmt->uses);
        } elseif ($stmt instanceof Stmt\Const_) {
            $this->constants = array_merge($this->constants, $stmt->consts);
        } elseif ($stmt instanceof Stmt\Property) {
            $name = $stmt->props[0]->name;
            $stmt = new PropertyCode($stmt);
            $this->properties[$name] = $stmt;
        } elseif ($stmt instanceof Stmt\ClassMethod) {
            $name = $stmt->name;
            $stmt = new MethodCode($stmt);
            $this->functions[$name] = $stmt;
        }
        $this->stmts[$this->offset ++] = $stmt;
        return $this;
    }
    
    /**
     * Duplicate node attributes to this
     */
    public function duplicate()
    {
        if ($doc_comment = $this->node->getDocComment()) {
            $this->setDocComment($doc_comment);
        }
        if ($this->node instanceof Stmt\Class_) {
            if ($this->node->isAbstract()) {
                $this->makeAbstract();
            }
            if ($this->node->isFinal()) {
                $this->makeFinal();
            }
            $this->setModifier($this->node->flags);
            $this->extend($this->node->extends);
            exec_method_array($this, 'implement', $this->node->implements);
        }
        $this->addStmts($this->node->stmts);
        $this->node = parent::getNode();
        return $this;
    }
}
