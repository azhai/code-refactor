<?php
/**
 * CodeRefactor
 * @author        Ryan Liu <http://azhai.oschina.io>
 * @copyright (c) 2017 MIT License
 */

namespace CodeRefactor;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;

class Visitor extends NodeVisitorAbstract
{
    public static $remove_this_node = [null, ];
    
    protected $modify_rules = [];
    
    public function beforeTraverse(array $nodes)
    {
        foreach ($nodes as $i => $node) {
            if ($node && !($node instanceof Node)) {
                $nodes[$i] = $node->getNode();
            }
        }
        return $nodes;
    }
    
    public function enterNode(Node $node)
    {
        $type = $node->getType();
        if (! isset($this->modify_rules[$type])) {
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }
    }
    
    public function leaveNode(Node $node)
    {
        $type = $node->getType();
        if (! isset($this->modify_rules[$type])) {
            return; //不修改
        }
        $rules = $this->modify_rules[$type];
        reset($rules);
        while (@list($filter, $callback) = next($rules)) {
            $args = [&$node, ];
            if (empty($filter) || exec_function_array($filter, $args)) {
                return empty($callback) ? self::$remove_this_node
                        : exec_function_array($callback, $args);
            }
        }
    }
    
    public function addRule($type, $filter = false, $callback = null)
    {
        if (! isset($this->modify_rules[$type])) {
            $this->modify_rules[$type] = [];
        }
        $this->modify_rules[$type][] = [$filter, $callback];
    }
}