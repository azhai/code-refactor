<?php
/**
 * CodeRefactor
 * @author        Ryan Liu <http://azhai.oschina.io>
 * @copyright (c) 2017 MIT License
 */

namespace CodeRefactor\Visitor;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;

/**
 * 空白的遍历修改器
 * @package CodeRefactor\Visitor
 */
class BlankVisitor extends NodeVisitorAbstract
{
    protected $modify_rules = [];
    
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
    
    public static function createArg($value)
    {
        if (! ($value instanceof Expr)) {
            $value = self::normalizeValue($value);
        }
        return new Node\Arg($value);
    }
    
    public static function addArgs(Node $node, $value)
    {
        $type = $node->getType();
        if ('Expr_FuncCall' === $type || 'Expr_MethodCall' === $type) {
            $args = array_slice(func_get_args(), 1);
            foreach ($args as $value) {
                $node->args[] = self::createArg($value);
            }
        }
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
    
    public static function cbRemoveNode()
    {
        return [null, ];
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
        //当节点是要关注的类型时，才会深入访问它的子节点
        if (! isset($this->modify_rules[$type])) {
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }
    }
    
    public function leaveNode(Node $node)
    {
        $type = $node->getType();
        if (! isset($this->modify_rules[$type])) {
            return; //类型不符合，不作任何修改
        }
        $rules = $this->modify_rules[$type];
        foreach ($rules as $rule) {
            @list($filter, $callback) = $rule;
            //检测是否符合规则
            if (is_callable($filter)) {
                $ft_result = $filter($this, $node);
            } elseif (is_array($filter) && count($filter) >= 2) {
                list($object, $method) = array_splice($filter, 0, 2, [$this, $node]);
                $ft_result = exec_method_array($object, $method, $filter);
            } else {
                $ft_result = null;
            }
            if (empty($ft_result)) {
                continue; //不符合，继续下一个
            }
            //修改节点代码
            $args = [$this, $node, $ft_result];
            if (is_callable($callback)) {
                return exec_function_array($callback, $args);
            } elseif (is_array($callback) && count($callback) >= 2) {
                list($object, $method) = array_splice($callback, 0, 2, $args);
                return exec_method_array($object, $method, $callback);
            }
        }
    }
}