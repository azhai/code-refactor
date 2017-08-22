<?php
namespace CodeRefactor;

use PhpParser\Builder;
use PhpParser\Node\Stmt;


class FunctionCode extends Builder\Function_
{
    protected $node = null;
    
    public function __construct(Stmt\FunctionLike $node)
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
     * Duplicate node attributes to this
     */
    public function duplicate()
    {
        if ($doc_comment = $this->node->getDocComment()) {
            $this->setDocComment($doc_comment);
        }
        if ($this->node->returnsByRef()) {
            $this->makeReturnByRef();
        }
        $this->setReturnType($this->node->returnType);
        $this->addParams($this->node->params);
        $this->addStmts($this->node->stmts);
        $this->node = parent::getNode();
        return $this;
    }
}
