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
final class GraphQLResolver
{
    /**
     * @var string
     */
    public $resolve;
}