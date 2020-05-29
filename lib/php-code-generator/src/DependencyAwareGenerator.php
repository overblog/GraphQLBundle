<?php


namespace Murtukov\PHPCodeGenerator;


abstract class DependencyAwareGenerator extends AbstractGenerator
{
    protected bool  $shortenQualifiers = true;
    protected array $usePaths = [];
    protected array $useGroups = [];

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
        if (empty($path) || false === Config::$shortenQualifiers || '\\' === $path[0]) {
            return $path;
        }

        if ($path[0] === Config::$suppressSymbol) {
            return substr($path, 1);
        }

        if ($portion = strrchr($path, '\\')) {
            $this->usePaths[$path] = $alias;
            $path = substr($portion, 1);
        }

        return $path;
    }

    public function addUse(string $fqcn, string ...$aliases): self
    {
        $this->usePaths[$fqcn] = implode(', ', $aliases);
        return $this;
    }

    public function addUseGroup(string $fqcn, string ...$classNames)
    {
        foreach ($classNames as $name) {
            if (empty($this->useGroups[$fqcn]) || !in_array($name, $this->useGroups[$fqcn])) {
                $this->useGroups[$fqcn][] = $name;
            }
        }
        return $this;
    }

    public function useGroupsToArray()
    {
        $result = [];

        foreach ($this->useGroups as $path => $classNames) {
            $result[rtrim($path, '\\') . '\{'.implode(', ', $classNames).'}'] = '';
        }

        return $result;
    }

    /**
     * Returns all use-qualifiers used in this object and all it's children.
     *
     * @return string[]
     */
    public function getUsePaths(): array
    {
        // Merge self use paths and use groups
        $mergedPaths = $this->usePaths + $this->useGroupsToArray();

        foreach ($this->dependencyAwareChildren as $child) {
            if (is_array($child)) {
                foreach ($child as $subchild) {
                    if (!$subchild instanceof self) {
                        continue;
                    }
                    $mergedPaths = $mergedPaths + $subchild->getUsePaths();
                }
            } else {
                $mergedPaths = $mergedPaths + $child->getUsePaths();
            }
        }

        return $mergedPaths;
    }
}
