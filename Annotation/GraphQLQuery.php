<?php
/**
 * @author Thibault Colette <thibaultcolette06@hotmail.fr>
 * @copyright 2018 Thibault Colette
 */

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for graphql type
 * Use it if you don't use Doctrine ORM annotation.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class GraphQLQuery
{
    /**
     * @var string
     */
    public $method;

    /**
     * @var array
     */
    public $args;

    /**
     * @var string
     */
    public $type;

    /**
     * @var array
     */
    public $input;

    /**
     * @var string
     */
    public $argsBuilder;
}
