<?php

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL type.
 * Use it if you don't use Doctrine ORM.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class GraphQLToMany extends AbstractGraphQLRelation
{
}
