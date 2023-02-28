<?php

namespace App\DataFixtures\MongoDB;

use App\Document\Friend;
use App\Document\Type;
use Doctrine\Bundle\MongoDBBundle\Fixture;
use Doctrine\ODM\MongoDB\DocumentManager;

class AppFixtures extends Fixture
{
    public function load(DocumentManager $manager)
    {
        $type = new Type();
        $type->setName('UNICORN');
        $manager->persist($type);
        $type = new Type();
        $type->setName('GOD');
        $manager->persist($type);
        $type = new Type();
        $type->setName('HOOMAN');
        $manager->persist($type);
        $type = new Type();
        $type->setName('NOOB');
        $manager->persist($type);

        for ($i = 0; $i < 10; $i++) {
            $friend = new Friend();
            $friend->setName('ami '.$i)
                ->setType(mt_rand(1, 5))
                ->setValue(mt_rand(1, 100))
                ->setTags(array('1' => 'tag1', '2' => 'tag2'));
            $manager->persist($friend);
        }

        $manager->flush();
    }
}