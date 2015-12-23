<?php

namespace Overblog\GraphBundle\Definition\Builder;

use GraphQL\Schema;
use GraphQL\Type\Definition\ObjectType;
use Overblog\GraphBundle\Definition\TypeResolverInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SchemaBuilder
{
    private $container;
    private $resolver;

    public function __construct(ContainerInterface $container, TypeResolverInterface $resolver)
    {
        $this->container = $container;
        $this->resolver = $resolver;
    }

    public function createSchema(array $queries, array $mutations)
    {
        return new Schema(
            empty($queries) ? null : $this->createRootQuery($queries),
            empty($mutations) ? null : $this->createRootMutation($mutations)
        );
    }

    private function createRootQuery(array $fields)
    {
        return new ObjectType([
            'name' => 'RootQuery',
            'fields' => $this->createFields($fields),
        ]);
    }

    private function createRootMutation(array $fields)
    {
        return new ObjectType([
            'name' => 'RootMutation',
            'fields' => $this->createFields($fields),
        ]);
    }

    private function createFields(array $fields)
    {
        return function () use ($fields) {
            foreach ($fields as $name => $class) {
                $fields[$name] = $this->createField($class);
            }

            return $fields;
        };
    }

    private function createField($class)
    {
        if (!class_exists($class)) {
            throw new \RuntimeException(sprintf('The field class "%s" does not exist.', $class));
        }

        $field = new $class();

        if ($field instanceof ContainerAwareInterface) {
            $field->setContainer($this->container);
        }

        return $field->toArray($this->resolver);
    }
}
