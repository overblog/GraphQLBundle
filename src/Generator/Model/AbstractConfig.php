<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Model;

/**
 * @template TKey of array-key
 *
 * @extends \ArrayObject<TKey,mixed>
 */
abstract class AbstractConfig extends \ArrayObject
{
    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    /**
     * @param string $name
     */
    public function __isset($name): bool
    {
        return $this->offsetExists($name) && null !== $this->offsetGet($name);
    }

    /**
     * @param string|int $name
     * @param mixed|null $value
     */
    public function __set($name, $value): void
    {
        $this->offsetSet($name, $value);
    }

    public function offsetGet($key)
    {
        if (!$this->offsetExists($key)) {
            throw new \OutOfBoundsException(sprintf('Index "%s" is undefined', $key));
        }

        return parent::offsetGet($key);
    }

    public function offsetSet($key, $value): void
    {
        throw new \LogicException('Setting of values is forbidden');
    }

    public function offsetUnset($key): void
    {
        throw new \LogicException('Unsetting of values is forbidden');
    }
}
