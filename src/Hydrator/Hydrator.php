<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class Hydrator
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    public function hydrate(ArgumentInterface $args, ResolveInfo $info)
    {
        $requestedField = $info->parentType->getField($info->fieldName);

        foreach ($args->getArrayCopy() as $key => $value) {
            $argType = $requestedField->getArg($key)->getType(); /** @var Type $argType */
            $unwrappedType = Type::getNamedType($argType);

            // If primitive type or no 'model' is set
            if (!isset($unwrappedType->config['model']) /* || Type::isBuiltInType($unwrappedType) */) {
                continue;
            }

            // Fill model
            $model = new $unwrappedType->config['model']();

            foreach ($value as $property => $propertyValue) {
                if (isset($model->$property))

                $model->$property = $propertyValue;
            }

//            $hydrator = $this->getHydratorForType($unwrappedType);
//
//            // Collection of input objects
//            if (Type::getNullableType($argType) instanceof ListOfType) {
//                $collection = [];
//                foreach ($value as $item) {
//                    $collection[] = $hydrator->hydrate($unwrappedType, $item);
//                }
//                $value = $collection;
//            }
//            // Single input object
//            else {
//                $value = $hydrator->hydrate($unwrappedType, $value);
//            }
//
//            $hydrated[$key] = $value;
        }

        $x = $info;
        $y = $args;

        return 'something';
    }
}
