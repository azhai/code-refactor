<?php
namespace CodeRefactor;

use PhpParser\Builder;
use PhpParser\Node\Stmt;


class MethodCode extends FunctionCode
{
    /**
     * Duplicate node attributes to this
     */
    public function duplicate()
    {
        if ($this->node instanceof Stmt\ClassMethod) {
            if ($this->node->isAbstract()) {
                $this->makeAbstract();
            }
            if ($this->node->isFinal()) {
                $this->makeFinal();
            }
            if ($this->node->isStatic()) {
                $this->makeStatic();
            }
            $this->setModifier($this->node->flags);
        }
        return parent::duplicate();
    }
}
