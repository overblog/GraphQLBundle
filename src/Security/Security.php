<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Security;

use LogicException;
use Symfony\Bundle\SecurityBundle\Security as BundleSecurity;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Security\Core\User\UserInterface;

use function array_reduce;

if (Kernel::VERSION_ID >= 60200) {
    final class Security extends BaseSecurity
    {
        public function __construct(?BundleSecurity $security)
        {
            parent::__construct($security);
        }
    }
} else {
    final class Security extends BaseSecurity
    {
        public function __construct(?CoreSecurity $security)
        {
            parent::__construct($security);
        }
    }
}

abstract class BaseSecurity
{
    /**
     * @var CoreSecurity|BundleSecurity
     */
    private $coreSecurity;

    /**
     * @param CoreSecurity|BundleSecurity $security
     */
    public function __construct($security)
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
            fn ($isGranted, $role) => $isGranted || $this->isGranted($role),
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
