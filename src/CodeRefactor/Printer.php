<?php
namespace CodeRefactor;

use PhpParser\PrettyPrinter;
use PhpParser\Comment;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;


class Printer extends PrettyPrinter\Standard
{
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
    
    public static function addPhpTag($content = '', $html_first = false, $html_last = false)
    {
        $content = '<?php' . "\n" . $content;
        if ($html_first) {
            $content = preg_replace('/^<\?php\s+\?>\n?/', '', $content);
        }
        if ($html_last) {
            $content = preg_replace('/<\?php$/', '', rtrim($content));
        }
        return $content;
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
        return ($node instanceof Expr) ? ';' : '';
    }
    
    protected function addIndent($code_text)
    {
        $pattern = '~\n(?!$|' . $this->noIndentToken . ')~';
        return preg_replace($pattern, "\n    ", $code_text);
    }
    
    /**
     * Pretty prints an array of nodes (statements) and indents them optionally.
     */
    protected function pStmts(array $nodes, $indent = true)
    {
        $result = '';
        foreach ($nodes as $node) {
            $node = CodeFile::getStmtNode($node);
            $comments = $node->getAttribute('comments', array());
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
            $result .= $front_of_node . $this->p($node) . $end_of_node;
        }
        if ($indent) {
            $result = $this->addIndent($result);
        }
        return $result;
    }
    
    public function prettyPrintCode($code, $add_file_tag = false)
    {
        if (method_exists($code, 'getStmts')) {
            $stmts = $code->getStmts();
        } else {
            $stmts = $code->stmts;
        }
        $result = $this->prettyPrint($stmts);
        if ($code instanceof CodeBlock) {
            if ($doc = $code->getDocComment()) {
                $result = $this->pComments([$doc]) . "\n\n" . $result;
            }
        }
        if ($add_file_tag) {
            $html_first = $stmts[0] instanceof Stmt\InlineHTML;
            $html_last = $stmts[count($stmts) - 1] instanceof Stmt\InlineHTML;
            $result = self::addPhpTag($result, $html_first, $html_last);
        }
        return $result;
    }
}
