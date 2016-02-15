<?php

namespace Overblog\GraphQLBundle\Relay\Connection\Output;

final class PageInfo
{
    /** @var string  */
    public $startCursor;

    /** @var  string */
    public $endCursor;

    /** @var  boolean */
    public $hasPreviousPage;

    /** @var  boolean */
    public $hasNextPage;

    public function __construct($startCursor, $endCursor, $hasPreviousPage, $hasNextPage)
    {
        $this->startCursor = $startCursor;
        $this->endCursor = $endCursor;
        $this->hasPreviousPage = $hasPreviousPage;
        $this->hasNextPage = $hasNextPage;
    }
}
