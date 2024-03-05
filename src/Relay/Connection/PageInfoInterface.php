<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: jvalerie
 * Date: 22/12/18
 * Time: 19:05.
 */

namespace Overblog\GraphQLBundle\Relay\Connection;

interface PageInfoInterface
{
    public function getStartCursor(): ?string;

    public function setStartCursor(string $startCursor): void;

    public function getEndCursor(): ?string;

    public function setEndCursor(string $endCursor): void;

    public function getHasPreviousPage(): ?bool;

    public function setHasPreviousPage(bool $hasPreviousPage): void;

    public function getHasNextPage(): ?bool;

    public function setHasNextPage(bool $hasNextPage): void;
}
