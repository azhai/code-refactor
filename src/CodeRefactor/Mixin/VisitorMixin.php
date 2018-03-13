<?php
/**
 * CodeRefactor
 * @author        Ryan Liu <http://azhai.oschina.io>
 * @copyright (c) 2017 MIT License
 */

namespace CodeRefactor\Mixin;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;

trait VisitorMixin
{
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
            $items = [];
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
}
