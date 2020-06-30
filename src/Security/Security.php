<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Security;

use LogicException;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Security\Core\User\UserInterface;
use function array_reduce;

final class Security
{
    /**
     * @var CoreSecurity
     */
    private $coreSecurity;

    public function __construct(?CoreSecurity $security)
    {
        // @phpstan-ignore-next-line
        $this->coreSecurity = $security ?? new class() {
            public function isGranted(): bool
            {
                throw new LogicException('The "symfony/security-core" component is required.');
            }

            public function getUser(): UserInterface
            {
                throw new LogicException('The "symfony/security-core" component is required.');
            }
        };
    }

    public function getUser(): ?UserInterface
    {
        return $this->coreSecurity->getUser();
    }

    /**
     * @param mixed $attributes
     * @param mixed $subject
     */
    public function isGranted($attributes, $subject = null): bool
    {
        return $this->coreSecurity->isGranted($attributes, $subject);
    }

    public function hasAnyPermission(object $object, array $permissions): bool
    {
        return array_reduce(
            $permissions,
            fn ($isGranted, $permission) => $isGranted || $this->isGranted($permission, $object),
            false
        );
    }

    public function hasAnyRole(array $roles): bool
    {
        return array_reduce(
            $roles,
            function ($isGranted, $role) {
                return $isGranted || $this->isGranted($role);
            },
            false
        );
    }

    /**
     * @param mixed $object
     * @param mixed $permission
     */
    public function hasPermission($object, $permission): bool
    {
        return $this->isGranted($permission, $object);
    }

    public function hasRole(string $role): bool
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
