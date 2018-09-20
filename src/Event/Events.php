<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Event;

final class Events
{
    public const EXECUTOR_CONTEXT = 'graphql.executor.context';
    public const PRE_EXECUTOR = 'graphql.pre_executor';
    public const POST_EXECUTOR = 'graphql.post_executor';
    public const ERROR_FORMATTING = 'graphql.error_formatting';
    public const TYPE_LOADED = 'graphql.type_loaded';
}
