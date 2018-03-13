<?php
error_reporting(E_ALL);
@header('Content-Type: text/text; charset=utf-8');

defined('VENDOR_DIR') or define('VENDOR_DIR', dirname(__DIR__) . '/vendor');
require_once dirname(__DIR__) . '/src/bootstrap.php';
ClassLoader::register('PhpParser', VENDOR_DIR . '/nikic/php-parser/lib/PhpParser/');

$ref = new CodeRefactor\Refactor([
    'phpVersion' => 'PREFER_PHP7',
    'shortArraySyntax' => true,
    'alternativeSyntax' => false,
]);
$parser = $ref->getParser();
$printer = $ref->getPrinter();
$files = $ref->listFiles(__DIR__, '/class.*\.php$/');
$codes = $ref->parseFiles($files);
foreach ($codes as $path => $code) {
    $code->addComment("The WordPress File");
    $code->addComment("Class WP_Site");
    $code->find('classes', false, function ($offset, $code) use ($parser) {
        $class = $code->stmts[$offset];
        //增加常量
        $class->setConst('MONTHES_PER_YEAR', 12);
        //增加成员
        $class->setProperty('xxx', [888, 999]);
        $class->setProperty('xx_site_id', $class->getProperty('site_id'));
        //删除成员
        $class->removeCode('path', 'properties');
        //前后各加一行代码
        $name = $class->getName();
        $head = $parser->parse('<?php if (!class_exists(\'' . $name . '\')) { ?>');
        $tail = $parser->parse('<?php } ?>');
        $code->insertStmts([$head], $offset);
        $code->insertStmts([$tail], $offset + 1);
    });
    echo $printer->prettyPrintCode($code, false);
}
