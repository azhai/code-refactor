<?php
/**
 * CodeRefactor
 * @author        Ryan Liu <http://azhai.oschina.io>
 * @copyright (c) 2017 MIT License
 */

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
    
    public function find($type, $filter = false, $callback = null)
    {
        if (!isset($this->{$type})) {
            return [];
        }
        $components = to_array($this->{$type}, false);
        $result = [];
        foreach ($components as $name => $node) {
            if (empty($filter) || preg_match($filter, $name)) {
                if (!empty($callback)) {
                    $node = exec_function_array($callback, [$node, $this]);
                }
                $result[$name] = $node;
            }
        }
        return $result;
    }
}