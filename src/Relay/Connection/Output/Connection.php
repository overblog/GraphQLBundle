<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection\Output;

use GraphQL\Executor\Promise\Promise;
use Overblog\GraphQLBundle\Relay\Connection\ConnectionInterface;
use Overblog\GraphQLBundle\Relay\Connection\EdgeInterface;
use Overblog\GraphQLBundle\Relay\Connection\PageInfoInterface;

class Connection implements ConnectionInterface
{
    use DeprecatedPropertyPublicAccessTrait;

    /** @var EdgeInterface[] */
    protected array $edges;

    protected ?PageInfoInterface $pageInfo;

    /** @var int|Promise|null Total count or promise that returns the total count */
    protected $totalCount = null;

    public function __construct(array $edges = [], PageInfoInterface $pageInfo = null)
    {
        $this->edges = $edges;
        $this->pageInfo = $pageInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function getEdges(): array
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
