<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator;

final class TypeGeneratorOptions
{
    /**
     * PSR-4 namespace for generated GraphQL classes.
     */
    public string $namespace;

    /**
     * Relative path to a directory for generated GraphQL classes.
     * Equals `null` unless explicitly set by user.
     */
    public ?string $cacheDir;

    /**
     * Permission bitmask for the directory of generated classes.
     */
    public int $cacheDirMask;

    /**
     * Whether a class map should be generated.
     */
    public bool $useClassMap = true;

    /**
     * Base directory for generated classes.
     */
    public ?string $cacheBaseDir;

    public function __construct(
        string $namespace,
        ?string $cacheDir,
        bool $useClassMap = true,
        string $cacheBaseDir = null,
        int $cacheDirMask = null
    ) {
        $this->namespace = $namespace;
        $this->cacheDir = $cacheDir;
        $this->useClassMap = $useClassMap;
        $this->cacheBaseDir = $cacheBaseDir;

        if (null === $cacheDirMask) {
            // Apply permission 0777 for default cache dir otherwise apply 0775.
            $cacheDirMask = null === $cacheDir ? 0777 : 0775;
        }

        $this->cacheDirMask = $cacheDirMask;
    }
}
