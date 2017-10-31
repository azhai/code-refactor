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
    use \CodeRefactor\Mixin\AltSyntaxMixin;
    
    /**
     * 将代码对象输出为字符串
     *
     * @param      $node
     * @param bool $add_file_tag 是否在开头添加PHP标记
     * @return string 代码的字符串表示
     */
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
        return $this->changeShortEchoTag($result);
    }
    
    /**
     * 将迭代器中的节点转为字符串数组
     */
    protected function pIterator($nodes)
    {
        $result = array();
        foreach ($nodes as $node) {
            if (null === $node) {
                $pNodes[] = '';
            } else {
                $result[] = $this->p($node);
            }
        }
        return $result;
    }
    
    /**
     * Pretty prints an array of nodes and implodes the printed values.
     */
    protected function pImplode(array $nodes, $glue = '')
    {
        $strings = $this->pIterator($nodes);
        return implode($glue, $strings);
    }
    
    /**
     * Pretty prints an array of nodes and implodes the printed values with commas.
     */
    protected function pCommaSeparated(array $nodes, $max_len = 80)
    {
        $result = '';
        $line = '';
        $strings = $this->pIterator($nodes);
        foreach ($strings as $node_str) {
            if ($line !== '' && strlen($line) + strlen($node_str) > $max_len) {
                $result .= $line . ",\n";
                $line = '    ' . $node_str;
            } else {
                $line .= ($line !== '' ? ', ' : '') . $node_str;
            }
        }
        $result .= $line;
        return $result;
    }
    
    /**
     * 将单行echo改为简写方式
     *
     * @param string $content 代码内容
     */
    public function changeShortEchoTag($content = '')
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content); //统一换行符
        if ($this->options['alternativeSyntax']) {
            $finders = ['!<\?php\s+/\*ECHO\*/echo!m', '!;/\*ENDECHO\*/\s+\?>!m'];
            $content = preg_replace($finders, ['<?=', '?>'], $content);
            $content = str_replace(['/*ECHO*/', '/*ENDECHO*/'], '', $content);
        }
        return $content;
    }
    
    /**
     * 为PHP代码添加PHP标记
     *
     * @param string $content 代码内容
     * @param null   $first  第一个表达式对象
     * @param null   $last  最后一个表达式对象
     * @return string 添加了PHP标记的代码内容
     */
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
    
    /**
     * 注释前的空行
     *
     * @param $node  注释所属表达式
     * @return string 若干个换行
     */
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
    
    /**
     * 表达式前的空行
     *
     * @param        $node  表达式
     * @param string $front_of_comment 注释前的换行
     * @return string 若干个换行
     */
    protected static function getFrontOfNode($node, $front_of_comment = '')
    {
        if (empty($front_of_comment)) {
            $result = self::getFrontOfComment($node);
        } else {
            $result = "\n";
        }
        return $result;
    }
    
    /**
     * 表达式后的空行
     *
     * @param $node   表达式
     * @return string 若干个换行
     */
    protected static function getEndOfNode($node)
    {
        return $node instanceof Expr ? ';' : '';
    }
    
    /**
     * 增加缩进
     *
     * @param string  $code_text 代码内容
     * @return string 加了缩进的代码内容
     */
    protected function addIndent($code_text)
    {
        $pattern = '~\\n(?!$|' . $this->noIndentToken . ')~';
        return preg_replace($pattern, "\n    ", $code_text);
    }
}
