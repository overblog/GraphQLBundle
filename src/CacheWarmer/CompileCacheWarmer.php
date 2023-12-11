<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\CacheWarmer;

use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

final class CompileCacheWarmer implements CacheWarmerInterface
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
    public function isOptional(): bool
    {
        return !$this->compiled;
    }

    /**
     * @return string[]
     */
    public function warmUp(string $cacheDir, string $buildDir = null): array
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
