<?php
/**
 * CodeRefactor
 * @author        Ryan Liu <http://azhai.oschina.io>
 * @copyright (c) 2017 MIT License
 */

namespace CodeRefactor\Code;

use PhpParser\Node\Stmt;

class PropertyCode extends Builder\Property
{
    use \CodeRefactor\Mixin\CodeMixin;
    
    protected $stmts = [];
    
    public function __construct(Stmt\Property $node, $name = '')
    {
        $this->node = $node;
        if (empty($name)) {
            $name = $node->props[0]->name;
        }
        parent::__construct($name);
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
}