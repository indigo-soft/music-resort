<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/bin',
        __DIR__ . '/config',
        //__DIR__ . '/tests',
    ])
    ->exclude([
        __DIR__ . '/samples',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return new Config()
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'trim_array_spaces' => true,
        'whitespace_after_comma_in_array' => true,
        'return_to_yield_from' => true,
        'ordered_attributes' => true,
        'single_line_empty_body' => true,
        'class_reference_name_casing' => true,
        'constant_case' => ['case' => 'lower'],
        'magic_constant_casing' => true,
        'magic_method_casing' => true,
        'native_function_casing' => true,
        'native_type_declaration_casing' => true,
        'cast_spaces' => ['space' => 'none'],
        'no_short_bool_cast' => true,
        'class_attributes_separation' => [
            'elements'
            => [
                'const' => 'none',
                'method' => 'one',
                'property' => 'none',
                'trait_import' => 'none',
                'case' => 'none'
            ]
        ],
        'class_definition' => ['single_line' => true, 'inline_constructor_arguments' => false, 'space_before_parenthesis' => true],
        'ordered_class_elements' => ['order' => ['use_trait', 'case', 'constant_public', 'constant_protected', 'constant_private', 'property_public', 'property_protected', 'property_private', 'construct', 'destruct', 'magic', 'phpunit', 'method_public', 'method_protected', 'method_private']],
        'ordered_types' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'alpha'],
        'protected_to_private' => true,
        'self_static_accessor' => true,
        'single_class_element_per_statement' => ['elements' => ['const', 'property']],
        'multiline_comment_opening_closing' => true,
        'no_empty_comment' => true,
        'empty_loop_body' => ['style' => 'braces'],
        'include' => true,
        'no_unneeded_braces' => ['namespaces' => true],
        'no_unneeded_control_parentheses' => ['statements' => ['break', 'clone', 'continue', 'echo_print', 'negative_instanceof', 'others', 'return', 'switch_case', 'yield', 'yield_from']],
        'no_useless_else' => true,
        'simplified_if_return' => true,
        'switch_continue_to_break' => true,
        'method_argument_space' => ['keep_multiple_spaces_after_comma' => false, 'on_multiline' => 'ensure_fully_multiline', 'attribute_placement' => 'ignore'],
        'nullable_type_declaration_for_default_null_value' => ['use_nullable_type_declaration' => true],
        'single_line_throw' => true,
        'fully_qualified_strict_types' => ['import_symbols' => true, 'leading_backslash_in_global_namespace' => false],
        'global_namespace_import' => ['import_classes' => true, 'import_constants' => true, 'import_functions' => false],
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'single_import_per_statement' => ['group_to_single_imports' => true],
        'combine_consecutive_issets' => true,
        'declare_equal_normalize' => ['space' => 'none'],
        'nullable_type_declaration' => ['syntax' => 'question_mark'],
        'list_syntax' => ['syntax' => 'short'],
        'clean_namespace' => true,
        'no_leading_namespace_whitespace' => true,
        'assign_null_coalescing_to_coalesce_equal' => true,
        'concat_space' => ['spacing' => 'one'],
        'increment_style' => ['style' => 'post'],
        'new_expression_parentheses' => ['use_parentheses' => false],
        'no_useless_concat_operator' => ['juggle_simple_strings' => true],
        'object_operator_without_whitespace' => true,
        'operator_linebreak' => ['only_booleans' => false, 'position' => 'beginning'],
        'standardize_increment' => true,
        'standardize_not_equals' => true,
        'ternary_to_null_coalescing' => true,
        'unary_operator_spaces' => ['only_dec_inc' => false],
        'echo_tag_syntax' => ['format' => 'short'],
        'linebreak_after_opening_tag' => true,
        'align_multiline_comment' => ['comment_type' => 'all_multiline'],
        'general_phpdoc_annotation_remove' => ['annotations' => ['author', 'package', 'subpackage'], 'case_sensitive' => false],
        'general_phpdoc_tag_rename' => ['replacements' => ['inheritDocs' => 'inheritDoc']],
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_phpdoc' => true,
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_annotation_without_dot' => true,
        'phpdoc_indent' => true,
        'phpdoc_inline_tag_normalizer' => [
            'tags' => [
                'example', 'id', 'internal', 'inheritdoc', 'inheritdocs', 'link', 'source', 'toc', 'tutorial'
            ]
        ],
        'phpdoc_line_span' => ['const' => 'single', 'property' => 'single', 'method' => 'multi',],
        'phpdoc_no_access' => true,
        'phpdoc_no_alias_tag' => [
            'replacements' => [
                'property-read' => 'property', 'property-write' => 'property', 'type' => 'var', 'link' => 'see'
            ]
        ],
        'phpdoc_no_package' => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_order' => ['order' => ['param', 'return', 'throws']],
        'phpdoc_param_order' => true,
        'phpdoc_return_self_reference' => ['replacements' => ['this' => '$this', '@this' => '$this', '$self' => 'self', '@self' => 'self', '$static' => 'static', '@static' => 'static']],
        'phpdoc_scalar' => ['types' => ['boolean', 'callback', 'double', 'integer', 'real', 'str']],
        'phpdoc_separation' => ['groups' => [
            ['Annotation', 'NamedArgumentConstructor', 'Target'],
            ['copyright', 'license'],
            ['deprecated'],
            ['ORM\\*'], ['Assert\\*'],
            ['property', 'return', 'param', 'throws']]],
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary' => true,
        'phpdoc_to_comment' => ['allow_before_return_statement' => false],
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => ['groups' => ['alias', 'meta', 'simple']],
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        'phpdoc_var_annotation_correct_order' => true,
        'phpdoc_var_without_name' => true,
        'no_useless_return' => true,
        'return_assignment' => true,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'no_empty_statement' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'semicolon_after_instruction' => true,
        'space_after_semicolon' => ['remove_in_empty_for_expressions' => true],
        'strict_param' => true,
        'explicit_string_variable' => true,
        'heredoc_to_nowdoc' => true,
        'simple_to_complex_string_variable' => true,
        'array_indentation' => true,
        'blank_line_before_statement' => ['statements' => ['declare', 'phpdoc', 'return', 'switch', 'throw', 'try', 'yield', 'yield_from']],
        'method_chaining_indentation' => true,
        'no_extra_blank_lines' => ['tokens' => ['attribute', 'break', 'case', 'comma', 'continue', 'curly_brace_block', 'default', 'extra', 'parenthesis_brace_block', 'return', 'square_brace_block', 'switch', 'throw', 'use', 'use_trait']],
        'no_spaces_around_offset' => ['positions' => ['inside']],
        'type_declaration_spaces' => ['elements' => ['constant', 'function', 'property']],
        'types_spaces' => ['space' => 'none'],
        'function_declaration' => ['closure_fn_spacing' => 'none', 'closure_function_spacing' => 'none'],
        'binary_operator_spaces' => ['operators' => ['|' => 'no_space'], 'default' => 'at_least_single_space'],
        'declare_parentheses' => true,
    ])
    ->setFinder($finder)
    ->setLineEnding("\n")
    ->setUsingCache(true)
    ->setParallelConfig(ParallelConfigFactory::detect());
