<?php

namespace Overblog\GraphQLBundle\Definition;

interface ArgInterface
{
    /**
     * @param array $config
     * @return array
     */
    public function toArgDefinition(array $config);
}
