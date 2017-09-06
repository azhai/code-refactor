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
    protected $before_model_names = [];
    protected $after_model_names = [];
    
    public function __construct()
    {
        //增加规则用于遍历Class和ClassMethod两种类型，
        //因为目标类型MethodCall可能是它们下面的子节点
        $this->addRule('Stmt_Class');
        $this->addRule('Stmt_ClassMethod');
        //增加规则用于目标类型的捕捉和修改操作
        $this->addRule('Expr_MethodCall',
            [$this, 'ftExprLoadModel'], [$this, 'cbExprLoadModel']);
    }
    
    /**
     * MethodCall是否符合条件
     */
    public function ftExprLoadModel($visitor, $node)
    {
        $name = $visitor->getExprAttr($node, 'name');
        $var_name = $visitor->getExprAttr($node, 'var', 'name');
        if ('model' === $name && 'load' === $var_name) {
            return $node->args;
        }
    }
    
    /**
     * Model类名首字母大写，没有别名的使用全小写作为别名
     */
    public function cbExprLoadModel($visitor, $node, $ft_result)
    {
        $arg1 = $visitor->getExprAttr($ft_result[0], 'value', 'value');
        if ($arg1 && is_string($arg1)) {
            $this->before_model_names[] = $arg1;
            if ($pos = strrpos($arg1, '/')) {
                $name = substr($arg1, $pos + 1);
                $arg1 = substr($arg1, 0, $pos + 1) . ucfirst($name);
            } else {
                $name = $arg1;
                $arg1 = ucfirst($name);
            }
            $this->after_model_names[] = $arg1;
            //修改Model类名首字母大写
            $node->args[0]->value->value = $arg1;
            if (1 === count($node->args)) { //使用全小写类名作为别名
                $node->args[] = $visitor->createArg(strtolower($name));
            }
            return $node; //提交修改
        }
    }
}