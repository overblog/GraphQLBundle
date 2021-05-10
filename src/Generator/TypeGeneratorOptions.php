<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator;

class TypeGeneratorOptions
{
    public string $namespace;
    public ?string $cacheDir;
    public array $types;
    public int $cacheDirMask;
    public bool $useClassMap = true;
    public ?string $cacheBaseDir;

    public function __construct(
        string $namespace,
        ?string $cacheDir,
        array $types,
        bool $useClassMap = true,
        ?string $cacheBaseDir = null,
        ?int $cacheDirMask = null
    ) {
        $this->namespace = $namespace;
        $this->cacheDir = $cacheDir;
        $this->types = $types;
        $this->useClassMap = $useClassMap;
        $this->cacheBaseDir = $cacheBaseDir;

        if (null === $cacheDirMask) {
            // Apply permission 0777 for default cache dir otherwise apply 0775.
            $cacheDirMask = null === $cacheDir ? 0777 : 0775;
        }

        $this->cacheDirMask = $cacheDirMask;
    }
}
