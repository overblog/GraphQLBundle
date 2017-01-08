<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__.'/vendor/autoload.php';

use Symfony\CS\Config;
use Symfony\CS\Fixer\Contrib\HeaderCommentFixer;

$header = <<<'EOF'
This file is part of the OverblogGraphQLBundle package.

(c) Overblog <http://github.com/overblog/>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

// PHP-CS-Fixer 1.x
if (method_exists('Symfony\CS\Fixer\Contrib\HeaderCommentFixer', 'getHeader')) {
    HeaderCommentFixer::setHeader($header);
}

$finder = Symfony\CS\Finder::create()->in(__DIR__);
$fixers = ['header_comment', 'ordered_use', 'short_array_syntax', '-unalign_equals', '-psr0'];
$config = Config::create();

$config
  ->setUsingCache(true)
  ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
  ->fixers(array_merge($config->getFixers(), $fixers))
  ->finder($finder);

// PHP-CS-Fixer 2.x
if (method_exists($config, 'setRules')) {
    $config->setRules(array_merge($config->getRules(), [
        'header_comment' => ['header' => $header]
    ]));
}

return $config;
