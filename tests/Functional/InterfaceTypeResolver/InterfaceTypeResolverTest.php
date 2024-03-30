<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\InterfaceTypeResolver;

use Closure;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Component\HttpKernel\Kernel;

final class InterfaceTypeResolverTest extends TestCase
{
    private Closure $loader;

    protected function setUp(): void
    {
        parent::setUp();
        // load types
        $this->loader = function ($class): void {
            if (preg_match('@^'.preg_quote('Overblog\GraphQLBundle\Attributes\__DEFINITIONS__\\').'(.*)$@', $class, $matches)) {
                $file = sys_get_temp_dir().'/OverblogGraphQLBundle/'.Kernel::VERSION.'/attributes/cache/testattributes/overblog/graphql-bundle/__definitions__/'.$matches[1].'.php';
                if (file_exists($file)) {
                    require $file;
                }
            }
        };
        spl_autoload_register($this->loader);
        static::bootKernel(['test_case' => 'attributes']);
    }

    public function testAutoTypeResolution(): void
    {
        $query = <<<'EOF'
            query res {
                getDemoItems {
                    fieldInterface
                    ... on Type1 {
                        field1
                    }
                    ... on Type2 {
                        field1
                    }
                }
            }
            EOF;
        $result = $this->executeGraphQLRequest($query);

        $this->assertEquals($result['data']['getDemoItems'][0]['fieldInterface'], 'field_interface');
        $this->assertEquals($result['data']['getDemoItems'][0]['field1'], 'type1_field1');
        $this->assertEquals($result['data']['getDemoItems'][1]['fieldInterface'], 'field_interface');
        $this->assertEquals($result['data']['getDemoItems'][1]['field1'], 'type2_field1');
    }
}
