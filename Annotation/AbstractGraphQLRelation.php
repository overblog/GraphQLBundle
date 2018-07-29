<?php
/**
 * @author Thibault Colette <thibaultcolette06@hotmail.fr>
 * @copyright 2018 Thibault Colette
 */

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for graphql type relation.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
abstract class AbstractGraphQLRelation
{
    /**
     * Type.
     *
     * @var string
     */
    public $target;

    /**
     * Is nullable?
     *
     * @var bool
     */
    public $nullable;
}
