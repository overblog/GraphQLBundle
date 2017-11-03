<?php

namespace Overblog\GraphQLBundle\Config\Parser;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Finder\SplFileInfo;

class XmlParser implements ParserInterface
{
    /*
     * @param SplFileInfo      $file
     * @param ContainerBuilder $container
     *
     * @return array
     */
    public static function parse(SplFileInfo $file, ContainerBuilder $container)
    {
        $typesConfig = [];

        try {
            $xml = XmlUtils::loadFile($file->getRealPath());
            foreach ($xml->documentElement->childNodes as $node) {
                if (!$node instanceof \DOMElement) {
                    continue;
                }
                $values = XmlUtils::convertDomElementToArray($node);
                $typesConfig = array_merge($typesConfig, $values);
            }
            $container->addResource(new FileResource($file->getRealPath()));
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException(sprintf('Unable to parse file "%s".', $file), $e->getCode(), $e);
        }

        return $typesConfig;
    }
}
