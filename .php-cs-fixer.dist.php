<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('Annotation')
    ->name('*.php')
    ->in([__DIR__.'/src', __DIR__.'/tests'])
;

return (new PhpCsFixer\Config())
    ->setRules(
        [
            '@Symfony' => true,
            '@PHP80Migration' => true,
            '@PHP80Migration:risky' => true,
            'ordered_imports' => ['imports_order' => ['class', 'function', 'const']],
            'general_phpdoc_annotation_remove' => [
                'annotations' => [
                    'author',
                    'category',
                    'copyright',
                    'created',
                    'license',
                    'package',
                    'since',
                    'subpackage',
                    'version',
                ],
            ],
            'fully_qualified_strict_types' => true,
            'single_line_throw' => false,
            'phpdoc_to_comment' => false,
            'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
            'global_namespace_import' => ['import_functions' => true, 'import_classes' => true, 'import_constants' => true],
            'phpdoc_summary' => false,
            'single_line_comment_style' => false,
            'phpdoc_no_alias_tag' => ['replacements' => ['type' => 'var']],
            'no_mixed_echo_print' => ['use' => 'echo'],
        ]
    )
    ->setFinder($finder)
    ->setUsingCache(true)
    ;
