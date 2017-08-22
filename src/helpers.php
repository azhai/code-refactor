<?php

/**
 * 开始的字符串相同.
 *
 * @param string $haystack 可能包含子串的字符串
 * @param string $needle   要查找的子串
 * @return bool
 */
function starts_with($haystack, $needle)
{
    return strncmp($haystack, $needle, strlen($needle)) === 0;
}


/**
 * 结束的字符串相同.
 *
 * @param string $haystack 可能包含子串的字符串
 * @param string $needle   要查找的子串
 * @return bool
 */
function ends_with($haystack, $needle)
{
    $ndlen = strlen($needle);
    return $ndlen === 0 || (strlen($haystack) >= $ndlen &&
            substr_compare($haystack, $needle, -$ndlen) === 0);
}


/**
 * 将内容字符串中的变量替换掉.
 *
 * @param string $content 内容字符串
 * @param array  $context 变量数组
 * @param string $prefix  变量前置符号
 * @param string $subfix  变量后置符号
 * @return string 当前内容
 */
function replace_with($content, array $context = [], $prefix = '', $subfix = '')
{
    if (empty($context)) {
        return $content;
    }
    if (empty($prefix) && empty($subfix)) {
        $replacers = $context;
    } else {
        $replacers = [];
        foreach ($context as $key => $value) {
            $replacers[$prefix . $key . $subfix] = $value;
        }
    }
    $content = strtr($content, $replacers);
    return $content;
}


/**
 * 主要用于将对象公开属性转为关联数组
 *
 * @param mixed $value 对象或其他值
 * @return array
 */
function to_array($value)
{
    if (is_object($value)) {
        return get_object_vars($value);
    } elseif (is_array($value)) {
        return $value;
    } else {
        return (array)$value;
    }
}


/**
 * 调用函数/闭包/可invoke的对象
 * 不使用call_user_func_array()，因为它有几个限制：
 * 一是函数的默认参数会丢失；
 * 二是$args中如果有引用参数，那么它们必须以引用方式传入；
 * 三是性能较低，只有反射的一半多一点。
 *
 * @param       string /Closure/object $func 函数名/闭包/含__invoke方法的对象
 * @param array $args  参数数组，长度限制5个元素及以下
 * @return mixed 执行结果，没有找到可执行函数时返回null
 */
function exec_function_array($func, array $args = [])
{
    $args = array_values($args);
    switch (count($args)) {
        case 0:
            return $func();
        case 1:
            return $func($args[0]);
        case 2:
            return $func($args[0], $args[1]);
        case 3:
            return $func($args[0], $args[1], $args[2]);
        case 4:
            return $func($args[0], $args[1], $args[2], $args[3]);
        case 5:
            return $func($args[0], $args[1], $args[2], $args[3], $args[4]);
        default:
            if (is_object($func)) {
                $ref = new ReflectionMethod($func, '__invoke');
                return $ref->invokeArgs($func, $args);
            } elseif (is_callable($func)) {
                $ref = new ReflectionFunction($func);
                return $ref->invokeArgs($args);
            }
    }
}


/**
 * 调用类/对象方法.
 * 不使用call_user_func_array()的理由同上
 *
 * @param        object  /class $clsobj 对象/类
 * @param string $method 方法名
 * @param array  $args   参数数组，长度限制5个元素及以下
 * @return mixed 执行结果，没有找到可执行方法时返回null
 */
function exec_method_array($clsobj, $method, array $args = [])
{
    $args = array_values($args);
    if (is_object($clsobj)) {
        switch (count($args)) {
            case 0:
                return $clsobj->{$method}();
            case 1:
                return $clsobj->{$method}($args[0]);
            case 2:
                return $clsobj->{$method}($args[0], $args[1]);
            case 3:
                return $clsobj->{$method}($args[0], $args[1], $args[2]);
            case 4:
                return $clsobj->{$method}($args[0], $args[1], $args[2], $args[3]);
            case 5:
                return $clsobj->{$method}($args[0], $args[1], $args[2], $args[3], $args[4]);
        }
    }
    if (method_exists($clsobj, $method)) {
        $ref = new ReflectionMethod($clsobj, $method);
        if ($ref->isPublic() && !$ref->isAbstract()) {
            if ($ref->isStatic()) {
                return $ref->invokeArgs(null, $args);
            } else {
                return $ref->invokeArgs($clsobj, $args);
            }
        }
    }
}
