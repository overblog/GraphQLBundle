<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection;

interface EdgeInterface
{
    /**
     * Get the edge node.
     *
     * @return mixed
     */
    public function getNode();

    /**
     * Set the edge node.
     *
     * @param mixed $node
     */
    public function setNode($node): void;

    /**
     * Get the edge cursor.
     *
     * @return string
     */
    public function getCursor(): ? string;

    /**
     * Set the edge cursor.
     */
    public function setCursor(string $cursor): void;
}
