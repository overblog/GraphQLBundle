<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\IsolatedResolver;

use Overblog\GraphQLBundle\Definition\Resolver\QueryInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class EchoQuery implements QueryInterface
{
    /**
     * @var ParameterBagInterface
     */
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function display(string $message): string
    {
        // @phpstan-ignore-next-line
        return $this->params->get('echo.prefix').$message;
    }
}
