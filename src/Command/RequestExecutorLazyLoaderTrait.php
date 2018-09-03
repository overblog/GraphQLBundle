<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Command;

use Overblog\GraphQLBundle\Request\Executor as RequestExecutor;

trait RequestExecutorLazyLoaderTrait
{
    /** @var RequestExecutor */
    private $requestExecutor;

    /** @var array */
    private $requestExecutorFactory;

    public function setRequestExecutorFactory(array $requestExecutorFactory): void
    {
        $this->requestExecutorFactory = $requestExecutorFactory;
    }

    /**
     * @return RequestExecutor
     */
    protected function getRequestExecutor(): RequestExecutor
    {
        if (null === $this->requestExecutor && null !== $this->requestExecutorFactory) {
            $this->requestExecutor = \call_user_func_array(...$this->requestExecutorFactory);
        }

        return $this->requestExecutor;
    }
}
