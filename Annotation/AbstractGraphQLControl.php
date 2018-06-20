<?php
/**
 * @author Thibault Colette <thibaultcolette06@hotmail.fr>
 * @copyright 2018 Thibault Colette
 */

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for graphql control
 *
 * @Annotation
 * @Target("PROPERTY")
 */
abstract class AbstractGraphQLControl
{
    /**
     * Access control access name
     *
     * @var string
     */
    public $method;
}