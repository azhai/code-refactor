<?php
namespace CodeRefactor;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use PhpParser\ParserFactory;
use PhpParser\Error;


/**
 * 代码重构
 */
class Refactor
{
    public $options = [
        'phpVersion' => 'PREFER_PHP7',
        'shortArraySyntax' => true,
    ];
    protected $_parser = null;
    protected $_printer = null;
    protected $_files = [];
    
    public function __construct(array $options = [])
    {
        if ($options) {
            $this->options = $options + $this->options;
        }
        $this->_files = [];
    }
    
    public function getParser()
    {
        if (empty($this->_parser)) {
            $factory = new ParserFactory();
            $prefix = 'PhpParser\\ParserFactory::';
            $version = constant($prefix . $this->options['phpVersion']);
            $this->_parser = $factory->create($version);
        }
        return $this->_parser;
    }
    
    public function getPrinter()
    {
        if (empty($this->_printer)) {
            $this->_printer = new Printer($this->options);
        }
        return $this->_printer;
    }
    
    public function readFiles($code_dir, $pattern = '/\.php$/')
    {
        $parser = $this->getParser();
        $iter = new RecursiveDirectoryIterator($code_dir);
        $iter = new RecursiveIteratorIterator($iter);
        $files = new RegexIterator($iter, $pattern);
        foreach ($files as $file) {
            try {
                $code = file_get_contents($file);
                $stmts = $parser->parse($code);
                $path = $file->getPathname();
                $this->_files[$path] = new CodeFile($stmts, $path);
            } catch (Error $e) {
                echo 'Parse Error: ', $e->getMessage();
            }
        }
        return $this->_files;
    }
    
    public function writeFile($infile, $outfile = false)
    {
        if (!isset($this->_files[$infile])) {
            throw new Error('File not read');
        }
        $printer = $this->getPrinter();
        $code = $this->_files[$infile];
        $content = $printer->prettyPrintCode($code, true);
        if (empty($outfile)) {
            $outfile = realpath($infile);
        }
        file_put_contents($outfile, $content, LOCK_EX);
        return $outfile;
    }
}
