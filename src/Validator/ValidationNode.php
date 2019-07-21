<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Validator;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use function in_array;
use Overblog\GraphQLBundle\Definition\Argument;

/**
 * ValidationNode
 *
 * Holds the input data of the associated to it GraphQL type. Properties will be
 * created dinamically in runtime. In order to avoid name conflicts all built in
 * property names are prefixed with double underscores.
 *
 * It also contains variables of the resolver context, in which this class was
 * instantiated.
 *
 * @author Timur Murtukov <murtukov@gmail.com>
 */
class ValidationNode
{
    private const KNOWN_VAR_NAMES = ['value', 'args', 'info', 'context'];

    /**
     * @var ObjectType|InputObjectType|Type
     */
    private $__type;

    /**
     * @var ValidationNode|null
     */
    private $__parent;

    /**
     * @var ValidationNode[]
     */
    private $__children = [];

    /**
     * @var string
     */
    private $__fieldName;

    /**
     * Arguments of the resolver, where the current validation is being executed.
     *
     * @var array
     */
    private $__resolverArgs;


    public function __construct(Type $type, string $field = null, ?ValidationNode $parent = null, array $resolverArgs = [])
    {
        $this->__type = $type;
        $this->__fieldName = $field;
        $this->__resolverArgs = $resolverArgs;

        if (null !== $parent) {
            $this->__parent = $parent;
            $parent->addChild($this);
        }
    }

    /**
     * Returns a GraphQL type associated to this object.
     *
     * @return ObjectType|InputObjectType|Type
     */
    public function getType(): Type
    {
        return $this->__type;
    }

    /**
     * Gets the name of the associated GraphQL type.
     * Shortcut for `getType()->name`.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->__type->name;
    }

    /**
     * Returns the field name of the type (only for root type).
     *
     * @return string|null
     */
    public function getFieldName(): ?string
    {
        return $this->__fieldName;
    }

    /**
     * @return ValidationNode|null
     */
    public function getParent(): ?ValidationNode
    {
        return $this->__parent;
    }

    /**
     * @internal
     * @param ValidationNode $child
     */
    public function addChild(ValidationNode $child)
    {
        $this->__children[] = $child;
    }

    /**
     * Traverses up through parent nodes and returns the first matching one
     *
     * @param string $name
     * @return ValidationNode|null
     */
    public function findParent(string $name): ?ValidationNode
    {
        $current = $this->__parent;

        while (null !== $current) {
            if($current->getName() === $name) {
                return $current;
            } else {
                $current = $current->getParent();
            }
        }

        return null;
    }

    /**
     * Returns an argument of the resolver, where this validation is being executed.
     *
     * @param string $name
     * @return ResolveInfo|Argument|mixed|null
     */
    public function getResolverArg(string $name)
    {
        if (in_array($name, self::KNOWN_VAR_NAMES)) {
            return $this->__resolverArgs[$name];
        }

        return null;
    }
}
