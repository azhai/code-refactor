<?php

if (function_exists('xdebug_disable')) {
  xdebug_disable();
}

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR1' => true,
        '@PSR2' => true,
        '@PHP56Migration:risky' => true,
        '@PHPUnit57Migration:risky' => true,
    ])
    ->setFinder(PhpCsFixer\Finder::create()
        ->exclude('adminer.php')
        ->exclude('application/logs')
        ->exclude('vendor')
        ->in(__DIR__)
    )
;

/*
This document has been generated with
https://mlocati.github.io/php-cs-fixer-configurator/
you can change this configuration by importing this YAML code:

fixerSets:
  - '@PSR1'
  - '@PSR2'
  - '@PHP56Migration:risky'
  - '@PHPUnit57Migration:risky'
risky: true

*/