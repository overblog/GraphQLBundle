<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\Setup;

class EntityManagerServiceFactory
{
    /**
     * @throws ORMException
     */
    public static function createEntityManager(): EntityManagerInterface
    {
        $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/src"), true, null, null, false);

        $conn = array(
            'driver' => 'pdo_sqlite',
            'path' => __DIR__ . '/db.sqlite',
        );

        // obtaining the entity manager
        return EntityManager::create($conn, $config);
    }
}