<?php
namespace CodeRefactor;


trait CodeMixin
{
    protected $node = null;
    protected $is_duplicated = false;
    
    public function isMixinCode()
    {
        return true;
    }
    
    /**
     * Returns the node name.
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Set the node name.
     */
    public function setName($name)
    {
        $this->name = $name;
        if ($this->node) {
            $this->node->name = $name;
        }
    }
    
    /**
     * Returns the class name.
     */
    public function getType()
    {
        $class_name = get_class($this);
        return ltrim(strrchr($class_name, '\\'), '\\');
    }
    
    /**
     * Returns the built node.
     */
    public function getNode()
    {
        return $this->node;
    }
    
    public function getDocComment()
    {
        return $this->getNode()->getDocComment();
    }
    
    public function &getAttribute($key, $default = null)
    {
        return $this->getNode()->getAttribute($key, $default);
    }
    
    /**
     * Returns the all stmts.
     */
    public function getStmts()
    {
        return $this->stmts;
    }
    
    /**
     * Adds a statement.
     */
    public function addStmt($stmt) {
        $stmt = $this->normalizeNode($stmt);
        $this->stmts[] = $stmt;
        return $this;
    }
    
    /**
     * Duplicate node attributes to this
     */
    public function duplicate()
    {
        if (false === $this->is_duplicated) {
            $this->_duplicate();
            $this->is_duplicated = true;
            $this->node = parent::getNode();
        }
        return $this;
    }
    
    public function dupDocComment()
    {
        $node = $this->getNode();
        if ($node && $doc_comment = $node->getDocComment()) {
            $this->setDocComment($doc_comment);
        }
    }
    
    public function dupAllDocComment()
    {
        $node = $this->getNode();
        if ($node && $comments = $node->getAttribute('comments', [])) {
            foreach ($comments as $comment) {
                $this->addComment($comment);
            }
        }
    }
    
    public function addComment($comment)
    {
        if (!isset($this->attributes['comments'])) {
            $this->attributes['comments'] = [];
        }
        $this->attributes['comments'][] = $this->normalizeDocComment($comment);
        return $this;
    }
}
