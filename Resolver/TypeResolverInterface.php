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

use GraphQL\Schema;

interface TypeResolverInterface extends  ResolverInterface
{
    /**
     * @param Schema $schema
     *
     * @return TypeResolver
     */
    public function setSchema(Schema $schema);

    /**
     * @param array $mapping
     *
     * @return TypeResolver
     */
    public function setMapping(array $mapping);
}
