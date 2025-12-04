<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PhpCsFixer' => true,
        '@autoPHPMigration' => true,
        '@autoPHPUnitMigration:risky' => true,
        'php_unit_attributes' => true,
        'native_function_invocation' => true,
        'strict_param' => true,
        'declare_strict_types' => true,
    ])
    ->setParallelConfig(\PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setFinder($finder)
;
