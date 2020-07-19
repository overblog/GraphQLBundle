<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator\Converters;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class EntityConverter extends Converter
{
    private EntityManagerInterface $em;

    public function __construct(?EntityManager $entityManager)
    {
        if (null === $entityManager) {
            throw new ServiceNotFoundException(
                "Couldn't convert value, because no EntityManager service is found. 
                To use the 'EntityConverter' you need to install Doctrine ORM first. 
                See: 'https://symfony.com/doc/current/doctrine.html'"
            );
        }

        $this->em = $entityManager;
    }

    /**
     * @param $value
     * @param Entity $entityAnnotation
     *
     * @return object|null
     */
    function convert($value, $entityAnnotation)
    {
        return $this->em->getRepository($entityAnnotation->value)->find($value);
    }
}