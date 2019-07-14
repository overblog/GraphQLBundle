<?php

namespace Overblog\GraphQLBundle\CacheWarmer;

use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class CompileCacheWarmer implements CacheWarmerInterface
{
    private $typeGenerator;

    private $compiled;

    /**
     * CompileCacheWarmer constructor.
     *
     * @param TypeGenerator $typeGenerator
     * @param bool          $compiled
     */
    public function __construct(TypeGenerator $typeGenerator, $compiled = true)
    {
        $this->typeGenerator = $typeGenerator;
        $this->compiled = $compiled;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return !$this->compiled;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        if ($this->compiled) {
            // use warm up cache dir if type generator cache dir not already explicitly declare
            $baseCacheDir = $this->typeGenerator->getBaseCacheDir();
            if (null === $this->typeGenerator->getCacheDir(false)) {
                $this->typeGenerator->setBaseCacheDir($cacheDir);
            }
            $this->typeGenerator->compile(TypeGenerator::MODE_WRITE | TypeGenerator::MODE_OVERRIDE);
            $this->typeGenerator->setBaseCacheDir($baseCacheDir);
        }
    }
}
