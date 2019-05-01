<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type(interfaces={"Character"}, resolveField="value")
 * @GQL\Description("The Sith type")
 * @GQL\Access("isAuthenticated()")
 * @GQL\IsPublic("isAuthenticated()")
 */
class Sith extends Character
{
    /**
     * @GQL\Field(type="String!")
     * @GQL\Access("hasRole('SITH_LORD')")
     */
    protected $realName;

    /**
     * @GQL\Field(type="String!")
     * @GQL\IsPublic("hasRole('SITH_LORD')")
     */
    protected $location;

    /**
     * @GQL\Field(type="Sith", resolve="service('master_resolver').getMaster(value)")
     */
    protected $currentMaster;

    /**
     * @GQL\Field(
     *   type="[Character]",
     *   name="victims",
     *   args={
     *     @GQL\Arg(name="jediOnly", type="Boolean", description="Only Jedi victims", default=false)
     *   }
     * )
     */
    public function getVictims(bool $jediOnly = false)
    {
        return [];
    }
}
