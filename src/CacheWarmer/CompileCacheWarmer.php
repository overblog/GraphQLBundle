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
     * @return bool
     */
    public function isOptional()
    {
        return !$this->compiled;
    }

    /**
     * @param string $cacheDir
     *
     * @return array
     */
    public function warmUp($cacheDir)
    {
        if ($this->compiled) {
            // use warm up cache dir if type generator cache dir not already explicitly declared
            $cacheBaseDir = $this->typeGenerator->getCacheBaseDir();

            if (null === $this->typeGenerator->getCacheDir()) {
                $this->typeGenerator->setCacheBaseDir($cacheDir);
            }

            $this->typeGenerator->compile(TypeGenerator::MODE_WRITE | TypeGenerator::MODE_OVERRIDE);

            if (null !== $cacheBaseDir) {
                $this->typeGenerator->setCacheBaseDir($cacheBaseDir);
            }
        }

        return [];
    }
}
