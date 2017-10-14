<?php

namespace Overblog\GraphQLBundle\Relay\Node;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use Overblog\GraphQLBundle\GraphQL\Relay\Node\PluralIdentifyingRootFieldResolver;

final class PluralIdentifyingRootFieldDefinition implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        if (!isset($config['argName']) || !is_string($config['argName'])) {
            throw new \InvalidArgumentException('A valid pluralIdentifyingRoot "argName" config is required.');
        }

        if (!isset($config['inputType']) || !is_string($config['inputType'])) {
            throw new \InvalidArgumentException('A valid pluralIdentifyingRoot "inputType" config is required.');
        }

        if (!isset($config['outputType']) || !is_string($config['outputType'])) {
            throw new \InvalidArgumentException('A valid pluralIdentifyingRoot "outputType" config is required.');
        }

        if (!array_key_exists('resolveSingleInput', $config)) {
            throw new \InvalidArgumentException('PluralIdentifyingRoot "resolveSingleInput" config is required.');
        }

        $argName = $config['argName'];
        $resolver = addslashes(PluralIdentifyingRootFieldResolver::class);

        return [
            'type' => "[${config['outputType']}]",
            'args' => [$argName => ['type' => "[${config['inputType']}!]!"]],
            'resolve' => sprintf(
                "@=resolver('%s', [args['$argName'], context, info, resolveSingleInputCallback(%s)])",
                $resolver,
                $this->cleanResolveSingleInput($config['resolveSingleInput'])
            ),
        ];
    }

    private function cleanResolveSingleInput($resolveSingleInput)
    {
        if (is_string($resolveSingleInput) && 0 === strpos($resolveSingleInput, '@=')) {
            $cleanResolveSingleInput = substr($resolveSingleInput, 2);
        } else {
            $cleanResolveSingleInput = json_encode($resolveSingleInput);
        }

        return $cleanResolveSingleInput;
    }
}
