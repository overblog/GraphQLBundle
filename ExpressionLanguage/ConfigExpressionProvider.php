<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\DependencyInjection\Parameter;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\DependencyInjection\Service;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\IsTypeOf;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Mutation;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay\FromGlobalID;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay\GlobalID as GlobalIDFunction;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay\IdFetcherCallback;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay\MutateAndGetPayloadCallback;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay\ResolveSingleInputCallback;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Resolver;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\NewObject;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class ConfigExpressionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return [
            new Service(),
            new Service('serv'),
            new Parameter(),
            new Parameter('param'),
            new IsTypeOf(),
            new Resolver(),
            new Resolver('res'),
            new Mutation(),
            new Mutation('mut'),
            new MutateAndGetPayloadCallback(),
            new IdFetcherCallback(),
            new ResolveSingleInputCallback(),
            new GlobalIDFunction(),
            new FromGlobalID(),
            new NewObject(),
        ];
    }
}
