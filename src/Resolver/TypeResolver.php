<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Event\Events;
use Overblog\GraphQLBundle\Event\TypeLoadedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use function sprintf;

class TypeResolver extends AbstractResolver
{
    private array $cache = [];
    private ?string $currentSchemaName = null;
    private EventDispatcherInterface $dispatcher;
    private bool $ignoreUnresolvableException = false;

    public function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    public function setCurrentSchemaName(?string $currentSchemaName): void
    {
        $this->currentSchemaName = $currentSchemaName;
    }

    public function setIgnoreUnresolvableException(bool $ignoreUnresolvableException): void
    {
        $this->ignoreUnresolvableException = $ignoreUnresolvableException;
    }

    /**
     * @param mixed $solution
     */
    protected function onLoadSolution($solution): void
    {
        if (isset($this->dispatcher)) {
            // @phpstan-ignore-next-line (only for Symfony 4.4)
            $this->dispatcher->dispatch(new TypeLoadedEvent($solution, $this->currentSchemaName), Events::TYPE_LOADED);
        }
    }

    /**
     * @param string $alias
     *
     * @return Type
     */
    public function resolve($alias): ?Type
    {
        if (null === $alias) {
            return null;
        }

        if (!isset($this->cache[$alias])) {
            $type = $this->baseType($alias);
            $this->cache[$alias] = $type;
            if (isset($type->name) && $type->name !== $alias) {
                $this->cache[$type->name] = $type;
            }
        }

        return $this->cache[$alias];
    }

    private function baseType(string $alias): ?Type
    {
        $type = $this->getSolution($alias);
        if (null === $type && !$this->ignoreUnresolvableException) {
            throw new UnresolvableException(
                sprintf('Could not find type with alias "%s". Did you forget to define it?', $alias)
            );
        }

        return $type;
    }

    protected function supportedSolutionClass(): ?string
    {
        return Type::class;
    }
}
