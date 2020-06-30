<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser;

use DOMElement;
use SplFileInfo;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use function array_merge;
use function is_array;
use function sprintf;

class XmlParser implements ParserInterface
{
    /**
     * {@inheritdoc}
     */
    public static function parse(SplFileInfo $file, ContainerBuilder $container, array $configs = []): array
    {
        $typesConfig = [];
        $container->addResource(new FileResource($file->getRealPath()));
        try {
            $xml = XmlUtils::loadFile($file->getRealPath());
            foreach ($xml->documentElement->childNodes as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }
                $values = XmlUtils::convertDomElementToArray($node);
                if (is_array($values)) {
                    $typesConfig = array_merge($typesConfig, $values);
                }
            }
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException(sprintf('Unable to parse file "%s".', $file), $e->getCode(), $e);
        }

        return $typesConfig;
    }
}
