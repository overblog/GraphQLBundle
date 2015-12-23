<?php

namespace Overblog\GraphBundle\Definition;

interface TypeRegistryInterface
{
    /**
     * Returns the specified type.
     *
     * @param string $name
     *
     * @return \GraphQL\Type\Definition\Type
     */
    public function getType($name);
}
