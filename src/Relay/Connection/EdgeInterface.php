<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection;

/**
 * @phpstan-template T
 */
interface EdgeInterface
{
    /**
     * Get the edge node.
     *
     * @return mixed
     *
     * @phpstan-return T|null
     */
    public function getNode();

    /**
     * Set the edge node.
     *
     * @param mixed $node
     *
     * @phpstan-param T|null $node
     */
    public function setNode($node): void;

    /**
     * Get the edge cursor.
     */
    public function getCursor(): ?string;

    /**
     * Set the edge cursor.
     */
    public function setCursor(string $cursor): void;
}
