<?php
/**
 * CodeRefactor
 * @author        Ryan Liu <http://azhai.oschina.io>
 * @copyright (c) 2017 MIT License
 */

namespace CodeRefactor;

use PhpParser\Comment;

class CodeBlock
{
    use \CodeRefactor\Mixin\BaseMixin;
    
    public $comments = [];
    
    public $stmts = [];
    
    protected $offset = 0;
    
    public function __construct(array $stmts = [])
    {
        $this->offset = 0;
        foreach ($stmts as $stmt) {
            $this->addStmt($stmt);
        }
    }
    
    /**
     * Adds a statement.
     */
    public function addStmt($stmt)
    {
        $this->stmts[$this->offset++] = $stmt;
        return $this;
    }
    
    /**
     * Insert or replace some statements.
     */
    public function insertStmts(array $stmts, $offset = 0, $remove = 0)
    {
        if (false === $offset) {
            $offset = count($this->stmts);
        } elseif (false === $remove) {
            $remove = count($this->stmts);
        }
        array_splice($this->stmts, $offset, $remove, $stmts);
        return $this;
    }
    
    public static function getStmtNode($node)
    {
        if (method_exists($node, 'getNode')) {
            $node = $node->getNode();
        }
        return $node;
    }
    
    public function getDocComment($all = false)
    {
        if ($this->comments) {
            $text = implode("\n * ", $this->comments);
            $comment = new Comment\Doc(sprintf("/**\n * %s\n */", $text));
            return $all ? [$comment] : $comment;
        } else {
            return $all ? [] : null;
        }
    }
    
    public function setDocComment($doc_comment)
    {
        if ($doc_comment instanceof Comment) {
            $text = $doc_comment->getText();
        } else {
            $text = strval($doc_comment);
        }
        $lines = explode("\n", trim($text));
        foreach ($lines as $line) {
            $line = trim($line);
            if ('/*' == $line || '/**' == $line || '*/' == $line) {
                continue;
            }
            $this->comments[] = ltrim($line, '* ');
        }
    }
    
    public function addComment($text)
    {
        if ($this->comments) {
            $this->comments[] = "\n";
        }
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $this->comments[] = rtrim($line);
        }
        return $this;
    }
    
    public function findCode($type, $pattern = false)
    {
        $callback = create_function('$n,$c', 'return $c->stmts[$n];');
        return $this->find($type, $pattern, $callback);
    }
    
    public function removeCode($type, $pattern = false)
    {
        $callback = create_function('$n,$c', 'return $c->stmts[$n] = null;');
        return $this->find($type, $pattern, $callback);
    }
}
