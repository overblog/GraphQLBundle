<?php

namespace Overblog\GraphBundle\Definition;

interface FieldInterface
{
    /**
     * @return array
     */
    public function toFieldsDefinition();
}
