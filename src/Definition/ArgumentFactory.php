<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition;

use Closure;
use function func_get_args;

class ArgumentFactory
{
    private string $className;

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function create(?array $rawArguments): ArgumentInterface
    {
        $className = $this->className;
        /** @var ArgumentInterface $arguments */
        $arguments = new $className();
        $arguments->exchangeArray($rawArguments);

        return $arguments;
    }

    public function wrapResolverArgs(callable $resolver): Closure
    {
        return function () use ($resolver) {
            $args = func_get_args();
            if (isset($args[1]) && !$args[1] instanceof ArgumentInterface) {
                $args[1] = $this->create($args[1]);
            }

            return $resolver(...$args);
        };
    }
}
