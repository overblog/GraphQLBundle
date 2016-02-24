<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ExecutorContextEvent extends Event
{
    private $executorContext = [];

    public function __construct(array $executorContext)
    {
        $this->executorContext = $executorContext;
    }

    /**
     * @return array
     */
    public function getExecutorContext()
    {
        return $this->executorContext;
    }

    /**
     * @param array $executionContext
     */
    public function setExecutorContext(array $executionContext)
    {
        $this->executorContext = $executionContext;
    }
}
