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
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;

class Visitor extends NodeVisitorAbstract
{
    protected $modify_rules = [];
    
    public static function cbRemoveNode()
    {
        return [null, ];
    }
    
    public static function getExprAttr(Node $node, $name)
    {
        $num = func_num_args();
        for ($i = 1; $i < $num; $i ++) {
            $name = func_get_arg($i);
            if (! property_exists($node, $name)) {
                return;
            }
            $node = $node->$name;
        }
        return $node;
    }
    
    /**
     * Normalizes a value: Converts nulls, booleans, integers,
     * floats, strings and arrays into their respective nodes
     *
     * @param mixed $value The value to normalize
     *
     * @return Expr The normalized value
     */
    public static function normalizeValue($value)
    {
        if ($value instanceof Node) {
            return $value;
        } elseif (is_null($value)) {
            return new Expr\ConstFetch(
                new Name('null')
            );
        } elseif (is_bool($value)) {
            return new Expr\ConstFetch(
                new Name($value ? 'true' : 'false')
            );
        } elseif (is_int($value)) {
            return new Scalar\LNumber($value);
        } elseif (is_float($value)) {
            return new Scalar\DNumber($value);
        } elseif (is_string($value)) {
            return new Scalar\String_($value);
        } elseif (is_array($value)) {
            $items = array();
            $lastKey = -1;
            foreach ($value as $itemKey => $itemValue) {
                // for consecutive, numeric keys don't generate keys
                if (null !== $lastKey && ++$lastKey === $itemKey) {
                    $items[] = new Expr\ArrayItem(
                        self::normalizeValue($itemValue)
                    );
                } else {
                    $lastKey = null;
                    $items[] = new Expr\ArrayItem(
                        self::normalizeValue($itemValue),
                        self::normalizeValue($itemKey)
                    );
                }
            }
            return new Expr\Array_($items);
        } else {
            throw new \LogicException('Invalid value');
        }
    }
    
    public function addRule($type, $filter = false, $callback = null)
    {
        if (! isset($this->modify_rules[$type])) {
            $this->modify_rules[$type] = [];
        }
        $this->modify_rules[$type][] = [$filter, $callback];
    }
    
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
            if (is_callable($filter)) {
                $filter = exec_function_array($filter, $args);
            }
            if ($filter && is_callable($callback)) {
                $args[] = $filter;
                return exec_function_array($callback, $args);
            }
        }
    }
}