<?php

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\MutationInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Overblog\GraphQLBundle\OverblogGraphQLBundle;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;

class AutoMappingPass implements CompilerPassInterface
{
    private static $serviceSubclassTagMapping = [
        MutationInterface::class => 'overblog_graphql.mutation',
        ResolverInterface::class => 'overblog_graphql.resolver',
        Type::class => 'overblog_graphql.type',
    ];

    public function process(ContainerBuilder $container)
    {
        $enabled = $container->getParameter('overblog_graphql.auto_mapping.enabled');
        // enabled auto mapping for all bundles and custom dirs ?
        if ($enabled) {
            $directories = $container->getParameter('overblog_graphql.auto_mapping.directories');
            $bundles = $container->getParameter('kernel.bundles');
            $directories = array_merge(
                array_map(
                    function ($class) {
                        $bundleDir = $this->bundleDir($class);

                        return $bundleDir.'/GraphQL';
                    },
                    $bundles
                ),
                $directories
            );
            // add app dir
            if ($container->hasParameter('kernel.root_dir')) {
                $directories[] = $container->getParameter('kernel.root_dir').'/GraphQL';
            }
        } else {
            // enabled auto mapping only for this bundle
            $directories = [$this->bundleDir(OverblogGraphQLBundle::class).'/GraphQL'];
        }
        $directoryList = [];

        foreach ($directories as $directory) {
            list($reflectionClasses, $directories) = $this->reflectionClassesFromDirectory($directory);
            $directoryList = array_merge($directoryList, $directories);
            $this->addServicesDefinitions($container, $reflectionClasses);
        }

        foreach ($directoryList as $directory => $v) {
            $directory = realpath($directory);
            $container->addResource(new DirectoryResource($directory, '/\.php$/'));
        }
    }

    /**
     * @param ContainerBuilder   $container
     * @param \ReflectionClass[] $reflectionClasses
     */
    private function addServicesDefinitions(ContainerBuilder $container, array $reflectionClasses)
    {
        foreach ($reflectionClasses as $reflectionClass) {
            $this->addServiceDefinition($container, $reflectionClass);
        }
    }

    private function addServiceDefinition(ContainerBuilder $container, \ReflectionClass $reflectionClass)
    {
        $className = $reflectionClass->getName();
        $definition = $container->setDefinition($className, new Definition($className));
        $definition->setPublic(false);
        $definition->setAutowired(true);
        if (is_subclass_of($definition->getClass(), ContainerAwareInterface::class)) {
            $definition->addMethodCall('setContainer', [new Reference('service_container')]);
        }
        $this->addDefinitionTags($definition, $reflectionClass);
    }

    private function addDefinitionTags(Definition $definition, \ReflectionClass $reflectionClass)
    {
        $className = $definition->getClass();

        foreach (self::$serviceSubclassTagMapping as $subclass => $tagName) {
            if (!$reflectionClass->isSubclassOf($subclass)) {
                continue;
            }

            if (Type::class !== $subclass) {
                $publicReflectionMethods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
                $isAliased = $reflectionClass->implementsInterface(AliasedInterface::class);
                foreach ($publicReflectionMethods as $publicReflectionMethod) {
                    if ('__construct' === $publicReflectionMethod->name || $isAliased && 'getAliases' === $publicReflectionMethod->name) {
                        continue;
                    }
                    $definition->addTag($tagName, ['method' => $publicReflectionMethod->name]);
                }
                if ($isAliased) {
                    $this->addDefinitionTagsFromAliasesMethod($definition, $className, $tagName, true);
                }
            } else {
                $definition->addTag($tagName);
                $this->addDefinitionTagsFromAliasesMethod($definition, $className, $tagName, false);
            }
        }
    }

    private function addDefinitionTagsFromAliasesMethod(Definition $definition, $className, $tagName, $withMethod)
    {
        // from getAliases
        if (!is_callable([$className, 'getAliases'])) {
            return;
        }
        $aliases = call_user_func([$className, 'getAliases']);

        foreach ($aliases as $key => $alias) {
            $definition->addTag($tagName, $withMethod ? ['alias' => $alias, 'method' => $key] : ['alias' => $alias]);
        }
    }

    private function subclass($class)
    {
        $interfaces = array_keys(self::$serviceSubclassTagMapping);

        foreach ($interfaces as $interface) {
            if (is_a($class, $interface, true)) {
                return $interface;
            }
        }

        return false;
    }

    /**
     * Gets the classes reflection of class in the given directory.
     *
     * @param string $directory
     *
     * @return array
     */
    private function reflectionClassesFromDirectory($directory)
    {
        $classes = [];
        $directoryList = [];
        $includedFiles = [];
        $reflectionClasses = [];

        $finder = new Finder();
        try {
            $finder->in($directory)->files()->name('*.php');
        } catch (\InvalidArgumentException $e) {
            return [$reflectionClasses, $directoryList];
        }

        foreach ($finder as $file) {
            $directoryList[$file->getPath()] = true;
            $sourceFile = $file->getRealpath();
            if (!preg_match('(^phar:)i', $sourceFile)) {
                $sourceFile = realpath($sourceFile);
            }

            require_once $sourceFile;
            $includedFiles[$sourceFile] = true;
        }

        $declared = get_declared_classes();
        foreach ($declared as $className) {
            $subclass = $this->subclass($className);
            if (false === $subclass) {
                continue;
            }
            $reflectionClass = new \ReflectionClass($className);
            $reflectionClasses[$className] = $reflectionClass;
            $sourceFile = $reflectionClass->getFileName();

            if ($reflectionClass->isAbstract()) {
                continue;
            }

            if (isset($includedFiles[$sourceFile])) {
                $classes[$className] = true;
            }
        }

        return [array_intersect_key($reflectionClasses, $classes), $directoryList];
    }

    private function bundleDir($bundleClass)
    {
        $bundle = new \ReflectionClass($bundleClass);
        $bundleDir = dirname($bundle->getFileName());

        return $bundleDir;
    }
}
