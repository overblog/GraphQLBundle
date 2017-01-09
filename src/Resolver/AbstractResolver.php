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

    public function addSolution($name, $solution, $options = [])
    {
        if (!$this->supportsSolution($solution)) {
            throw new UnsupportedResolverException(
                sprintf('Resolver "%s" must be "%s" "%s" given.', $name, $this->supportedSolutionClass(), get_class($solution))
            );
        }

        $this->solutions[$name] = $solution;
        $this->solutionOptions[$name] = $options;

        return $this;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function getSolution($name)
    {
        return isset($this->solutions[$name]) ? $this->solutions[$name] : null;
    }

    /**
     * @return array
     */
    public function getSolutions()
    {
        return $this->solutions;
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
     * @param mixed $solution
     *
     * @return bool
     */
    protected function supportsSolution($solution)
    {
        $supportedClass = $this->supportedSolutionClass();

        return  null === $supportedClass || $solution instanceof $supportedClass;
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
