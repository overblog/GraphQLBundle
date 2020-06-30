<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection\Output;

use Overblog\GraphQLBundle\Relay\Connection\EdgeInterface;

class Edge implements EdgeInterface
{
    use DeprecatedPropertyPublicAccessTrait;

    protected ?string $cursor;

    /** @var mixed */
    protected $node;

    /**
     * @param mixed $node
     */
    public function __construct(string $cursor = null, $node = null)
    {
        $this->cursor = $cursor;
        $this->node = $node;
    }

    /**
     * {@inheritdoc}
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * {@inheritdoc}
     */
    public function setNode($node): void
    {
        $this->node = $node;
    }

    /**
     * {@inheritdoc}
     */
    public function getCursor(): ? string
    {
        return $this->cursor;
    }

    /**
     * {@inheritdoc}
     */
    public function setCursor(string $cursor): void
    {
        $this->cursor = $cursor;
    }
}
