<?php

namespace Overblog\GraphBundle\Definition\Builder;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use Overblog\GraphBundle\Definition\TypeRegistryInterface;
use Overblog\GraphBundle\Definition\TypeResolverInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TypeBuilder implements TypeRegistryInterface, TypeResolverInterface
{
    private $container;
    private $definitions;
    private $types;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->definitions = [];
        $this->types = [
            'ID' => Type::id(),
            'Int' => Type::int(),
            'Boolean' => Type::boolean(),
            'Float' => Type::float(),
            'String' => Type::string(),
        ];
    }

    /**
     * Adds a type.
     *
     * @param string $type The name of the type
     * @param string $class The fully qualified name of the type definition class
     *
     * @return TypeBuilder This type builder
     */
    public function setDefinitionClass($type, $class)
    {
        $this->definitions[$type] = $class;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getType($name)
    {
        if (isset($this->types[$name])) {
            return $this->types[$name];
        }

        $definition = $this->getTypeDefinition($name);
        $type = $definition->createType($this);

        return $this->types[$name] = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveType($expr)
    {
        if (!is_string($expr)) {
            return $expr;
        }

        // Non-Null
        if ('!' === $expr[strlen($expr) - 1]) {
            return new NonNull($this->resolveType(substr($expr, 0, -1)));
        }

        // List
        if ('[' === $expr[0]) {
            if (']' !== $expr[strlen($expr) - 1]) {
                throw new \RuntimeException(sprintf('Invalid type "%s"', $expr));
            }

            return new ListOfType($this->resolveType(substr($expr, 1, -1)));
        }

        // Named
        return $this->getType($expr);
    }

    /**
     * Returns the type definition.
     *
     * @param string $type The type name
     *
     * @return TypeDefinition The type definition
     */
    private function getTypeDefinition($type)
    {
        if (!isset($this->definitions[$type])) {
            throw new \RuntimeException(sprintf('The type "%s" is not registered.', $type));
        }

        $class = $this->definitions[$type];

        if (!class_exists($class)) {
            throw new \RuntimeException(sprintf('The type class "%s" does not exist.', $class));
        }

        $definition = new $class($type);

        if ($definition instanceof ContainerAwareInterface) {
            $definition->setContainer($this->container);
        }

        return $definition;
    }
}
