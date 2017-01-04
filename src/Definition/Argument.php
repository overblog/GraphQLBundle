<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Definition;

class Argument implements \ArrayAccess, \Countable
{
    /**
     * @var array
     */
    private $arguments;

    public function __construct(array $arguments = null)
    {
        $this->arguments = null === $arguments ? [] : $arguments;
    }

    public function offsetExists($offset)
    {
        return isset($this->arguments[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->arguments[$offset]) ? $this->arguments[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        $this->arguments[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->arguments[$offset]);
    }

    public function getRawArguments()
    {
        return $this->arguments;
    }

    public function count()
    {
        return count($this->arguments);
    }
}
