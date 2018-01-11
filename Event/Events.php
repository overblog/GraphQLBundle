<?php

namespace Overblog\GraphQLBundle\Event;

final class Events
{
    const EXECUTOR_CONTEXT = 'graphql.executor.context';
    const PRE_EXECUTOR = 'graphql.pre_executor';
    const POST_EXECUTOR = 'graphql.post_executor';
    const ERROR_FORMATTING = 'graphql.error_formatting';
}
