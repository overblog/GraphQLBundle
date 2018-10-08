<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\Type;

use GraphQL\Type\Schema;
use Overblog\GraphQLBundle\Definition\Type\SchemaExtension\SchemaExtensionInterface;
use Overblog\GraphQLBundle\Resolver\UnresolvableException;

class ExtensibleSchema extends Schema
{
    public function __construct($config)
    {
        if (isset($config['typeLoader'])) {
            $typeLoader = $config['typeLoader'];
            $config['typeLoader'] = function ($name) use ($typeLoader) {
                try {
                    $type = $typeLoader($name);
                } catch (UnresolvableException $e) {
                    // second chance for types with un-registered name in TypeResolver
                    $type = $this->getType($name);
                }

                return $type;
            };
        }

        parent::__construct($config);
    }

    /** @var SchemaExtensionInterface[] */
    private $extensions = [];

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

    /**
     * @param SchemaExtensionInterface $extension
     */
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
}
