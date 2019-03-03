<?php

/*
 * This file is part of the OverblogGraphQLPhpGenerator package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__.'/vendor/sllh/php-cs-fixer-styleci-bridge/autoload.php';

use SLLH\StyleCIBridge\ConfigBridge;
use Symfony\CS\Fixer\Contrib\HeaderCommentFixer;

$header = <<<EOF
This file is part of the OverblogGraphQLPhpGenerator package.

(c) Overblog <http://github.com/overblog/>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

// PHP-CS-Fixer 1.x
if (method_exists('Symfony\CS\Fixer\Contrib\HeaderCommentFixer', 'getHeader')) {
    HeaderCommentFixer::setHeader($header);
}

$config = ConfigBridge::create();

// PHP-CS-Fixer 2.x
if (method_exists($config, 'setRules')) {
    $config->setRules(array_merge($config->getRules(), [
        'header_comment' => ['header' => $header]
    ]));
}

return $config
    ->setUsingCache(true)
    ->fixers(array_merge($config->getFixers(), ['header_comment']))
;
