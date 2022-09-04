<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\EnumPhp;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

final class EnumPhpTest extends TestCase
{
    protected function setUp(): void
    {
        static::bootKernel(['test_case' => 'enumPhp']);
    }

    public static function resolveQueryEnum(): EnumPhp
    {
        return EnumPhp::VALUE2;
    }

    public function testEnumSerializedToName(): void
    {
        $query = 'query { enum }';
        $result = $this->executeGraphQLRequest($query);

        $this->assertEquals($result['data']['enum'], EnumPhp::VALUE2->name);
    }

    public static function resolveQueryEnumAsInput($enumParam = null)
    {
        return EnumPhp::VALUE2 === $enumParam ? 'OK' : 'KO';
    }

    public function testEnumLiteralParsedAsPhpEnum(): void
    {
        $query = 'query { enumParser(enum: VALUE2) }';

        $result = $this->executeGraphQLRequest($query);
        $this->assertEquals($result['data']['enumParser'], 'OK');
    }

    public function testEnumVariableParsedAsPhpEnum(): void
    {
        $query = 'query($enum: EnumPhp!) { enumParser(enum: $enum) }';
        $result = $this->executeGraphQLRequest($query, [], null, ['enum' => EnumPhp::VALUE2->name]);

        $this->assertEquals($result['data']['enumParser'], 'OK');
    }
}
