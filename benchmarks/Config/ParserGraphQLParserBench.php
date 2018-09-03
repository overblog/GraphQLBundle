<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Benchmarks\Config;

use Overblog\GraphQLBundle\Benchmarks\Benchmark;
use Overblog\GraphQLBundle\Benchmarks\Mock\ContainerBuilder;
use Overblog\GraphQLBundle\Config\Parser\GraphQLParser;

/**
 * @Warmup(2)
 * @Revs(100)
 */
final class ParserGraphQLParserBench extends Benchmark
{
    /** @var ContainerBuilder */
    private $container;

    public function setUp(): void
    {
        $this->container = new ContainerBuilder();
    }

    public function benchParse(): void
    {
        GraphQLParser::parse(new \SplFileInfo(__DIR__.'/../fixtures/schema.graphql'), $this->container);
    }
}
