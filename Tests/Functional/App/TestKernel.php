<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Functional\App;

use Overblog\GraphQLBundle\OverblogGraphQLBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel
{
    private $testCase;

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new OverblogGraphQLBundle(),
        ];
    }

    public function __construct($environment, $debug, $testCase = null)
    {
        $this->testCase = null !== $testCase ? $testCase : false;
        parent::__construct($environment, $debug);
    }

    public function getCacheDir()
    {
        return sys_get_temp_dir().'/OverblogGraphQLBundle/'.Kernel::VERSION.'/'.$this->testCase.'/cache/'.$this->environment;
    }

    public function getLogDir()
    {
        return sys_get_temp_dir().'/OverblogGraphQLBundle/'.Kernel::VERSION.'/'.$this->testCase.'/logs';
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        if ($this->testCase) {
            $loader->load(sprintf(__DIR__.'/config/%s/config.yml', $this->testCase));
        } else {
            $loader->load(__DIR__.'/config/config.yml');
        }
    }
}
