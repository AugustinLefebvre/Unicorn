<?php

namespace App\Repository;


use App\Document\Type;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;

class TypeRepository extends ServiceDocumentRepository {
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Type::class);
    }

    public function add(Type $type, bool $flush = false): void
    {
        $this->getDocumentManager()->persist($type);
        if ($flush) {
            $this->getDocumentManager()->flush();
        }
    }

    public function remove(Type $type, bool $flush = false): void
    {
        $this->getDocumentManager()->remove($type);

        if ($flush) {
            $this->getDocumentManager()->flush();
        }
    }
}