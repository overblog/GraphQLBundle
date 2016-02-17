<?php

namespace Overblog\GraphQLBundle\Definition;

interface ArgsInterface
{
    /**
     * @param array $config
     * @return array
     */
    public function toArgsDefinition(array $config);
}
