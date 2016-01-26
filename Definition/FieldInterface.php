<?php

namespace Overblog\GraphBundle\Definition;

interface FieldInterface
{
    /**
     * @param array $config
     * @return array
     */
    public function toFieldsDefinition(array $config);
}
