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
    
    protected $modify_rules = [];
    
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
            $args = [$node, ];
            $ft_result = exec_callback($filter, $args);
            if (! is_null($ft_result)) {
                //修改节点代码
                $args[] = $ft_result;
                return exec_callback($callback, $args);
            }
        }
    }
}