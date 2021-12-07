<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Annotation for GraphQL query.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"METHOD"})
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Query extends Field
{
    /**
     * The target types to attach this query to.
     *
     * @var array<string>
     */
    public ?array $targetTypes;

    /**
     * {@inheritdoc}
     *
     * @param string|string[]|null $targetTypes
     */
    public function __construct(
        ?string $name = null,
        ?string $type = null,
        ?string $resolve = null,
        ?string $complexity = null,
        array|string|null $targetTypes = null
    ) {
        parent::__construct($name, $type, $resolve, $complexity);
        if ($targetTypes) {
            $this->targetTypes = is_string($targetTypes) ? [$targetTypes] : $targetTypes;
        }
    }
}
