<?php
/**
 * CodeRefactor
 * @author        Ryan Liu <http://azhai.oschina.io>
 * @copyright (c) 2017 MIT License
 */

namespace CodeRefactor\Code;

use PhpParser\Node\Stmt;

class MethodCode extends FunctionCode
{
    
    /**
     * Duplicate node attributes to this
     */
    protected function _duplicate()
    {
        if ($this->node instanceof Stmt\ClassMethod) {
            $this->setModifier($this->node->flags);
        }
        return parent::_duplicate();
    }
}
