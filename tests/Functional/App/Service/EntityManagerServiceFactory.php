<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Service;

use Doctrine\DBAL\DriverManager;
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

        $conn = DriverManager::getConnection([
            'driver'   => 'pdo_mysql',
            'user'     => 'root',
            'password' => 'root',
            'dbname'   => 'overblog_testdb',
        ]);

        return EntityManager::create($conn, $config);
    }
}