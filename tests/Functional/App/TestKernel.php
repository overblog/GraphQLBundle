<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App;

use Overblog\GraphQLBundle\OverblogGraphQLBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use function sprintf;
use function sys_get_temp_dir;

final class TestKernel extends Kernel implements CompilerPassInterface
{
    private ?string $testCase;

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        yield new FrameworkBundle();
        yield new SecurityBundle();
        yield new OverblogGraphQLBundle();
    }

    public function __construct(string $environment, bool $debug, string $testCase = null)
    {
        $this->testCase = $testCase;
        parent::__construct($environment, $debug);
    }

    public function getCacheDir(): string
    {
        return $this->basePath().'cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return $this->basePath().'logs';
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getRootDir(): string
    {
        return __DIR__;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        if (null !== $this->testCase) {
            $loader->load(sprintf(__DIR__.'/config/%s/config.yml', $this->testCase));
        } else {
            $loader->load(__DIR__.'/config/config.yml');
        }

        $loader->load(function (ContainerBuilder $container): void {
            $container->addCompilerPass($this);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        // disabled http_exception_listener because it flatten exception to html response
        if ($container->has('http_exception_listener')) {
            $container->removeDefinition('http_exception_listener');
        }
    }

    private function basePath(): string
    {
        return sys_get_temp_dir().'/OverblogGraphQLBundle/'.Kernel::VERSION.'/'.($this->testCase ? $this->testCase.'/' : '');
    }
}
