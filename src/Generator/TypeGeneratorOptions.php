<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator;

use Overblog\GraphQLBundle\Generator\Model\TypeGeneratorOptions as BaseTypeGeneratorOptions;

@trigger_error(sprintf('Since overblog/graphql-bundle 0.14.4: Class \Overblog\GraphQLBundle\Generator\TypeGeneratorOptions is deprecated. Use %s instead of it.', BaseTypeGeneratorOptions::class), \E_USER_DEPRECATED);

/**
 * @deprecated Use {@see \Overblog\GraphQLBundle\Generator\Model\TypeGeneratorOptions }
 */
class TypeGeneratorOptions extends BaseTypeGeneratorOptions
{

}
