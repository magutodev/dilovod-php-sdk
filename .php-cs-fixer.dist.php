<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,

        // Строгая типизация
        'declare_strict_types' => true,
        'strict_param' => true,

        // Импорты
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'no_unused_imports' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],

        // Пробелы и форматирование
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => ['default' => 'single_space'],
        'blank_line_before_statement' => [
            'statements' => ['return', 'throw', 'try'],
        ],
        'concat_space' => ['spacing' => 'one'],
        'no_extra_blank_lines' => [
            'tokens' => [
                'curly_brace_block',
                'extra',
                'parenthesis_brace_block',
                'return',
                'square_brace_block',
                'throw',
                'use',
            ],
        ],
        'no_trailing_comma_in_singleline' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        'trim_array_spaces' => true,

        // Классы
        'class_attributes_separation' => ['elements' => ['method' => 'one', 'property' => 'one']],
        'no_blank_lines_after_class_opening' => true,
        'self_accessor' => true,
        'single_class_element_per_statement' => true,

        // PHPDoc
        'no_empty_phpdoc' => true,
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_order' => true,
        'phpdoc_trim' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last'],

        // Прочее
        'cast_spaces' => ['space' => 'single'],
    ])
    ->setFinder($finder)
    ->setCacheFile('.php-cs-fixer.cache');
