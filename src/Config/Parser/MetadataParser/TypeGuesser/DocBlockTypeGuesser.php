<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\MetadataParser\TypeGuesser;

use Exception;
use phpDocumentor\Reflection\DocBlock\Tags\TagWithType;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\AbstractList;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\ContextFactory;
use phpDocumentor\Reflection\Types\Mixed_;
use phpDocumentor\Reflection\Types\Null_;
use phpDocumentor\Reflection\Types\Nullable;
use phpDocumentor\Reflection\Types\Object_;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;
use function sprintf;

class DocBlockTypeGuesser extends PhpTypeGuesser
{
    protected ?DocBlockFactory $factory;

    public function getName(): string
    {
        return 'Dock block';
    }

    public function supports(Reflector $reflector): bool
    {
        return $reflector instanceof ReflectionProperty || $reflector instanceof ReflectionMethod;
    }

    /**
     * @param ReflectionProperty|ReflectionMethod $reflector
     */
    public function guessType(ReflectionClass $reflectionClass, Reflector $reflector, array $filterGraphQLTypes = []): ?string
    {
        $contextFactory = new ContextFactory();
        $context = $contextFactory->createFromReflector($reflectionClass);
        try {
            $docBlock = $this->getParser()->create($reflector->getDocComment(), $context);
        } catch (Exception $e) {
            throw new TypeGuessingException(sprintf('Doc Block parsing failed with error: %s', $e->getMessage()));
        }
        $tagName = $reflector instanceof ReflectionProperty ? 'var' : 'return';
        $tags = $docBlock->getTagsByName($tagName);
        $tag = $tags[0] ?? null;
        if (!$tag || !$tag instanceof TagWithType) {
            throw new TypeGuessingException(sprintf('No @%s tag found in doc block or tag has no type', $tagName));
        }
        $type = $tag->getType();
        $isNullable = false;
        $isList = false;
        $isListNullable = false;
        $exceptionPrefix = sprintf('Tag @%s found', $tagName);

        if ($type instanceof Compound) {
            $type = $this->resolveCompound($type);
            if (!$type) {
                throw new TypeGuessingException(sprintf('%s, but composite types are only allowed with null. Ex: string|null.', $exceptionPrefix));
            }
            $isNullable = true;
        } elseif ($type instanceof Nullable) {
            $isNullable = true;
            $type = $type->getActualType();
        }

        if ($type instanceof AbstractList) {
            $isList = true;
            $isListNullable = $isNullable;
            $isNullable = false;
            $type = $type->getValueType();
            if ($type instanceof Compound) {
                $type = $this->resolveCompound($type);
                if (!$type) {
                    throw new TypeGuessingException(sprintf('%s, but composite types in array or iterable are only allowed with null. Ex: string|null.', $exceptionPrefix));
                }
                $isNullable = true;
            } elseif ($type instanceof Mixed_) {
                throw new TypeGuessingException(sprintf('%s, but the array values cannot be mixed type', $exceptionPrefix));
            }
        }

        if ($type instanceof Object_) {
            $className = $type->getFqsen();
            if (!$className) {
                throw new TypeGuessingException(sprintf('%s, but type "object" is too generic.', $exceptionPrefix, $className));
            }
            // Remove first '\' from returned class name
            $className = substr((string) $className, 1);
            $gqlType = $this->map->resolveType((string) $className, $filterGraphQLTypes);
            if (!$gqlType) {
                throw new TypeGuessingException(sprintf('%s, but target object "%s" is not a GraphQL Type class.', $exceptionPrefix, $className));
            }
        } else {
            $gqlType = $this->resolveTypeFromPhpType((string) $type);
            if (!$gqlType) {
                throw new TypeGuessingException(sprintf('%s, but unable to resolve type "%s" to a GraphQL scalar.', $exceptionPrefix, (string) $type));
            }
        }

        return $isList ? sprintf('[%s%s]%s', $gqlType, $isNullable ? '' : '!', $isListNullable ? '' : '!') : sprintf('%s%s', $gqlType, $isNullable ? '' : '!');
    }

    protected function resolveCompound(Compound $compound): ?Type
    {
        $typeNull = new Null_();
        if ($compound->getIterator()->count() > 2 || !$compound->contains($typeNull)) {
            return null;
        }
        $type = current(array_filter(iterator_to_array($compound->getIterator(), false), fn (Type $type) => (string) $type !== (string) $typeNull));

        return $type;
    }

    private function getParser(): DocBlockFactory
    {
        if (!isset($this->factory)) {
            $this->factory = DocBlockFactory::createInstance();
        }

        return $this->factory;
    }
}
