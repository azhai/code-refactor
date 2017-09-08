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

/**
 * 空白的遍历修改器
 * @package CodeRefactor\Visitor
 */
class BlankVisitor extends NodeVisitorAbstract
{
    use \CodeRefactor\Mixin\VisitorMixin;
    
    public $modify_times = 0;     //修改次数
    protected $modify_rules = []; //匹配改写规则
    
    public function addRule($type, $filter = null, $callback = null)
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
            if (is_null($filter)) {
                continue;
            }
            //检测是否符合规则
            $args = [$node, ];
            $ft_result = exec_callback($filter, $args);
            if (is_null($callback) || is_null($ft_result)) {
                continue;
            }
            //修改节点代码
            $args[] = $ft_result;
            $cb_result = exec_callback($callback, $args);
            if (is_null($cb_result)) {
                continue;
            }
            $this->modify_times ++;
            return $cb_result;
        }
    }
}
