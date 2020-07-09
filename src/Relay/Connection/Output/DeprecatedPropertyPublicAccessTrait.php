<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection\Output;

use function array_keys;
use function get_object_vars;
use function in_array;
use function sprintf;
use function trigger_error;
use function ucfirst;
use const E_USER_DEPRECATED;

/**
 * @internal
 */
trait DeprecatedPropertyPublicAccessTrait
{
    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->accessProperty('get', $name);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function __set(string $name, $value)
    {
        return $this->accessProperty('set', $name, $value);
    }

    /**
     * @param mixed|null $value
     *
     * @return array|null
     */
    private function accessProperty(string $type, string $name, $value = null)
    {
        if (in_array($name, array_keys(get_object_vars($this)))) {
            $method = $type.ucfirst($name);

            @trigger_error(
                sprintf(
                    '%sting directly property %s::$%s value is deprecated as of 0.12 and will be removed in 0.13. '.
                    'You should now use method %s::%s.',
                    ucfirst($type),
                    __CLASS__,
                    $name,
                    __CLASS__,
                    $method
                ),
                E_USER_DEPRECATED
            );

            return $this->$method($value);
        }

        if ('set' === $type) {
            $this->$name = $value;

            return null;
        } else {
            return $this->$name;
        }
    }
}
