# CodeRefactor

基于PHP-Parser的代码重构工具，只在PHP-Parser v3.1.0 (要求php v5.5+)下测试

## 基本用法

```php
<?php
error_reporting(E_ALL);
defined('VENDOR_DIR') or define('VENDOR_DIR', dirname(__DIR__) . '/vendor');
require_once VENDOR_DIR . '/azhai/coderefactor/src/bootstrap.php';
ClassLoader::register('PhpParser', VENDOR_DIR . '/nikic/php-parser/lib/PhpParser/');

/**
 * 找出 $this->setName(...); 的代码
 */
function ft_set_name($visitor, $node)
{
    $name = $visitor->getExprAttr($node, 'name');
    $var = $visitor->getExprAttr($node, 'var', 'name');
    if ('setName' === $name && 'this' === $var) {
        return $node->args;
    }
}

/**
 * 将上面的代码改为 $this->setName2(..., ...);
 */
function cb_set_name2($visitor, $node, $args)
{
    $full_name = $visitor->getExprAttr($args[0], 'value', 'value');
    @list($first_name, $last_name) = explode(' ', strval($full_name), 2);
    $node->args[0]->value->value = trim($first_name);
    $node->args[] = $visitor->createArg(trim($last_name) ?: '');
    return $node;
}

//遍历和修改工具
$visitor = new CodeRefactor\Visitor\BlankVisitor();
$visitor->addRule('Stmt_Class'); //遍历Class的子节点，找出所有Method
$visitor->addRule('Stmt_ClassMethod'); //遍历Method的子节点，找出所有代码块
$visitor->addRule('Expr_MethodCall', 'ft_set_name', 'cb_set_name2');

$ref = new CodeRefactor\Refactor(['phpVersion' => 'ONLY_PHP5']);
$ref->addVisitor($visitor);
$files = $ref->readFiles(__DIR__, '/\.class\.php$/');
foreach ($files as $path => $code) {
    //添加一个常量和一个属性（粗粒度）
    $code->find('classes', false, function ($offset, $code) {
        $class = $code->stmts[$offset];
        $class->setConst('MONTHES_PER_YEAR', 12);
        $class->setProperty('monthes_per_year', 12);
    });
    //使用Visitor检查和修改所有代码（细粒度）
    $code->fixedBy($ref);
    //写入新文件
    $ref->writeFile($path, $path . '.new');
}
```