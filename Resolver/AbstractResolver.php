<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Resolver;

abstract class AbstractResolver implements ResolverInterface
{
    /**
     * @var array
     */
    private $solutions = [];

    /**
     * @var array
     */
    private $solutionOptions = [];

    /**
     * @var array
     */
    private $fullyLoadedSolutions = [];

    public function addSolution($name, callable $solutionFunc, array $solutionFuncArgs = [], array $options = [])
    {
        $this->fullyLoadedSolutions[$name] = false;
        $this->solutions[$name] = function () use ($name, $solutionFunc, $solutionFuncArgs) {
            $solution = call_user_func_array($solutionFunc, $solutionFuncArgs);
            $this->checkSolution($name, $solution);

            return $solution;
        };
        $this->solutionOptions[$name] = $options;

        return $this;
    }

    public function hasSolution($name)
    {
        return isset($this->solutions[$name]);
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function getSolution($name)
    {
        return $this->loadSolution($name);
    }

    /**
     * @return array
     */
    public function getSolutions()
    {
        return $this->loadSolutions();
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function getSolutionOptions($name)
    {
        return isset($this->solutionOptions[$name]) ? $this->solutionOptions[$name] : [];
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    private function loadSolution($name)
    {
        if (!$this->hasSolution($name)) {
            return null;
        }

        if ($this->fullyLoadedSolutions[$name]) {
            return $this->solutions[$name];
        } else {
            $loader = $this->solutions[$name];
            $this->solutions[$name] = $loader();
            $this->fullyLoadedSolutions[$name] = true;
            $this->postLoadSolution($this->solutions[$name]);

            return $this->solutions[$name];
        }
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
     */
    protected function postLoadSolution($solution)
    {
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

    protected function checkSolution($name, $solution)
    {
        if (!$this->supportsSolution($solution)) {
            throw new UnsupportedResolverException(
                sprintf('Resolver "%s" must be "%s" "%s" given.', $name, $this->supportedSolutionClass(), get_class($solution))
            );
        }
    }

    /**
     * default return null to accept mixed type.
     *
     * @return null|string supported class name
     */
    protected function supportedSolutionClass()
    {
        return;
    }
}
