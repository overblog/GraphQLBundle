<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator\Converters;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class EntityConverter extends Converter
{
    private EntityManagerInterface $em;

    public function __construct(?EntityManagerInterface $entityManager)
    {
        if (null === $entityManager) {
            throw new ServiceNotFoundException("Cannot use EntityConverter, because Doctrine ORM is not installed.");
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