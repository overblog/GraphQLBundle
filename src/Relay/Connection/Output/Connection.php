<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection\Output;

use AllowDynamicProperties;
use GraphQL\Executor\Promise\Promise;
use Overblog\GraphQLBundle\Relay\Connection\ConnectionInterface;
use Overblog\GraphQLBundle\Relay\Connection\EdgeInterface;
use Overblog\GraphQLBundle\Relay\Connection\PageInfoInterface;

/**
 * @phpstan-template T
 *
 * @phpstan-implements ConnectionInterface<T>
 */
#[AllowDynamicProperties]
class Connection implements ConnectionInterface
{
    use DeprecatedPropertyPublicAccessTrait;

    /** @phpstan-var iterable<EdgeInterface<T>> */
    protected iterable $edges;

    protected ?PageInfoInterface $pageInfo;

    /** @var int|Promise|null Total count or promise that returns the total count */
    protected $totalCount;

    /**
     * @param EdgeInterface<T>[] $edges
     */
    public function __construct(array $edges = [], PageInfoInterface $pageInfo = null)
    {
        $this->edges = $edges;
        $this->pageInfo = $pageInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function getEdges(): iterable
    {
        return $this->edges;
    }

    /**
     * {@inheritdoc}
     */
    public function setEdges(iterable $edges): void
    {
        $this->edges = $edges;
    }

    /**
     * {@inheritdoc}
     */
    public function getPageInfo(): ?PageInfoInterface
    {
        return $this->pageInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function setPageInfo(PageInfoInterface $pageInfo): void
    {
        $this->pageInfo = $pageInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * {@inheritdoc}
     */
    public function setTotalCount($totalCount): void
    {
        $this->totalCount = $totalCount;
    }
}
