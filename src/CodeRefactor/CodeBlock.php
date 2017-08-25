<?php

namespace CodeRefactor;

use PhpParser\Comment;


class CodeBlock
{
    use \CodeRefactor\Mixin\BaseMixin;
    
    public $comments = [];
    protected $offset = 0;
    protected $stmts = [];
    
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
    
    public function getDocComment($all = false)
    {
        if ($this->comments) {
            $text = implode("\n * ", $this->comments);
            return new Comment\Doc(sprintf("/**\n * %s\n */", $text));
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
}
