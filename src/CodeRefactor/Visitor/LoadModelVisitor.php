<?php
/**
 * CodeRefactor
 * @author        Ryan Liu <http://azhai.oschina.io>
 * @copyright (c) 2017 MIT License
 */

namespace CodeRefactor\Visitor;

/**
 * 找出所有 ->load->model() 的代码，用于基于CI框架的项目
 * @package CodeRefactor\Visitor
 */
class LoadModelVisitor extends BlankVisitor
{
    public $model_names = [];
    public $arg_values = [];
    
    public function __construct()
    {
        //增加规则用于遍历Class和ClassMethod两种类型，
        //因为目标类型MethodCall可能是它们下面的子节点
        $this->addRule('Stmt_Class');
        $this->addRule('Stmt_ClassMethod');
        //增加规则用于目标类型的捕捉和修改操作
        $this->addRule(
            'Expr_MethodCall',
            [$this, 'ftExprLoadModel'],
            [$this, 'cbExprLoadModel']
        );
    }
    
    /**
     * 检查第一个参数是否字符串，并记录它
     */
    public function checkModelName(array $args)
    {
        $arg1 = self::getExprAttr($args[0], 'value', 'value');
        if ($arg1 && is_string($arg1)) {
            $model = ['path' => null, 'name' => $arg1];
            if ($pos = strrpos($arg1, '/')) {
                $model['path'] = substr($arg1, 0, $pos);
                $model['name'] = substr($arg1, $pos + 1);
            }
            $this->model_names[] = $model;
            return $model;
        }
    }
    
    /**
     * 收集方法的参数
     */
    public function checkMethodArgs(array $args)
    {
        $values = [];
        foreach ($args as $arg) {
            $value = $arg->value;
            $type = $value->getType();
            if ('Expr_ConstFetch' === $type) {
                $value->summary = $value->name;
            } elseif ('Expr_PropertyFetch' === $type) {
                $value->summary = sprintf(
                    '$%s->%s',
                        $value->var->name,
                    $value->name
                );
            } elseif (starts_with($type, 'Scalar_')) {
                $value->summary = $value->value;
            } else {
                $value->summary = "<$type>";
            }
            $values[] = $value;
        }
        $this->arg_values[] = $values;
        return $values;
    }
    
    /**
     * MethodCall是否符合条件
     */
    public function ftExprLoadModel($node)
    {
        $name = self::getExprAttr($node, 'name');
        $var_name = self::getExprAttr($node, 'var', 'name');
        if ('model' === $name && 'load' === $var_name) {
            if (count($node->args) >= 1) {
                return $this->checkModelName($node->args);
            }
        }
    }
    
    /**
     * Model类名首字母大写，没有别名的使用全小写作为别名
     */
    public function cbExprLoadModel($node, $ft_result)
    {
        //修改Model类名首字母大写
        $file = ucfirst($ft_result['name']);
        if (isset($ft_result['path']) && $ft_result['path']) {
            $file = $ft_result['path'] . '/' . $file;
        }
        $node->args[0]->value->value = $file;
        //如果没有别名，使用全小写类名作为别名
        if (1 === count($node->args)) {
            $name = strtolower($ft_result['name']);
            $node->args[] = self::createArg($name);
        }
        return $node; //提交修改
    }
}
