<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser;

use SplFileInfo;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use function file_get_contents;
use function is_array;
use function sprintf;

class YamlParser implements ParserInterface
{
    private static Parser $yamlParser;

    public static function parse(SplFileInfo $file, ContainerBuilder $container, array $configs = []): array
    {
        if (!isset(self::$yamlParser)) {
            self::$yamlParser = new Parser();
        }
        $container->addResource(new FileResource($file->getRealPath()));

        try {
            $typesConfig = self::$yamlParser->parse(file_get_contents($file->getPathname()));
        } catch (ParseException $e) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not contain valid YAML.', $file), 0, $e);
        }

        return is_array($typesConfig) ? $typesConfig : [];
    }
}
