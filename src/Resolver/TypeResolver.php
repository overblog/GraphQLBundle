<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Event\Events;
use Overblog\GraphQLBundle\Event\TypeLoadedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function json_encode;
use function sprintf;
use function strlen;
use function substr;

class TypeResolver extends AbstractResolver
{
    private array $cache = [];
    private ?string $currentSchemaName = null;
    private EventDispatcherInterface $dispatcher;

    public function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    public function setCurrentSchemaName(?string $currentSchemaName): void
    {
        $this->currentSchemaName = $currentSchemaName;
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
            $type = $this->string2Type($alias);
            $this->cache[$alias] = $type;
        }

        return $this->cache[$alias];
    }

    private function string2Type(string $alias): Type
    {
        if (null !== ($type = $this->wrapTypeIfNeeded($alias))) {
            return $type;
        }

        return $this->baseType($alias);
    }

    private function baseType(string $alias): Type
    {
        $type = $this->getSolution($alias);
        if (null === $type) {
            throw new UnresolvableException(
                sprintf('Could not find type with alias "%s". Did you forget to define it?', $alias)
            );
        }

        return $type;
    }

    private function wrapTypeIfNeeded(string $alias): ?Type
    {
        // Non-Null
        if ('!' === $alias[strlen($alias) - 1]) {
            /** @var NullableType $type */
            $type = $this->string2Type(substr($alias, 0, -1));

            return Type::nonNull($type);
        }
        // List
        if ($this->hasNeedListOfWrapper($alias)) {
            return Type::listOf($this->string2Type(substr($alias, 1, -1)));
        }

        return null;
    }

    private function hasNeedListOfWrapper(string $alias): bool
    {
        if ('[' === $alias[0]) {
            $got = $alias[strlen($alias) - 1];
            if (']' !== $got) {
                throw new UnresolvableException(
                    sprintf('Malformed ListOf wrapper type "%s" expected "]" but got "%s".', $alias, json_encode($got))
                );
            }

            return true;
        }

        return false;
    }

    protected function supportedSolutionClass(): ?string
    {
        return Type::class;
    }
}
