<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\DataCollector;

use GraphQL\Type\Introspection;
use Overblog\GraphQLBundle\DataCollector\GraphQLCollector;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class GraphQLCollectorResetTest extends TestCase
{
    public function testCollectorIsResetByServicesResetter(): void
    {
        $client = static::createClient(['test_case' => 'connection']);
        static::sendRequest($client, Introspection::getIntrospectionQuery());

        $container = static::getContainer();
        // the premise of this test: the collector is registered while Symfony's profiler is not
        $this->assertFalse($container->has('profiler'));
        /** @var ContainerInterface $testContainer */
        $testContainer = $container->get('test.service_container');
        /** @var GraphQLCollector $collector */
        $collector = $testContainer->get(GraphQLCollector::class);

        $collector->collect(new Request(), new Response());
        $this->assertCount(1, $collector->getBatches());

        // this is what a long-running runtime does between requests, through Kernel::boot()
        /** @var \Symfony\Contracts\Service\ResetInterface $resetter */
        $resetter = $container->get('services_resetter');
        $resetter->reset();

        $collector->collect(new Request(), new Response());
        $this->assertSame([], $collector->getBatches());
    }
}
