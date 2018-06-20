<?php
/**
 * @author Thibault Colette <thibaultcolette06@hotmail.fr>
 * @copyright 2018 Thibault Colette
 */

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for graphql access control
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class GraphQLRelayMutation extends GraphQLMutation
{
    /**
     * @var string The input graphql related type
     */
    public $input;

    /**
     * @var string The payload graphql related type
     */
    public $payload;
}