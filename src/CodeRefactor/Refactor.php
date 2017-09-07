<?php
/**
 * CodeRefactor
 * @author        Ryan Liu <http://azhai.oschina.io>
 * @copyright (c) 2017 MIT License
 */

namespace CodeRefactor;

use PhpParser\Error as PhpParserError;
use PhpParser\ParserFactory;
use PhpParser\NodeDumper;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Unirest\Exception;

/**
 * 代码重构工具
 * phpVersion: PREFER_PHP7/PREFER_PHP5/ONLY_PHP7/ONLY_PHP5
 * shortArraySyntax: 是否使用[]代替array()表示数组，需要PHP5.4+
 */
class Refactor
{
    use \CodeRefactor\Mixin\BackendMixin;
    
    public $options = [
        'phpVersion' => 'PREFER_PHP7',
        'shortArraySyntax' => false,
    ];
    
    protected $_parser = null;
    
    protected $_printer = null;
    
    protected $_dumper = null;
    
    protected $_traverser = null;
    
    protected $_files = [];
    
    public function __construct(array $options = [])
    {
        if ($options) {
            $this->options = $options + $this->options;
        }
        $this->_files = [];
    }
    
    /**
     * 找出目录下的所有文件
     *
     * @param string $code_dir  目录
     * @param string $pattern   文件名正则
     * @return RegexIterator 文件路径迭代器
     */
    public static function listFiles($code_dir, $pattern = '/\.php$/')
    {
        $flags = FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::SKIP_DOTS;
        $dir_iter = new RecursiveDirectoryIterator($code_dir, $flags);
        $iter_iter = new RecursiveIteratorIterator($dir_iter);
        return new RegexIterator($iter_iter, $pattern);
    }
    
    public function getParser()
    {
        if (empty($this->_parser)) {
            $factory = new ParserFactory();
            $prefix = 'PhpParser\\ParserFactory::';
            $version = constant($prefix . $this->options['phpVersion']);
            $this->_parser = $factory->create($version);
            $this->addBackend($this->_parser);
        }
        return $this->_parser;
    }
    
    public function getPrinter()
    {
        if (empty($this->_printer)) {
            $this->_printer = new Printer($this->options);
            $this->addBackend($this->_printer);
        }
        return $this->_printer;
    }
    
    public function getDumper()
    {
        if (empty($this->_dumper)) {
            $this->_dumper = new NodeDumper($this->options);
            $this->addBackend($this->_dumper);
        }
        return $this->_dumper;
    }
    
    public function getTraverser()
    {
        return $this->_traverser;
    }
    
    /**
     * Adds a visitor.
     */
    public function addVisitor(NodeVisitor $visitor)
    {
        if (empty($this->_traverser)) {
            $this->_traverser = new NodeTraverser();
            $this->addBackend($this->_traverser);
        }
        $this->_traverser->addVisitor($visitor);
    }
    
    /**
     * 解析代码文件
     *
     * @param array/Iterator $files  文件路径的数组或迭代器
     * @param bool/string $exclude   排除文件
     * @return array 文件路径和文件代码的关联数组
     */
    public function parseFiles($files, $exclude = false)
    {
        $parser = $this->getParser();
        $result = [];
        foreach ($files as $path) {
            if ($exclude && preg_match($exclude, $path)) {
                continue;
            }
            try {
                $code = file_get_contents($path);
                $stmts = $parser->parse($code);
                $result[$path] = new CodeFile($stmts, $path);
            } catch (PhpParserError $e) {
                echo 'Parse Error: ', $e->getRawMessage();
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
            throw new \Exception('File not read');
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