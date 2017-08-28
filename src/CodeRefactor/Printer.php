<?php
/**
 * CodeRefactor
 * @author        Ryan Liu <http://azhai.oschina.io>
 * @copyright (c) 2017 MIT License
 */

namespace CodeRefactor;

use PhpParser\PrettyPrinter;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;

class Printer extends PrettyPrinter\Standard
{
    
    public function prettyPrintCode($node, $add_file_tag = false)
    {
        $result = $this->prettyPrint(to_array($node, false));
        if ($add_file_tag && $node instanceof CodeBlock) {
            if ($stmts = $node->getStmts()) {
                $first = $stmts[0];
                $last = $stmts[count($stmts) - 1];
            } else {
                $first = $last = null;
            }
            $result = self::addPhpTag($result, $first, $last);
        }
        return $result;
    }
    
    public static function addPhpTag($content = '', $first = null, $last = null)
    {
        $content = '<?php' . "\n" . $content;
        if ($first && $first instanceof Stmt\InlineHTML) {
            $content = preg_replace('/^<\\?php\\s+\\?>\\n?/', '', $content);
        }
        if ($last && $last instanceof Stmt\InlineHTML) {
            $content = preg_replace('/<\\?php$/', '', rtrim($content));
        }
        return $content;
    }
    
    /**
     * Pretty prints an array of nodes (statements) and indents them optionally.
     */
    protected function pStmts(array $nodes, $indent = true)
    {
        $result = '';
        foreach ($nodes as $node) {
            if ($node instanceof CodeBlock) {
                $comments = $node->getDocComment(true);
                $content = $this->pStmts($node->getStmts(), $indent);
            } else {
                $node = CodeBlock::getStmtNode($node);
                $comments = $node->getAttribute('comments', []);
                $content = $this->p($node);
            }
            if ($comments) {
                $front_of_comment = self::getFrontOfComment($node);
                $result .= $front_of_comment . $this->pComments($comments);
                if ($node instanceof Stmt\Nop) {
                    continue;
                }
            } else {
                $front_of_comment = '';
            }
            $front_of_node = self::getFrontOfNode($node, $front_of_comment);
            $end_of_node = self::getEndOfNode($node);
            $result .= $front_of_node . $content . $end_of_node;
        }
        if ($indent) {
            $result = $this->addIndent($result);
        }
        return $result;
    }
    
    protected static function getFrontOfComment($node)
    {
        $type = $node->getType();
        switch ($type) {
            case 'Stmt_Class':
            case 'Stmt_Interface':
            case 'Stmt_Trait':
            case 'Stmt_Property':
            case 'Stmt_ClassMethod':
            case 'Stmt_Function':
            case 'ClassCode':
            case 'PropertyCode':
            case 'MethodCode':
            case 'FunctionCode':
                $result = "\n\n";
                break;
            default:
                $result = "\n";
                break;
        }
        return $result;
    }
    
    protected static function getFrontOfNode($node, $front_of_comment = '')
    {
        if (empty($front_of_comment)) {
            $result = self::getFrontOfComment($node);
        } else {
            $result = "\n";
        }
        return $result;
    }
    
    protected static function getEndOfNode($node)
    {
        return $node instanceof Expr ? ';' : '';
    }
    
    protected function addIndent($code_text)
    {
        $pattern = '~\\n(?!$|' . $this->noIndentToken . ')~';
        return preg_replace($pattern, "\n    ", $code_text);
    }
}