<?php
namespace Overblog\GraphQLBundle\Error;

/**
 * Class UserError
 * @package Overblog\GraphQLBundle\Error
 *
 * use this exception to render raw message to user if not in debug mode
 */
class UserError extends \Exception
{
}
