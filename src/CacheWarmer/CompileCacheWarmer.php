<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\CacheWarmer;

use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class CompileCacheWarmer implements CacheWarmerInterface
{
    private TypeGenerator $typeGenerator;
    private bool $compiled;

    public function __construct(TypeGenerator $typeGenerator, bool $compiled = true)
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
     *
     * @param string $cacheDir
     *
     * @return string[]
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

            if (null !== $baseCacheDir) {
                $this->typeGenerator->setBaseCacheDir($baseCacheDir);
            }
        }

        return [];
    }
}
