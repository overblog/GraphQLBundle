<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Relay\Connection\Output;

final class PageInfo
{
    /** @var string */
    public $startCursor;

    /** @var string */
    public $endCursor;

    /** @var bool */
    public $hasPreviousPage;

    /** @var bool */
    public $hasNextPage;

    public function __construct($startCursor, $endCursor, $hasPreviousPage, $hasNextPage)
    {
        $this->startCursor = $startCursor;
        $this->endCursor = $endCursor;
        $this->hasPreviousPage = $hasPreviousPage;
        $this->hasNextPage = $hasNextPage;
    }
}
