<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection;

use Symfony\Contracts\Service\ResetInterface;

class TotalCountCache implements ResetInterface
{
    /**
     * @var int|callable
     */
    private $total;
    private int $totalCount;

    /**
     * @param int|callable $total
     */
    public function __construct($total)
    {
        $this->total = $total;
    }

    public function __invoke(array $callableArgs = []): int
    {
        if (isset($this->totalCount)) {
            return $this->totalCount;
        }

        $this->totalCount = is_callable($this->total) ? call_user_func_array($this->total, $callableArgs) : $this->total;

        return $this->totalCount;
    }

    public function reset(): void
    {
        unset($this->totalCount);
    }
}
