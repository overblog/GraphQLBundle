<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Annotation for GraphQL mutation.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"METHOD"})
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Mutation extends Field
{
    /**
     * The target types to attach this mutation to (useful when multiple schemas are allowed).
     *
     * @var array<string>
     */
    public array $targetTypes;

    /**
     * {@inheritdoc}
     *
     * @param string|string[]|null $targetTypes
     * @param string|string[]|null $targetType  @deprecated
     */
    public function __construct(
        ?string $name = null,
        ?string $type = null,
        array $args = [],
        ?string $resolve = null,
        $argsBuilder = null,
        $fieldBuilder = null,
        ?string $complexity = null,
        $targetTypes = null,
        $targetType = null
    ) {
        parent::__construct($name, $type, $args, $resolve, $argsBuilder, $fieldBuilder, $complexity);
        if ($targetTypes) {
            $this->targetTypes = is_string($targetTypes) ? [$targetTypes] : $targetTypes;
        } elseif ($targetType) {
            $this->targetTypes = is_string($targetType) ? [$targetType] : $targetType;
            @trigger_error('The attributes "targetType" on annotation @GQL\Mutation is deprecated as of 0.14 and will be removed in 1.0. Use the "targetTypes" attributes instead.', E_USER_DEPRECATED);
        }
    }
}
