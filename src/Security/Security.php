<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Security;

use Symfony\Component\Security\Core\Security as CoreSecurity;

final class Security
{
    private $coreSecurity;

    public function __construct(?CoreSecurity $security)
    {
        $this->coreSecurity = $security ?? new class() {
            public function isGranted(): void
            {
                throw new \LogicException('The "symfony/security-core" component is required.');
            }

            public function getUser(): void
            {
                throw new \LogicException('The "symfony/security-core" component is required.');
            }
        };
    }

    public function getUser()
    {
        return $this->coreSecurity->getUser();
    }

    public function isGranted($attributes, $subject = null): bool
    {
        return $this->coreSecurity->isGranted($attributes, $subject);
    }

    public function hasAnyPermission($object, array $permissions): bool
    {
        return \array_reduce(
            $permissions,
            function ($isGranted, $permission) use ($object) {
                return $isGranted || $this->isGranted($permission, $object);
            },
            false
        );
    }

    public function hasAnyRole(array $roles): bool
    {
        return \array_reduce(
            $roles,
            function ($isGranted, $role) {
                return $isGranted || $this->isGranted($role);
            },
            false
        );
    }

    public function hasPermission($object, $permission): bool
    {
        return $this->isGranted($permission, $object);
    }

    public function hasRole($role): bool
    {
        return $this->isGranted($role);
    }

    public function isAnonymous(): bool
    {
        return $this->isGranted('IS_AUTHENTICATED_ANONYMOUSLY');
    }

    public function isAuthenticated(): bool
    {
        return $this->hasAnyRole(['IS_AUTHENTICATED_REMEMBERED', 'IS_AUTHENTICATED_FULLY']);
    }

    public function isFullyAuthenticated(): bool
    {
        return $this->isGranted('IS_AUTHENTICATED_FULLY');
    }

    public function isRememberMe(): bool
    {
        return $this->isGranted('IS_AUTHENTICATED_REMEMBERED');
    }
}
