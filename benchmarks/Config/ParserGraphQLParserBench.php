<?php

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

    public function setUp()
    {
        $this->container = new ContainerBuilder();
    }

    public function benchParse()
    {
        GraphQLParser::parse(new \SplFileInfo(__DIR__.'/../fixtures/schema.graphql'), $this->container);
    }
}
