<?php
/**
 * CodeRefactor
 * @author        Ryan Liu <http://azhai.oschina.io>
 * @copyright (c) 2017 MIT License
 */

define('__CODE_REFACTOR_PATH', __DIR__ . '/CodeRefactor/');


if (! function_exists('starts_with')) {
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
}


if (! function_exists('ends_with')) {
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
        return $ndlen === 0 || strlen($haystack) >= $ndlen && substr_compare($haystack, $needle, -$ndlen) === 0;
    }
}


if (! function_exists('replace_with')) {
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
}


if (! function_exists('to_array')) {
    /**
     * 主要用于将对象公开属性转为关联数组
     *
     * @param mixed $value      对象或其他值
     * @param bool  $read_props 读取对象公开属性为数组
     * @return array
     */
    function to_array($value, $read_props = true)
    {
        if (is_array($value)) {
            return $value;
        } elseif (is_object($value) && $read_props) {
            return get_object_vars($value);
        } else {
            return is_null($value) ? [] : [$value];
        }
    }
}


if (! function_exists('array_flatten')) {
    /**
     * 将多维折叠数组变为一维
     *
     * @param array $values     多维数组
     * @param bool $drop_empty  去掉为空的值
     * @return array
     */
    function array_flatten(array $values, $drop_empty = false)
    {
        $result = [];
        array_walk_recursive($values, function($value)
                use(&$result, $drop_empty) {
            if (!$drop_empty || !empty($value)) {
                $result[] = $value;
            }
        });
        return $result;
    }
}


if (! function_exists('exec_function_array')) {
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
}


if (! function_exists('exec_method_array')) {
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
}


if (! function_exists('exec_callback')) {
    /**
     * 执行给定的函数或方法
     */
    function exec_callback($callback, array $args = [])
    {
        if (is_array($callback) && count($callback) >= 2) {
            list($object, $method) = array_splice($callback, 0, 2, $args);
            return exec_method_array($object, $method, $callback);
        } else {
            assert(is_callable($callback));
            return exec_function_array($callback, $args);
        }
    }
}


if (! class_exists('ClassLoader')) {
    /**
     * 自动加载Class/Interface/Trait.
     */
    class ClassLoader
    {
        protected static $instance = null;
        
        protected static $ns_prefixes = ['CodeRefactor' => __CODE_REFACTOR_PATH];
        
        public static function register($prefix, $path)
        {
            if (!self::$instance) {
                self::$instance = new self();
                spl_autoload_register([self::$instance, 'autoload']);
            }
            $prefix = trim($prefix, '\\');
            $path = rtrim($path, '\\/ ') . '/';
            self::$ns_prefixes[$prefix] = $path;
        }
        
        /**
         * 自动加载.
         *
         * @param string $class 类名
         */
        public function autoload($class)
        {
            $class = ltrim(rtrim($class, '\\'), '\\_');
            $first = strstr($class, '\\', true);
            if (!isset(self::$ns_prefixes[$first])) {
                // 在已知类中查找
                return false;
            }
            $name = substr($class, strlen($first) + 1);
            $path = str_replace('\\', DIRECTORY_SEPARATOR, $name);
            $fullpath = self::$ns_prefixes[$first] . $path . '.php';
            if (self::require_file($fullpath)) {
                $autoload = false;
                return class_exists($class, $autoload) || interface_exists($class, $autoload) || trait_exists($class, $autoload);
            }
        }
        
        /**
         * 如果文件存在，加载文件中的代码.
         *
         * @param string $file 文件路径
         * @param bool   $once 使用require+once还是require指令
         * @return bool 如果文件存在返回true，否则返回false
         */
        public static function require_file($file, $once = false)
        {
            if (empty($file) || !file_exists($file)) {
                return false;
            }
            if ($once) {
                require_once $file;
            } else {
                require $file;
            }
            return true;
        }
    }
}
