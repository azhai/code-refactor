<?php
/**
 * CodeRefactor
 * @author        Ryan Liu <http://azhai.oschina.io>
 * @copyright (c) 2017 MIT License
 */

namespace CodeRefactor\Mixin;


trait BackendMixin
{
    protected $backend_methods = [];
    
    public function __call($name, $args)
    {
        if (isset($this->backend_methods[$name])) {
            $backend = $this->backend_methods[$name];
            return exec_method_array($backend, $name, $args);
        }
    }
    
    /**
     * 添加后备对象的方法
     */
    public function addBackend(& $backend, array $methods = null, $prefix = '')
    {
        if (empty($methods)) {
            $methods = get_class_methods($backend);
        }
        foreach ($methods as $name) {
            $this->backend_methods[$prefix . $name] = $backend;
        }
    }
    
    /**
     * 删除后备对象的所有方法
     */
    public function removeBackend(& $backend)
    {
        foreach ($this->backend_methods as $name => $object) {
            if ($object === $backend) { //同一个对象spl_object_hash()结果一致
                unset($this->backend_methods[$name]);
            }
        }
    }
    
    /**
     * 删除后备对象的某些方法
     */
    public function removeMethods(array $methods, $prefix = '')
    {
        foreach ($methods as $name) {
            if (isset($this->backend_methods[$prefix . $name])) {
                unset($this->backend_methods[$prefix . $name]);
            }
        }
    }
}