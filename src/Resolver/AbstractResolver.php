<?php

namespace Overblog\GraphQLBundle\Resolver;

use Symfony\Component\HttpKernel\Kernel;

abstract class AbstractResolver implements FluentResolverInterface
{
    /** @var array */
    private $solutions = [];

    private $aliases = [];

    /** @var array */
    private $solutionOptions = [];

    /** @var array */
    private $fullyLoadedSolutions = [];

    /** @var bool */
    private $ignoreCase = true;

    public function __construct()
    {
        $this->ignoreCase = \version_compare(Kernel::VERSION, '3.3.0') < 0;
    }

    public function addSolution($id, $solutionOrFactory, array $aliases = [], array $options = [])
    {
        $id = $this->cleanIdOrAlias($id);
        $this->fullyLoadedSolutions[$id] = false;
        $this->addAliases($id, $aliases);

        $this->solutions[$id] = function () use ($id, $solutionOrFactory) {
            $solution = $solutionOrFactory;
            if (self::isSolutionFactory($solutionOrFactory)) {
                if (!isset($solutionOrFactory[1])) {
                    $solutionOrFactory[1] = [];
                }
                $solution = \call_user_func_array(...$solutionOrFactory);
            }
            $this->checkSolution($id, $solution);

            return $solution;
        };
        $this->solutionOptions[$id] = $options;

        return $this;
    }

    public function hasSolution($id)
    {
        $id = $this->resolveAlias($id);

        return isset($this->solutions[$id]);
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function getSolution($id)
    {
        return $this->loadSolution($id);
    }

    /**
     * @return array
     */
    public function getSolutions()
    {
        return $this->loadSolutions();
    }

    public function getSolutionAliases($id)
    {
        return \array_keys($this->aliases, $id);
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function getSolutionOptions($id)
    {
        $id = $this->resolveAlias($id);

        return isset($this->solutionOptions[$id]) ? $this->solutionOptions[$id] : [];
    }

    /**
     * @param string $id
     *
     * @return mixed
     */
    private function loadSolution($id)
    {
        $id = $this->resolveAlias($id);
        if (!$this->hasSolution($id)) {
            return null;
        }

        if ($this->fullyLoadedSolutions[$id]) {
            return $this->solutions[$id];
        } else {
            $loader = $this->solutions[$id];
            $this->solutions[$id] = $loader();
            $this->fullyLoadedSolutions[$id] = true;

            return $this->solutions[$id];
        }
    }

    private function addAliases($id, $aliases)
    {
        foreach ($aliases as $alias) {
            $this->aliases[$this->cleanIdOrAlias($alias)] = $id;
        }
    }

    private static function isSolutionFactory($solutionOrFactory)
    {
        return \is_array($solutionOrFactory) && isset($solutionOrFactory[0]) && \is_callable($solutionOrFactory[0]);
    }

    private function resolveAlias($alias)
    {
        $alias = $this->cleanIdOrAlias($alias);

        return isset($this->aliases[$alias]) ? $this->aliases[$alias] : $alias;
    }

    private function cleanIdOrAlias($idOrAlias)
    {
        return $this->ignoreCase ? \strtolower($idOrAlias) : $idOrAlias;
    }

    /**
     * @return mixed[]
     */
    private function loadSolutions()
    {
        foreach ($this->solutions as $name => &$solution) {
            $solution = $this->loadSolution($name);
        }

        return $this->solutions;
    }

    /**
     * @param mixed $solution
     *
     * @return bool
     */
    protected function supportsSolution($solution)
    {
        $supportedClass = $this->supportedSolutionClass();

        return  null === $supportedClass || $solution instanceof $supportedClass;
    }

    protected function checkSolution($id, $solution)
    {
        if (!$this->supportsSolution($solution)) {
            throw new UnsupportedResolverException(
                \sprintf('Resolver "%s" must be "%s" "%s" given.', $id, $this->supportedSolutionClass(), \get_class($solution))
            );
        }
    }

    /**
     * default return null to accept mixed type.
     *
     * @return string|null supported class name
     */
    protected function supportedSolutionClass()
    {
        return null;
    }
}
