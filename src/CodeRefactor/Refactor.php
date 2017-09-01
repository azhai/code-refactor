<?php
/**
 * CodeRefactor
 * @author        Ryan Liu <http://azhai.oschina.io>
 * @copyright (c) 2017 MIT License
 */

namespace CodeRefactor;

use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeDumper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * 代码重构工具
 * phpVersion: PREFER_PHP7/PREFER_PHP5/ONLY_PHP7/ONLY_PHP5
 * shortArraySyntax: 是否使用[]代替array()表示数组，需要PHP5.4+
 */
class Refactor
{
    
    public $options = [
        'phpVersion' => 'PREFER_PHP7',
        'shortArraySyntax' => false,
    ];
    
    protected $_parser = null;
    
    protected $_printer = null;
    
    protected $_dumper = null;
    
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
    
    public function getDumper()
    {
        if (empty($this->_dumper)) {
            $this->_dumper = new NodeDumper($this->options);
        }
        return $this->_dumper;
    }
    
    /**
     * 解析目录下的代码文件
     *
     * @param string $code_dir  目录
     * @param string $pattern   文件名正则
     * @return array 文件路径和文件代码的关联数组
     */
    public function readFiles($code_dir, $pattern = '/\.php$/')
    {
        $parser = $this->getParser();
        $iter = new RecursiveDirectoryIterator($code_dir);
        $iter = new RecursiveIteratorIterator($iter);
        $files = new RegexIterator($iter, $pattern);
        $result = [];
        foreach ($files as $file) {
            try {
                $code = file_get_contents($file);
                $stmts = $parser->parse($code);
                $path = $file->getPathname();
                $result[$path] = new CodeFile($stmts, $path);
            } catch (Error $e) {
                echo 'Parse Error: ', $e->getMessage();
            }
        }
        $this->_files = $result + $this->_files;
        return $result;
    }
    
    /**
     * 将解析好的代码写入文件
     *
     * @param string $infile  已解析的代码文件路径
     * @param bool   $outfile 要写入的文件路径，默认覆盖原文件
     * @return string 写入的文件路径
     */
    public function writeFile($infile, $outfile = false)
    {
        if (!isset($this->_files[$infile])) {
            throw new Error('File not read');
        }
        $code = $this->_files[$infile];
        $printer = $this->getPrinter();
        $content = $printer->prettyPrintCode($code, true);
        if (empty($outfile)) {
            $outfile = realpath($infile);
        } else {
            @mkdir(dirname($outfile), 0755, true);
        }
        file_put_contents($outfile, $content, LOCK_EX);
        return $outfile;
    }
}