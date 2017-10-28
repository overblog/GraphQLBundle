<?php

namespace Overblog\GraphQLBundle\Config\Parser;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class YamlParser implements ParserInterface
{
    /** @var Parser */
    private static $yamlParser;

    /**
     * @param SplFileInfo      $file
     * @param ContainerBuilder $container
     *
     * @return array
     */
    public static function parse(SplFileInfo $file, ContainerBuilder $container)
    {
        if (null === self::$yamlParser) {
            self::$yamlParser = new Parser();
        }

        try {
            $typesConfig = self::$yamlParser->parse($file->getContents());
            $container->addResource(new FileResource($file->getRealPath()));
        } catch (ParseException $e) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not contain valid YAML.', $file), 0, $e);
        }

        return (array) $typesConfig;
    }
}
