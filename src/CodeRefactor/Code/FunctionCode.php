<?php
/**
 * CodeRefactor
 * @author        Ryan Liu <http://azhai.oschina.io>
 * @copyright (c) 2017 MIT License
 */

namespace CodeRefactor\Code;

use PhpParser\Node\FunctionLike;

class FunctionCode extends Builder\Function_
{
    use \CodeRefactor\Mixin\CodeMixin;
    
    public function __construct(FunctionLike $node, $name = '')
    {
        $this->node = $node;
        if (empty($name)) {
            $name = $node->name;
        }
        parent::__construct($name);
    }
    
    /**
     * Duplicate node attributes to this
     */
    protected function _duplicate()
    {
        $this->dupDocComment();
        $this->setReturnType($this->node->returnType);
        $this->addParams($this->node->params);
        $this->addStmts($this->node->stmts);
    }
}