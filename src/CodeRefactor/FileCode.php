<?php
namespace CodeRefactor;

use PhpParser\Builder;
use PhpParser\Node\Stmt;

class FileNode extends Stmt\Namespace_
{
}


class FileCode extends Builder\Declaration
{
    protected $offset = 0;
    public $name = [];
    public $stmts = [];
    public $namespaces = [];
    public $classes = [];
    public $functions = [];
    
    public function __construct($filename, array $stmts = [])
    {
        $this->offset = 0;
        $parts = explode(DIRECTORY_SEPARATOR, $filename);
        $this->name = $this->normalizeName($parts);
        $this->addStmts($stmts);
    }
    
    /**
     * Returns the built node.
     */
    public function getNode()
    {
        return new FileNode(null, $this->stmts);
    }
    
    /**
     * Adds a statement.
     */
    public function addStmt($stmt) {
        $stmt = $this->normalizeNode($stmt);
        if ($stmt instanceof Stmt\Namespace_) {
            $name = $stmt->name;
            $this->namespaces[$name] = $stmt;
        } elseif ($stmt instanceof Stmt\ClassLike) {
            $name = $stmt->name;
            $stmt = new ClassCode($stmt);
            $this->classes[$name] = $stmt;
        } elseif ($stmt instanceof Stmt\Function_) {
            $name = $stmt->name;
            $stmt = new FunctionCode($stmt);
            $this->functions[$name] = $stmt;
        }
        $this->stmts[$this->offset ++] = $stmt;
        return $this;
    }
}
