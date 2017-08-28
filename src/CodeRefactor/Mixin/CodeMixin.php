<?php
/**
 * CodeRefactor
 * @author        Ryan Liu <http://azhai.oschina.io>
 * @copyright (c) 2017 MIT License
 */

namespace CodeRefactor\Mixin;


trait CodeMixin
{
    use \CodeRefactor\Mixin\BaseMixin;
    
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
    
    public function &getAttribute($key, $default = null)
    {
        return $this->getNode()->getAttribute($key, $default);
    }
    
    /**
     * Returns the built node.
     */
    public function getNode()
    {
        return $this->node;
    }
    
    /**
     * Adds a statement.
     */
    public function addStmt($stmt)
    {
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
    
    public function getDocComment($all = false)
    {
        $node = $this->getNode();
        if ($all) {
            return $node ? $node->getAttribute('comments', []) : [];
        } else {
            return $node ? $node->getDocComment() : null;
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