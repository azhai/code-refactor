<?php
/**
 * CodeRefactor
 * @author        Ryan Liu <http://azhai.oschina.io>
 * @copyright (c) 2017 MIT License
 */

namespace CodeRefactor\Mixin;

use PhpParser\Node\Stmt;

trait AltSyntaxMixin
{
    // Control flow

    protected function pStmt_If(Stmt\If_ $node) {
        if ($this->options['alternativeSyntax']) {
            return 'if (' . $this->p($node->cond) . '):'
                 . $this->pStmts($node->stmts) . "\n"
                 . $this->pImplode($node->elseifs)
                 . (null !== $node->else ? $this->p($node->else) : '')
                 . 'endif;';
        } else {
            return parent::pStmt_If($node);
        }
    }

    protected function pStmt_ElseIf(Stmt\ElseIf_ $node) {
        if ($this->options['alternativeSyntax']) {
            return ' elseif (' . $this->p($node->cond) . '):'
                 . $this->pStmts($node->stmts) . "\n";
        } else {
            return parent::pStmt_ElseIf($node);
        }
    }

    protected function pStmt_Else(Stmt\Else_ $node) {
        if ($this->options['alternativeSyntax']) {
            return ' else:' . $this->pStmts($node->stmts) . "\n";
        } else {
            return parent::pStmt_Else($node);
        }
    }

    protected function pStmt_For(Stmt\For_ $node) {
        if ($this->options['alternativeSyntax']) {
            return 'for ('
                 . $this->pCommaSeparated($node->init) . ';' . (!empty($node->cond) ? ' ' : '')
                 . $this->pCommaSeparated($node->cond) . ';' . (!empty($node->loop) ? ' ' : '')
                 . $this->pCommaSeparated($node->loop)
                 . '):' . $this->pStmts($node->stmts) . "\n" . 'endfor;';
        } else {
            return parent::pStmt_For($node);
        }
    }

    protected function pStmt_Foreach(Stmt\Foreach_ $node) {
        if ($this->options['alternativeSyntax']) {
            return 'foreach (' . $this->p($node->expr) . ' as '
                 . (null !== $node->keyVar ? $this->p($node->keyVar) . ' => ' : '')
                 . ($node->byRef ? '&' : '') . $this->p($node->valueVar) . '):'
                 . $this->pStmts($node->stmts) . "\n" . 'endforeach;';
        } else {
            return parent::pStmt_Foreach($node);
        }
    }

    protected function pStmt_While(Stmt\While_ $node) {
        if ($this->options['alternativeSyntax']) {
            return 'while (' . $this->p($node->cond) . '):'
                 . $this->pStmts($node->stmts) . "\n" . 'endwhile;';
        } else {
            return parent::pStmt_While($node);
        }
    }

    //There is no alternative-syntax or template syntax for a do-while-loop.
    protected function pStmt_Do(Stmt\Do_ $node) {
        return 'do {' . $this->pStmts($node->stmts) . "\n"
             . '} while (' . $this->p($node->cond) . ');';
    }

    protected function pStmt_Switch(Stmt\Switch_ $node) {
        if ($this->options['alternativeSyntax']) {
            return 'switch (' . $this->p($node->cond) . '):'
                 . $this->pStmts($node->cases) . "\n" . 'endswitch;';
        } else {
            return parent::pStmt_Switch($node);
        }
    }
    
    // Other
    
    protected function pStmt_InlineHTML(Stmt\InlineHTML $node) {
        $newline = $node->getAttribute('hasLeadingNewline', true) ? "\n" : '';
        return '?>' . $this->pNoIndent($newline . $node->value) . '<?php ';
    }
    
    protected function pStmt_Echo(Stmt\Echo_ $node) {
        if ($this->options['alternativeSyntax'] && 1 === count($node->exprs)) {
            return '/*ECHO*/echo ' . $this->pCommaSeparated($node->exprs) . ' ;/*ENDECHO*/';
        }
        return parent::pStmt_Echo($node);
    }
}
