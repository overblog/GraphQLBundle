<?php

namespace Overblog\GraphQLBundle\Definition\Resolver;

use Overblog\GraphQLBundle\Error\UserWarning;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;


/**
 * Class AbstractResolver
 * This class provides a base for Query and Mutation resolver implementing these Symfony controller methods:
 * - isGranted
 * - denyAccessUnlessGranted
 * @package Overblog\GraphQLBundle\Definition\Resolver
 * @author Sylvain Fabre sylvain@assoconnect.com
 */
Abstract class AbstractResolver
{

    private $authorizationChecker;

    /**
     * @required
     */
    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Checks if the attributes are granted against the current authentication token and optionally supplied subject.
     *
     * @final
     */
    public function isGranted($attributes, $subject = null): bool
    {
        return $this->authorizationChecker->isGranted($attributes, $subject);
    }

    /**
     * Throws an exception unless the attributes are granted against the current authentication token and optionally
     * supplied subject.
     *
     * @throws UserWarning
     *
     * @final
     */
    public function denyAccessUnlessGranted($attributes, $subject = null, string $message = 'Access denied to this field')
    {
        if (!$this->isGranted($attributes, $subject)) {
            throw new UserWarning($message);
        }
    }

}