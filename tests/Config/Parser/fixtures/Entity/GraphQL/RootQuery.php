<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\Entity\GraphQL;

use Overblog\GraphQLBundle\Annotation as GQL;

class RootQuery
{
    /**
     * @GQL\GraphQLQuery(
     *     type="Character",
     *     method="App\\MyResolver::getHero"
     * )
     */
    private $hero;

    /**
     * @GQL\GraphQLQuery(
     *     type="Droid",
     *     method="App\\MyResolver::getDroid"
     * )
     */
    private $droid;
}
