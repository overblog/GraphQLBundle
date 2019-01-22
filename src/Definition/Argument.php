<?php

namespace Overblog\GraphQLBundle\Definition;

class Argument implements \ArrayAccess, \Countable
{
    /** @var array */
    private $arguments;

    public function __construct(array $arguments = null)
    {
        $this->arguments = null === $arguments ? [] : $arguments;
    }

    public function offsetExists($offset)
    {
        return \array_key_exists($offset, $this->arguments);
    }

    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->arguments[$offset] : null;
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
        return \count($this->arguments);
    }
}
