<?php

namespace Overblog\GraphQLBundle\Tests\Error;

use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;

class CustomErrorFormatter
{
    /**
     * @param \Throwable $e
     *
     * @return array
     */
    public static function format($e)
    {
        $code = $e->getCode();
        if ($e instanceof Error && $e->getPrevious()) {
            $code = $e->getPrevious()->getCode();
        }

        $formattedError = FormattedError::createFromException($e);
        $formattedError['code'] = $code;

        return $formattedError;
    }

    /**
     * @param \Throwable $e
     *
     * @return array
     */
    public function __invoke($e)
    {
        return static::format($e);
    }
}
