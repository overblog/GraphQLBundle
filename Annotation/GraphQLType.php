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
 * @Target("CLASS")
 */
final class GraphQLType
{
    /**
     * Type
     *
     * @var string
     */
    public $type;
}