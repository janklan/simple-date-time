<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PhpCsFixer' => true,
    ])
    ->setParallelConfig(\PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setFinder($finder)
    ;
