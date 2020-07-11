<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection\Output;

use Overblog\GraphQLBundle\Relay\Connection\PageInfoInterface;

class PageInfo implements PageInfoInterface
{
    use DeprecatedPropertyPublicAccessTrait;

    protected ?string $startCursor;
    protected ?string $endCursor;
    protected ?bool $hasPreviousPage;
    protected ?bool $hasNextPage;

    public function __construct(string $startCursor = null, string $endCursor = null, bool $hasPreviousPage = null, bool $hasNextPage = null)
    {
        $this->startCursor = $startCursor;
        $this->endCursor = $endCursor;
        $this->hasPreviousPage = $hasPreviousPage;
        $this->hasNextPage = $hasNextPage;
    }

    /**
     * @return string
     */
    public function getStartCursor(): ?string
    {
        return $this->startCursor;
    }

    public function setStartCursor(string $startCursor): void
    {
        $this->startCursor = $startCursor;
    }

    /**
     * @return string
     */
    public function getEndCursor(): ?string
    {
        return $this->endCursor;
    }

    public function setEndCursor(string $endCursor): void
    {
        $this->endCursor = $endCursor;
    }

    /**
     * @return bool
     */
    public function getHasPreviousPage(): ?bool
    {
        return $this->hasPreviousPage;
    }

    public function setHasPreviousPage(bool $hasPreviousPage): void
    {
        $this->hasPreviousPage = $hasPreviousPage;
    }

    /**
     * @return bool
     */
    public function getHasNextPage(): ?bool
    {
        return $this->hasNextPage;
    }

    public function setHasNextPage(bool $hasNextPage): void
    {
        $this->hasNextPage = $hasNextPage;
    }
}
