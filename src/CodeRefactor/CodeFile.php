<?php
/**
 * CodeRefactor
 * @author        Ryan Liu <http://azhai.oschina.io>
 * @copyright (c) 2017 MIT License
 */

namespace CodeRefactor;

use PhpParser\Builder;
use PhpParser\Node\Stmt;

class CodeFile extends CodeBlock
{
    public $filename = '';
    
    protected $namespaces = [];
    
    protected $classes = [];
    
    protected $functions = [];
    
    public function __construct(array $stmts = [], $filename = '')
    {
        $this->filename = $filename;
        parent::__construct($stmts);
    }
    
    public function getClass($name = false)
    {
        if (empty($name)) {
            return $this->findCode('classes');
        } elseif (isset($this->classes[$name])) {
            $offset = $this->classes[$name];
            return $this->stmts[$offset];
        }
    }
    
    public function setClass($name, $node = null)
    {
        if ($node instanceof ClassCode) {
            $node = new ClassCode($node->getNode(), $name);
        } elseif ($node instanceof Stmt\ClassLike) {
            $node = new ClassCode($node, $name);
        } else {
            $stmt = new Builder\Class_($name);
            $node = new ClassCode($stmt->getNode());
        }
        $this->addStmt($node);
        return $this;
    }
    
    /**
     * Adds a statement.
     */
    public function addStmt($stmt)
    {
        if (method_exists($stmt, 'isMixinCode')) {
            $name = strval($stmt->getName());
        } else {
            $stmt = self::getStmtNode($stmt);
            $name = isset($stmt->name) ? strval($stmt->name) : null;
        }
        $type = $stmt->getType();
        switch ($type) {
            case 'Stmt_Namespace':
                $this->namespaces[$name] = $this->offset;
                break;
            case 'Stmt_Class':
            case 'Stmt_Interface':
            case 'Stmt_Trait':
                $stmt = new Code\ClassCode($stmt);
                // no break
            case 'ClassCode':
                $this->classes[$name] = $this->offset;
                break;
            case 'Stmt_Function':
                $stmt = new Code\FunctionCode($stmt);
                // no break
            case 'FunctionCode':
                $name = strtolower($name);
                $this->functions[$name] = $this->offset;
                break;
        }
        $this->stmts[$this->offset++] = $stmt;
        return $this;
    }
    
    public function getFunction($name = false)
    {
        if (empty($name)) {
            return $this->findCode('functions');
        }
        $name = strtolower($name);
        if (isset($this->functions[$name])) {
            $offset = $this->functions[$name];
            return $this->stmts[$offset];
        }
    }
    
    public function setFunction($name, $node = null)
    {
        if ($node instanceof FunctionCode) {
            $node = new FunctionCode($node->getNode(), $name);
        } elseif ($node instanceof Stmt\Function_) {
            $node = new FunctionCode($node, $name);
        } else {
            $stmt = new Builder\Function_($name);
            $node = new FunctionCode($stmt->getNode());
        }
        $this->addStmt($node);
        return $this;
    }
}
