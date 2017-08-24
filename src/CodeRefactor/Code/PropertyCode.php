<?php
namespace CodeRefactor\Code;

use PhpParser\Builder;
use PhpParser\Node\Stmt;


class PropertyCode extends Builder\Property
{
    use \CodeRefactor\CodeMixin;
    
    protected $stmts = [];
    
    public function __construct(Stmt\Property $node)
    {
        $this->node = $node;
        $name = $node->props[0]->name;
        parent::__construct($name);
    }
    
    /**
     * Duplicate node attributes to this
     */
    protected function _duplicate()
    {
        $this->dupDocComment();
        $this->setModifier($this->node->flags);
        $default = $this->node->props[0]->default;
        $this->setDefault($default);
        $this->addStmts($this->node->stmts);
    }
    
    /**
     * Set the node name.
     */
    public function setName($name)
    {
        $this->name = $name;
        if ($this->node) {
            $this->node->props[0]->name = $name;
        }
    }
}
