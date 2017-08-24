#CodeRefactor

基于PHP-Parser的代码重构工具，只在PHP-Parser v3.1.0 (要求php v5.5+)下测试

## 基本用法

```php
error_reporting(E_ALL);
defined('VENDOR_DIR') or define('VENDOR_DIR', dirname(__DIR__) . '/vendor');
require_once VENDOR_DIR . '/azhai/coderefactor/src/bootstrap.php';
ClassLoader::register('PhpParser', VENDOR_DIR . '/nikic/php-parser/lib/PhpParser/');

$ref = new CodeRefactor\Refactor(['phpVersion' => 'ONLY_PHP5']);
$files = $ref->readFiles(__DIR__, '/\.class\.php$/');
foreach ($files as $path => $code) {
    $classes = $code->getClass();
    foreach ($classes as $class) {
        $class->setConst('MONTHES_PER_YEAR', 12);
        $class->setProperty('monthes_per_year', 12);
    }
    $ref->writeFile($path, $path . '.new');
}
```