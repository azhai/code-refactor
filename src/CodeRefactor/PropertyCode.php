<?php
namespace CodeRefactor;

use PhpParser\Builder;
use PhpParser\Node\Stmt;


class PropertyCode extends Builder\Property
{
    protected $node = null;
    
    public function __construct(Stmt\Property $node)
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
        if ($this->node->isStatic()) {
            $this->makeStatic();
        }
        $this->setModifier($this->node->flags);
        $this->setDefault($this->node->props[0]->default);
        $this->addStmts($this->node->stmts);
        $this->node = parent::getNode();
        return $this;
    }
}
