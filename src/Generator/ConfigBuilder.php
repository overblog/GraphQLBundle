<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator;

use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\Generator\ConfigBuilder\ConfigBuilderInterface;
use Overblog\GraphQLBundle\Generator\Model\TypeConfig;

class ConfigBuilder
{
    /**
     * @var iterable<ConfigBuilderInterface>
     */
    protected iterable $builders;

    /**
     * @param iterable<ConfigBuilderInterface> $builders
     */
    public function __construct(iterable $builders)
    {
        $this->builders = $builders;
    }

    /**
     * Builds a config array compatible with webonyx/graphql-php type system. The content
     * of the array depends on the GraphQL type that is currently being generated.
     *
     * Render example (object):
     *
     *      [
     *          'name' => self::NAME,
     *          'description' => 'Root query type',
     *          'fields' => fn() => [
     *              'posts' => {@see \Overblog\GraphQLBundle\Generator\ConfigBuilder\FieldsBuilder::buildField()},
     *              'users' => {@see \Overblog\GraphQLBundle\Generator\ConfigBuilder\FieldsBuilder::buildField()},
     *               ...
     *           ],
     *           'interfaces' => fn() => [
     *               $services->getType('PostInterface'),
     *               ...
     *           ],
     *           'resolveField' => {@see \Overblog\GraphQLBundle\Generator\ResolveInstructionBuilder::build()},
     *      ]
     *
     * Render example (input-object):
     *
     *      [
     *          'name' => self::NAME,
     *          'description' => 'Some description.',
     *          'validation' => {@see \Overblog\GraphQLBundle\Generator\ValidationRulesBuilder::build()}
     *          'fields' => fn() => [
     *              {@see \Overblog\GraphQLBundle\Generator\ConfigBuilder\FieldsBuilder::buildField()},
     *               ...
     *           ],
     *      ]
     *
     * Render example (interface)
     *
     *      [
     *          'name' => self::NAME,
     *          'description' => 'Some description.',
     *          'fields' => fn() => [
     *              {@see \Overblog\GraphQLBundle\Generator\ConfigBuilder\FieldsBuilder::buildField()},
     *               ...
     *           ],
     *          'resolveType' => {@see \Overblog\GraphQLBundle\Generator\ConfigBuilder\ResolveTypeBuilder::buildResolveType()},
     *      ]
     *
     * Render example (union):
     *
     *      [
     *          'name' => self::NAME,
     *          'description' => 'Some description.',
     *          'types' => fn() => [
     *              $services->getType('Photo'),
     *              ...
     *          ],
     *          'resolveType' => {@see \Overblog\GraphQLBundle\Generator\ConfigBuilder\ResolveTypeBuilder::buildResolveType()},
     *      ]
     *
     * Render example (custom-scalar):
     *
     *      [
     *          'name' => self::NAME,
     *          'description' => 'Some description'
     *          'serialize' => {@see \Overblog\GraphQLBundle\Generator\ConfigBuilder\CustomScalarTypeFieldsBuilder::buildScalarCallback()},
     *          'parseValue' => {@see \Overblog\GraphQLBundle\Generator\ConfigBuilder\CustomScalarTypeFieldsBuilder::buildScalarCallback()},
     *          'parseLiteral' => {@see \Overblog\GraphQLBundle\Generator\ConfigBuilder\CustomScalarTypeFieldsBuilder::buildScalarCallback()},
     *      ]
     *
     * Render example (enum):
     *
     *      [
     *          'name' => self::NAME,
     *          'values' => [
     *              'PUBLISHED' => ['value' => 1],
     *              'DRAFT' => ['value' => 2],
     *              'STANDBY' => [
     *                  'value' => 3,
     *                  'description' => 'Waiting for validation',
     *              ],
     *              ...
     *          ],
     *      ]
     */
    public function build(TypeConfig $typeConfig, PhpFile $phpFile): Collection
    {
        $configLoader = Collection::assoc();
        foreach ($this->builders as $builder) {
            $builder->build($typeConfig, $configLoader, $phpFile);
        }

        return $configLoader;
    }
}
