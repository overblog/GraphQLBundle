<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

abstract class TestCase extends WebTestCase
{
    /** @var ContainerBuilder|MockObject */
    protected $containerBuilder;

    public function setUp(): void
    {
        $this->containerBuilder = $this->getMockBuilder(ContainerBuilder::class)->setMethods(['addResource'])->getMock();
    }

    protected function assertContainerAddFileToResources($fileName): void
    {
        $this->containerBuilder->expects($this->once())
            ->method('addResource')
            ->with($fileName);
    }

    protected static function cleanConfig($config)
    {
        foreach ($config as $key => &$value) {
            if (\is_array($value)) {
                $value = self::cleanConfig($value);
            }
        }

        return \array_filter($config, function ($item) {
            return !\is_array($item) || !empty($item);
        });
    }
}
