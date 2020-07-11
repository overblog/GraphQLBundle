<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Resolver;

use Closure;
use function func_get_args;

class Toto
{
    public const PRIVATE_PROPERTY_WITH_GETTER_VALUE = 'IfYouWantMeUseMyGetter';
    public const PRIVATE_PROPERTY_WITH_GETTER2_VALUE = 'IfYouWantMeUseMyGetter2';
    public const PRIVATE_PROPERTY_WITHOUT_GETTER = 'ImNotAccessibleFromOutside:D';

    private string $privatePropertyWithoutGetter = self::PRIVATE_PROPERTY_WITHOUT_GETTER;
    private string $privatePropertyWithGetter = self::PRIVATE_PROPERTY_WITH_GETTER_VALUE;
    private string $private_property_with_getter2 = self::PRIVATE_PROPERTY_WITH_GETTER2_VALUE;
    public string $name = 'public';

    public function getPrivatePropertyWithGetter(): string
    {
        return $this->privatePropertyWithGetter;
    }

    public function getPrivatePropertyWithGetter2(): string
    {
        return $this->private_property_with_getter2;
    }

    public function getPrivatePropertyWithoutGetterUsingCallBack(): Closure
    {
        return function () {
            return $this->privatePropertyWithoutGetter;
        };
    }

    public function resolve(): array
    {
        return func_get_args();
    }
}
