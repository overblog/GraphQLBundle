<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Resolver;

use Overblog\GraphQLBundle\Resolver\FieldResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class FieldResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ContainerBuilder */
    private static $container;

    /** @var  FieldResolver */
    private static $fieldResolver;

    public static function setUpBeforeClass()
    {
        $container = new ContainerBuilder();

        $mapping = [
            'Toto' => ['id' => 'overblog_graphql.definition.custom_toto_field', 'alias' => 'Toto'],
            'Tata' => ['id' => 'overblog_graphql.definition.custom_tata_field', 'alias' => 'Tata'],
        ];

        $container->setParameter('overblog_graphql.fields_mapping', $mapping);

        foreach ($mapping as $alias => $options) {
            $container->setDefinition($options['id'], new Definition('stdClass'))
                ->setProperty('name', $alias);
        }

        self::$container = $container;
        self::$fieldResolver = new FieldResolver(self::$container);
    }

    public function testResolveKnownField()
    {
        $field = self::$fieldResolver->resolve('Toto');

        $this->assertInstanceOf('stdClass', $field);
        $this->assertEquals('Toto', $field->name);
    }

    /**
     * @expectedException \Overblog\GraphQLBundle\Resolver\UnresolvableException
     */
    public function testResolveUnknownField()
    {
        self::$fieldResolver->resolve('Fake');
    }
}
