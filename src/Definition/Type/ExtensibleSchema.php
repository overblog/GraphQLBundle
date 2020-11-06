<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\Type;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use Overblog\GraphQLBundle\Definition\Type\SchemaExtension\SchemaExtensionInterface;
use Overblog\GraphQLBundle\Resolver\UnresolvableException;

class ExtensibleSchema extends Schema
{
    public function __construct($config)
    {
        parent::__construct($this->addDefaultFallBackToTypeLoader(
            $config instanceof SchemaConfig ? $config : SchemaConfig::create($config)
        ));
    }

    /** @var SchemaExtensionInterface[] */
    private array $extensions = [];

    /**
     * @param SchemaExtensionInterface[] $extensions
     *
     * @return $this
     */
    public function setExtensions(array $extensions)
    {
        $this->extensions = [];
        foreach ($extensions as $extension) {
            $this->addExtension($extension);
        }

        return $this;
    }

    public function addExtension(SchemaExtensionInterface $extension): void
    {
        $this->extensions[] = $extension;
    }

    /**
     * @return $this
     */
    public function processExtensions()
    {
        foreach ($this->extensions as $extension) {
            $extension->process($this);
        }

        return $this;
    }

    private function addDefaultFallBackToTypeLoader(SchemaConfig $schemaConfig): SchemaConfig
    {
        $typeLoader = $schemaConfig->typeLoader;
        $loaderWrapper = null;
        $loaderWrapper = function ($name) use ($typeLoader, &$schemaConfig, &$loaderWrapper): ?Type {
            $type = null;
            try {
                $type = $typeLoader($name);
            } catch (UnresolvableException $e) {
                // second chance for types with un-registered name in TypeResolver
                // we disabled the custom typeLoader to force default loader usage
                $schemaConfig->typeLoader = null;
                $type = $this->getType($name);
                $schemaConfig->typeLoader = $loaderWrapper; // @phpstan-ignore-line
            }

            return $type;
        };

        $schemaConfig->typeLoader = $loaderWrapper; // @phpstan-ignore-line

        return $schemaConfig;
    }
}
