<?php

namespace Overblog\GraphQLBundle\Tests\Error;

use GraphQL\Executor\Promise\Promise;
use Overblog\GraphQLBundle\Executor\Promise\Adapter\ReactPromiseAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\PhpProcess;

class ReactPromiseAdapterTest extends TestCase
{
    /** @var ReactPromiseAdapter */
    private $adapter;

    public function setUp()
    {
        $this->adapter = new ReactPromiseAdapter();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "Overblog\GraphQLBundle\Executor\Promise\Adapter\ReactPromiseAdapter::wait" method must be call with compatible a Promise.
     */
    public function testWaitWithNotSupportedPromise()
    {
        $noSupportedPromise = new Promise(new \stdClass(), $this->adapter);
        $this->adapter->wait($noSupportedPromise);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Promise has been rejected!
     */
    public function testWaitRejectedPromise()
    {
        $rejected = $this->adapter->createRejected(new \Exception('Promise has been rejected!'));
        $this->adapter->wait($rejected);
    }

    public function testWaitAsyncPromise()
    {
        $output = 'OK!';
        $process = new PhpProcess(<<<EOF
<?php
usleep(30);
echo '$output';
EOF
        );

        $promise = $this->adapter->create(function (callable $resolve) use (&$process) {
            $process->start(function () use ($resolve, &$process) {
                $output = $process->getOutput();
                $resolve($output);
            });
        });

        $this->assertEquals(
            $output,
            $this->adapter->wait($promise, function () use (&$process) {
                $process->wait();
            })
        );
    }
}
