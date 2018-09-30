<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Executor\Promise\Adapter;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\Promise;
use Overblog\GraphQLBundle\Executor\Promise\Adapter\ReactPromiseAdapter;
use PHPUnit\Framework\TestCase;
use React\Promise\FulfilledPromise;
use Symfony\Component\Process\PhpProcess;

class ReactPromiseAdapterTest extends TestCase
{
    /** @var ReactPromiseAdapter */
    private $adapter;

    public function setUp(): void
    {
        $this->adapter = new ReactPromiseAdapter();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "Overblog\GraphQLBundle\Executor\Promise\Adapter\ReactPromiseAdapter::wait" method must be call with compatible a Promise.
     */
    public function testWaitWithNotSupportedPromise(): void
    {
        $noSupportedPromise = new Promise(new \stdClass(), $this->adapter);
        $this->adapter->wait($noSupportedPromise);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Promise has been rejected!
     */
    public function testWaitRejectedPromise(): void
    {
        $rejected = $this->adapter->createRejected(new \Exception('Promise has been rejected!'));
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

    public function testSkipsConversionWhenPromiseIsAGraphQlOne(): void
    {
        $reactAdapter = new ReactPromiseAdapter();
        $reactPromise = new FulfilledPromise(1);

        $promise = $reactAdapter->convertThenable($reactPromise);
        // Test it's already converted then skip it
        $reactAdapter->convertThenable($promise);

        $this->assertInstanceOf(Promise::class, $promise);
        $this->assertInstanceOf(FulfilledPromise::class, $promise->adoptedPromise);
    }
}
