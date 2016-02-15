<?php

namespace Overblog\GraphQLBundle\Definition;

interface FieldInterface
{
    /**
     * @param array $config
     * @return array
     */
    public function toFieldsDefinition(array $config);
}
