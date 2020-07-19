<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Hydrator\Repository;

use Doctrine\ORM\EntityRepository;
use Overblog\GraphQLBundle\Tests\Functional\Hydrator\Entity\Post;

class PostRepository extends EntityRepository
{
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        return new Post(15, "My Example Title", "Lorem ipsum dolor sit amet...");
    }
}