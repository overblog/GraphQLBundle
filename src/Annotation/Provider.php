<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use \Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for operations provider.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Provider implements NamedArgumentConstructorAnnotation, Annotation
{
    /**
     * Optionnal prefix for provider fields.
     * 
     * @var string
     */
    public ?string $prefix;

    /**
     * The default target types to attach the provider queries to.
     *
     * @var array<string>
     */
    public ?array $targetQueryTypes;

    /**
     * The default target types to attach the provider mutations to.
     *
     * @var array<string>
     */
    public ?array $targetMutationTypes;
    
    public function __construct(?string $prefix = null, $targetQueryTypes = null, $targetMutationTypes = null)
    {
        $this->prefix = $prefix;
        $this->targetQueryTypes = is_string($targetQueryTypes) ? [$targetQueryTypes] : $targetQueryTypes;
        $this->targetMutationTypes = is_string($targetMutationTypes) ? [$targetMutationTypes] : $targetMutationTypes;
    }
}
