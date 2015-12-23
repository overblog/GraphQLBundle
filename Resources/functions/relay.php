<?php

namespace Overblog\GraphBundle;

use GraphQL\Executor\Executor;
use GraphQL\Type\Definition\ResolveInfo;
use function Overblog\GraphBundle\argument;
use function Overblog\GraphBundle\field;
use function Overblog\GraphBundle\idType;
use function Overblog\GraphBundle\inputObjectType;
use function Overblog\GraphBundle\intType;
use function Overblog\GraphBundle\listOf;
use function Overblog\GraphBundle\nonNull;
use function Overblog\GraphBundle\objectType;
use function Overblog\GraphBundle\stringType;

function globalIdField($typeName, callable $idFetcher = null)
{
    $resolver = function ($obj, array $args, ResolveInfo $info) use ($typeName, $idFetcher) {
        return toGlobalId(
            $typeName ?: $info->parentType->name,
            $idFetcher ? $idFetcher($obj, $info) : Executor::defaultResolveFn($obj, $args, $info)
        );
    };

    return field('id', nonNull(idType()), $resolver);
}

function mutation($name, array $inputFields, array $outputFields, callable $mutateAndGetPayload)
{
    $inputType = inputObjectType($name . 'Input', array_merge($inputFields, [
        field('clientMutationId', nonNull(stringType())),
    ]));

    $outputType = objectType($name . 'Payload', array_merge($outputFields, [
        field('clientMutationId', nonNull(stringType())),
    ]));

    $resolver = function ($_, array $args, ResolveInfo $info) use ($mutateAndGetPayload, $inputFields) {
        $payload = $mutateAndGetPayload($args['input'], $info);
        $payload['clientMutationId'] = $inputFields['clientMutationId'];

        return $payload;
    };

    return field($name, $outputType, $resolver, [
        argument('input', nonNull($inputType)),
    ]);
}

function connectionType($name, $nodeType, array $edgeFields = [], array $connectionFields = [])
{
    $edgeType = objectType($name . 'Edge', array_merge($edgeFields, [
        field('node', $nodeType),
        field('cursor', nonNull(stringType())),
    ]));

    $connectionType = objectType($name . 'Connection', array_merge($connectionFields, [
        field('edges', listOf($edgeType)),
        field('pageInfo', nonNull(pageInfoType())),
    ]));

    return $connectionType;
}

function forwardConnectionArgs()
{
    static $args;
    if ($args) {
        return $args;
    }

    return $args = [
        argument('after', stringType()),
        argument('first', intType()),
    ];
}

function backwardConnectionArgs()
{
    static $args;
    if ($args) {
        return $args;
    }

    return $args = [
        argument('before', stringType()),
        argument('last', intType()),
    ];
}

function connectionArgs()
{
    static $args;
    if ($args) {
        return $args;
    }

    return $args = array_merge(
        forwardConnectionArgs(),
        backwardConnectionArgs()
    );
}

function pageInfoType()
{
    static $type;
    if ($type) {
        return $type;
    }

    return $type = objectType('PageInfo', [
        field('hasNextPage', nonNull(booleanType())),
        field('hasPreviousPage', nonNull(booleanType())),
        field('startCursor', stringType()),
        field('endCursor', stringType()),
    ]);
}

function toGlobalId($type, $id)
{
    return base64_encode($type . ':' . $id);
}

function fromGlobalId($globalId)
{
    return explode(':', base64_decode($globalId), 2);
}
