<?php
namespace CodeRefactor\Code;

use PhpParser\Builder;
use PhpParser\Node\FunctionLike;


class FunctionCode extends Builder\Function_
{
    use \CodeRefactor\Mixin\CodeMixin;
    
    public function __construct(FunctionLike $node)
    {
        $this->node = $node;
        parent::__construct($node->name);
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
