<?php

namespace App\Repository;


use App\Document\Friend;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;

class FriendRepository extends ServiceDocumentRepository {
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Friend::class);
    }

    public function add(Friend $friend, bool $flush = false): void
    {
        $this->getDocumentManager()->persist($friend);
        if ($flush) {
            $this->getDocumentManager()->flush();
        }
    }

    public function remove(Friend $friend, bool $flush = false): void
    {
        $this->getDocumentManager()->remove($friend);

        if ($flush) {
            $this->getDocumentManager()->flush();
        }
    }
}