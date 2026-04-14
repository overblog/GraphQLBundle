<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\Type;

use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use Overblog\GraphQLBundle\Definition\Type\SchemaExtension\SchemaExtensionInterface;

class ExtensibleSchema extends Schema
{
    /**
     * Need to reset when container reset called
     */
    private bool $isResettable = false;

    public function __construct($config)
    {
        parent::__construct(
            $config instanceof SchemaConfig ? $config : SchemaConfig::create($config)
        );
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

    public function isResettable(): bool
    {
        return $this->isResettable;
    }

    public function setIsResettable(bool $isResettable): void
    {
        $this->isResettable = $isResettable;
    }
}
