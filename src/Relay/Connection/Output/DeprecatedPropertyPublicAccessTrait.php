<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection\Output;

/**
 * @internal
 */
trait DeprecatedPropertyPublicAccessTrait
{
    public function __get($name)
    {
        return $this->accessProperty('get', $name);
    }

    public function __set($name, $value)
    {
        return $this->accessProperty('set', $name, $value);
    }

    private function accessProperty(string $type, string $name, $value = null)
    {
        if (\in_array($name, \array_keys(\get_object_vars($this)))) {
            $method = $type.\ucfirst($name);

            @\trigger_error(
                \sprintf(
                    '%sting directly property %s::$%s value is deprecated as of 0.12 and will be removed in 0.13. '.
                    'You should now use method %s::%s.',
                    \ucfirst($type),
                    __CLASS__,
                    $name,
                    __CLASS__,
                    $method
                ),
                \E_USER_DEPRECATED
            );

            return $this->$method($value);
        }

        \trigger_error(
            \sprintf(
                'Undefined property %s::$%s.',
                __CLASS__,
                $name
            ),
            \E_USER_NOTICE
        );

        return null;
    }
}
