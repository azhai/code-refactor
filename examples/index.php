<?php
error_reporting(E_ALL);
@header('Content-Type: text/text; charset=utf-8');

require_once dirname(__DIR__) . '/src/bootstrap.php';
defined('VENDOR_DIR') or define('VENDOR_DIR', dirname(__DIR__) . '/vendor');
ClassLoader::register('PhpParser', VENDOR_DIR . '/nikic/php-parser/lib/PhpParser/');

$ref = new CodeRefactor\Refactor(['phpVersion' => 'ONLY_PHP5']);
$printer = $ref->getPrinter();
$files = $ref->listFiles(__DIR__, '/class.*\.php$/');
$codes = $ref->parseFiles($files);
foreach ($codes as $path => $code) {
    $code->addComment("The WordPress File");
    $code->addComment("Class WP_Site");
    $code->find('classes', false, function ($offset, $code) {
        $class = $code->stmts[$offset];
        $class->setConst('MONTHES_PER_YEAR', 12);
        $class->setProperty('xxx', [888, 999]);
        $class->setProperty('xx_site_id', $class->getProperty('site_id'));
        $class->removeCode('path', 'properties');
    });
    echo $printer->prettyPrintCode($code, false);
}
