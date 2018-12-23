<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection\Output;

use Overblog\GraphQLBundle\Relay\Connection\PageInfoInterface;

class PageInfo implements PageInfoInterface
{
    use DeprecatedPropertyPublicAccessTrait;

    /** @var string */
    protected $startCursor;

    /** @var string */
    protected $endCursor;

    /** @var bool */
    protected $hasPreviousPage;

    /** @var bool */
    protected $hasNextPage;

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

    /**
     * @param string $startCursor
     */
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

    /**
     * @param string $endCursor
     */
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

    /**
     * @param bool $hasPreviousPage
     */
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

    /**
     * @param bool $hasNextPage
     */
    public function setHasNextPage(bool $hasNextPage): void
    {
        $this->hasNextPage = $hasNextPage;
    }
}
