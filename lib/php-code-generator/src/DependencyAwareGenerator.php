<?php


namespace Murtukov\PHPCodeGenerator;


abstract class DependencyAwareGenerator extends AbstractGenerator
{
    /**
     * @var bool
     */
    protected bool $shortenQualifiers = true;

    /**
     * @var array
     */
    protected array $usePaths = [];

    /**
     * List of all generator children, which maintain their own use dependencies.
     * The list will be defined in the constructor.
     *
     * @var mixed[]
     */
    protected array $dependencyAwareChildren = [];

    /**
     * @param string $path
     * @param string $alias
     * @return string
     */
    public function resolveQualifier(string $path, $alias = ''): string
    {
        if (empty($path)) {
            return $path;
        }

        if (false === Config::$shortenQualifiers) {
            return $path;
        }

        if (Config::$suppressSymbol === $path[0]) {
            return substr($path, 1);
        }

        if ('\\' === $path[0]) {
            return $path;
        }

        $portion = strrchr($path, '\\');

        if ($portion) {
            $this->usePaths[$path] = $alias;
            $path = substr($portion, 1);
        }

        return $path;
    }

    /**
     * Returns all use-qualifiers used in this object and all it's children.
     *
     * @return string[]
     */
    public function getUsePaths(): array
    {
        $mergedPaths = $this->usePaths;

        foreach ($this->dependencyAwareChildren as $child) {
            if (is_array($child)) {
                foreach ($child as $subchild) {
                    $mergedPaths = array_replace($mergedPaths, $subchild->getUsePaths());
                }
            } else {
                $mergedPaths = array_replace($mergedPaths, $child->getUsePaths());
            }
        }

        return $mergedPaths;
    }
}
