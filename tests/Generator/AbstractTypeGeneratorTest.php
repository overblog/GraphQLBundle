<?php

/*
 * This file is part of the OverblogGraphQLPhpGenerator package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLGenerator\Tests\Generator;

use Composer\Autoload\ClassLoader;
use Overblog\GraphQLGenerator\Generator\TypeGenerator;
use Overblog\GraphQLGenerator\Tests\TestCase;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractTypeGeneratorTest extends TestCase
{
    /** @var Filesystem */
    protected $filesystem;
    protected $tmpDir;
    protected $typeConfigs = [];
    /** @var TypeGenerator */
    protected $typeGenerator;
    /** @var ClassLoader */
    protected $classLoader;

    public function setUp()
    {
        $this->filesystem = new Filesystem();
        $this->tmpDir = sys_get_temp_dir() . '/mcgweb-graphql-generator';
        $this->filesystem->remove($this->tmpDir);
        $this->typeConfigs = $this->prepareTypeConfigs();
        $this->typeGenerator = new TypeGenerator();
        $this->typeGenerator->setExpressionLanguage(new ExpressionLanguage());
        $this->classLoader = require __DIR__ . '/../../vendor/autoload.php';
    }

    public function tearDown()
    {
        $this->filesystem->remove($this->tmpDir);
    }

    protected function generateClasses(array $typeConfigs = null, $tmpDir = null, $mode = true)
    {
        if (null === $typeConfigs) {
            $typeConfigs = $this->typeConfigs;
        }

        if (null === $tmpDir) {
            $tmpDir = $this->tmpDir;
        }

        $classes = $this->typeGenerator->generateClasses($typeConfigs, $tmpDir, $mode);

        $this->classLoader->addClassMap($classes);

        return $classes;
    }

    /**
     * @return array
     */
    protected function prepareTypeConfigs()
    {
        $yaml = new \Symfony\Component\Yaml\Parser();
        $typeConfigs = $yaml->parse(file_get_contents(__DIR__ . '/../starWarsSchema.yml'));

        return $this->processConfig($typeConfigs);
    }

    protected function processConfig(array $configs)
    {
        return array_map(
            function ($v) {
                if (is_array($v)) {
                    return call_user_func([$this, 'processConfig'], $v);
                } elseif (is_string($v) && 0 === strpos($v, '@=')) {
                    return new Expression(substr($v, 2));
                }

                return $v;
            },
            $configs
        );
    }

    protected function getType($type)
    {
        return call_user_func(["\\".$this->typeGenerator->getClassNamespace().'\\'.$type.'Type', 'getInstance']);
    }
}
