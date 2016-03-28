<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Request\Validator\Rule;

use GraphQL\Language\AST\FragmentDefinition;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Validator\ValidationContext;

class AbstractQuerySecurity
{
    /** @var FragmentDefinition[] */
    private $fragments = [];

    /** @var Type[]  */
    private $rootTypes = [];

    /**
     * @return \GraphQL\Language\AST\FragmentDefinition[]
     */
    protected function getFragments()
    {
        return $this->fragments;
    }

    /**
     * @return \GraphQL\Type\Definition\Type[]
     */
    protected function getRootTypes()
    {
        return $this->rootTypes;
    }

    /**
     * check if equal to 0 no check is done. Must be greater or equal to 0.
     *
     * @param $value
     */
    protected static function checkIfGreaterOrEqualToZero($name, $value)
    {
        if ($value < 0) {
            throw new \InvalidArgumentException(sprintf('$%s argument must be greater or equal to 0.', $name));
        }
    }

    protected function isRootType(ValidationContext $context)
    {
        $parentType = $context->getParentType();
        $isParentRootType = $parentType && in_array($parentType, $this->getRootTypes());

        return $isParentRootType;
    }

    protected function isIntrospectionType(ValidationContext $context)
    {
        $type = $this->retrieveCurrentType($context);
        $isIntrospectionType = $type && $type->name === '__Schema';

        return $isIntrospectionType;
    }

    protected function gatherRootTypes(ValidationContext $context)
    {
        $schema = $context->getSchema();
        $this->rootTypes = [$schema->getQueryType(), $schema->getMutationType(), $schema->getSubscriptionType()];
    }

    protected function gatherFragmentDefinition(ValidationContext $context)
    {
        // Gather all the fragment definition.
        // Importantly this does not include inline fragments.
        $definitions = $context->getDocument()->definitions;
        foreach ($definitions as $node) {
            if ($node instanceof FragmentDefinition) {
                $this->fragments[$node->name->value] = $node;
            }
        }
    }

    protected function retrieveCurrentType(ValidationContext $context)
    {
        $type = $context->getType();

        if ($type instanceof WrappingType) {
            $type = $type->getWrappedType(true);
        }

        return $type;
    }

    protected function getNodeType($node)
    {
        return $node instanceof Node ? $node->kind : null;
    }
}
