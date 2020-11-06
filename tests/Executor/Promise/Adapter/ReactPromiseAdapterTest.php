<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Executor\Promise\Adapter;

use Exception;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\Promise;
use InvalidArgumentException;
use Overblog\GraphQLBundle\Executor\Promise\Adapter\ReactPromiseAdapter;
use PHPUnit\Framework\TestCase;
use React\Promise\FulfilledPromise;
use stdClass;
use Symfony\Component\Process\PhpProcess;
use function sprintf;

class ReactPromiseAdapterTest extends TestCase
{
    private ReactPromiseAdapter $adapter;

    public function setUp(): void
    {
        $this->adapter = new ReactPromiseAdapter();
    }

    public function testWaitWithNotSupportedPromise(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'The "%s::wait" method must be call with compatible a Promise.',
            ReactPromiseAdapter::class
        ));
        $noSupportedPromise = new Promise(new stdClass(), $this->adapter);
        $this->adapter->wait($noSupportedPromise);
    }

    public function testWaitRejectedPromise(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Promise has been rejected!');
        $rejected = $this->adapter->createRejected(new Exception('Promise has been rejected!'));
        $this->adapter->wait($rejected);
    }

    public function testWaitAsyncPromise(): void
    {
        $output = 'OK!';
        $process = new PhpProcess(<<<EOF
<?php
usleep(30);
echo '$output';
EOF
        );
        $result = new ExecutionResult(['output' => $output]);

        $promise = $this->adapter->create(function (callable $resolve) use (&$process, $result): void {
            $process->start(function () use ($resolve, $result): void {
                $resolve($result);
            });
        });

        $this->assertSame(
            $result,
            $this->adapter->wait($promise, function () use (&$process): void {
                $process->wait();
            })
        );
    }

    /**
     * TODO: replace deprecated code.
     */
    public function testSkipsConversionWhenPromiseIsAGraphQlOne(): void
    {
        $reactAdapter = new ReactPromiseAdapter();
        $reactPromise = new FulfilledPromise(1);

        $promise = $reactAdapter->convertThenable($reactPromise);
        // Test it's already converted then skip it
        $reactAdapter->convertThenable($promise);

        $this->assertInstanceOf(Promise::class, $promise);
        $this->assertInstanceOf(FulfilledPromise::class, $promise->adoptedPromise); // @phpstan-ignore-line
    }
}
