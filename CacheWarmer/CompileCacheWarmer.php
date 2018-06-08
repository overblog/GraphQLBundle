<?php

namespace Overblog\GraphQLBundle\CacheWarmer;

use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class CompileCacheWarmer implements CacheWarmerInterface
{
    /** @var TypeGenerator */
    private $typeGenerator;

    public function __construct(TypeGenerator $typeGenerator)
    {
        $this->typeGenerator = $typeGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        // use warm up cache dir if type generator cache dir not already explicitly declare
        $baseCacheDir = $this->typeGenerator->getBaseCacheDir();
        if (null === $this->typeGenerator->getCacheDir(false)) {
            $this->typeGenerator->setBaseCacheDir($cacheDir);
        }
        $this->typeGenerator->compile(TypeGenerator::MODE_WRITE | TypeGenerator::MODE_OVERRIDE);
        $this->typeGenerator->setBaseCacheDir($baseCacheDir);
    }
}
