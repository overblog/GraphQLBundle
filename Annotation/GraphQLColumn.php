<?php
/**
 * @author Thibault Colette <thibaultcolette06@hotmail.fr>
 * @copyright 2018 Thibault Colette
 */

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for graphql type
 * Use it if you don't use Doctrine ORM annotation
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class GraphQLColumn
{
    /**
     * Type
     *
     * @var string
     */
    public $type;

    /**
     * Is nullable?
     *
     * @var bool
     */
    public $nullable;
}