<?php

namespace Overblog\GraphBundle\Definition\Builder;

interface ConfigBuilderInterface
{
    public function create($type, array $config);

    public function getBaseClassName($type);
}
