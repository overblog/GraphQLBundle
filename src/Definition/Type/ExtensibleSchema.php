<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\Type;

use GraphQL\Type\Schema;
use Overblog\GraphQLBundle\Definition\Type\SchemaExtension\SchemaExtensionInterface;

class ExtensibleSchema extends Schema
{
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
