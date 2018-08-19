<?php

namespace Overblog\GraphQLBundle\Benchmarks;

/**
 * @BeforeMethods({"setUp"})
 * @AfterMethods({"tearDown"})
 */
abstract class Benchmark
{
    public function setUp()
    {
    }

    public function tearDown()
    {
    }
}
