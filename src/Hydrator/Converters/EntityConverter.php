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
            // TODO: change the message
            throw new ServiceNotFoundException("EntityManager not found.");
        }

        $this->em = $entityManager;
    }

    /**
     * @param $values
     * @param Entity $entityAnnotation
     *
     * @return object|null
     */
    function convert($values, $entityAnnotation)
    {
        // TODO: make id property configurable
        $idProperty = 'id';

        return $this->em->getRepository($entityAnnotation->value)->find($values->$idProperty);
    }
}