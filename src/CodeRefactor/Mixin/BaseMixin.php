<?php
namespace CodeRefactor\Mixin;


trait BaseMixin
{
    /**
     * Returns the class name.
     */
    public function getType()
    {
        $class_name = get_class($this);
        return ltrim(strrchr($class_name, '\\'), '\\');
    }
    
    /**
     * Returns the all stmts.
     */
    public function getStmts()
    {
        return array_filter($this->stmts);
    }
    
    public function dupDocComment()
    {
        if ($doc_comment = $this->getDocComment(false)) {
            $this->setDocComment($doc_comment);
        }
    }
    
    public function dupAllDocComments()
    {
        if ($doc_comments = $this->getDocComment(true)) {
            foreach ($doc_comments as $doc_comment) {
                $this->addComment($doc_comment);
            }
        }
    }
}
