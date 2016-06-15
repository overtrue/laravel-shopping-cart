<?php

$year = date('Y');

$header = <<<EOF
This file is part of the overtrue/laravel-shopping-cart.

(c) $year overtrue <i@overtrue.me>
EOF;

Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader($header);

return Symfony\CS\Config\Config::create()
    // use default SYMFONY_LEVEL and extra fixers:
    ->fixers(array(
        'header_comment',
        'short_array_syntax',
        'ordered_use',
        'strict',
        'strict_param',
        'phpdoc_order',
    ))
    ->finder(
        Symfony\CS\Finder\DefaultFinder::create()
            ->exclude('vendor')
            ->in(__DIR__.'/src')
    )
;