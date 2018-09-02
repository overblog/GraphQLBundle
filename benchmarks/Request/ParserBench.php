<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Benchmarks\Request;

use Overblog\GraphQLBundle\Benchmarks\Benchmark;
use Overblog\GraphQLBundle\Request\Parser;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Warmup(2)
 * @Revs(100)
 */
final class ParserBench extends Benchmark
{
    /** @var Parser */
    private $parser;

    public function setUp(): void
    {
        $this->parser = new Parser();
    }

    /**
     * @ParamProviders({"requestProvider"})
     *
     * @param array $args
     */
    public function benchParse(array $args): void
    {
        $this->parser->parse(new Request(...$args));
    }

    public function requestProvider()
    {
        yield [['query' => '{ foo }']];
        yield [['query' => 'query bar { foo }', 'variables' => '{"baz": "bar"}', 'operationName' => 'bar']];
        yield [[], ['variables' => '{"baz": "bar"}', 'operationName' => 'bar'], [], [], [], ['CONTENT_TYPE' => 'application/graphql'], '{ foo }'];
    }
}
