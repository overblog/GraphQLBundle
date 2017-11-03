<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Executor;

use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;

class Executor implements ExecutorInterface
{
    /**
     * @var PromiseAdapter
     */
    private $promiseAdapter;

    /**
     * {@inheritdoc}
     */
    public function execute(Schema $schema, $requestString, $rootValue = null, $contextValue = null, $variableValues = null, $operationName = null)
    {
        $args = func_get_args();

        if (null === $this->promiseAdapter) {
            $method = 'executeQuery';
        } else {
            array_unshift($args, $this->promiseAdapter);
            $method = 'promiseToExecute';
        }

        return call_user_func_array(sprintf('\%s::%s', GraphQL::class, $method), $args);
    }

    /**
     * {@inheritdoc}
     */
    public function setPromiseAdapter(PromiseAdapter $promiseAdapter = null)
    {
        $this->promiseAdapter = $promiseAdapter;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultFieldResolver(callable $fn)
    {
        call_user_func_array(sprintf('\%s::setDefaultFieldResolver', GraphQL::class), func_get_args());
    }
}
