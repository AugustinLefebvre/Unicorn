<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
// use Symfony\Component\HttpFoundation\Response;

use App\Document\Friend;
use App\Document\Type;
use App\Repository\FriendRepository;
use App\Repository\TypeRepository;


class ApiController extends AbstractController
{
    public function __construct(private ManagerRegistry $manager, private TypeRepository $typeRepo, private NormalizerInterface $normalizer) {}

    #[Route('/api/get/friend', name: 'api_get_friend', methods: 'GET')]
    public function getFriend(FriendRepository $repo): JsonResponse
    {
        $friends = $repo->findAll();

        $friendsArray = $this->getTypeNames($friends);
        // return json with http code 200
        return $this->json($friendsArray, 200, []);
    }

    #[Route('/api/get/friend/{filter}', name: 'api_get_friend_by_filter', methods: 'GET')]
    public function getFriendByFilter($filter, FriendRepository $repo): JsonResponse
    {
        //TODO: check if filter is a json => go to another function checking the given parameters

        //friends By Name
        $friendsByName = $repo->FindBy(['name' => $filter]);

        //friends by Type
        $typeId = $this->getTypeByName($filter);
        if (!is_null($typeId)) {
            $friendsByType = $repo->FindBy(['type' => $typeId]);
        } else {
            $friendsByType = array();
        }
        //friends By Value
        $friendsByValue = $repo->findBy(['value' => $filter]);

        //friends by Tag
        $friendsByTag = $this->getFriendByTag($filter);

        // If no friend is found, return all, otherwise merge all matches
        if (count($friendsByName) === 0 && count($friendsByType) === 0 && count($friendsByValue) === 0) {
            //TODO
            $friends = $repo->findAll();
        } else {
            $friends = array_unique(array_merge($friendsByName, $friendsByType, $friendsByValue), SORT_REGULAR);
        }

        $friendsArray = $this->getTypeNames($friends);

        // return json with http code 200
        return $this->json($friendsArray, 200, []);
    }

    public function getTypeByName($name): array|null
    {
        $type = $this->typeRepo->findBy(['name' =>$name]);
        if (count($type) > 0) {
            return $type[0]->getId();
        }
        return null;
    }

    public function getTypeNames(array $friends): array
    {
        //get the type name
        foreach ($friends as $friend) {
            $friend = $this->normalizer->normalize($friend);
            $friend['type'] = $this->typeRepo->find($friend['type'])->getName();
            $friendsArray[] = $friend;
        }
        return $friendsArray;
    }

    //TODO
    public function getFriendsByTag($tag): array|null
    {
        return null;
    }

    // #[Route('/api/post', name: 'api_post')]
    // public function post(Request $request, FriendRepository $repo): Response
    // {
    //     $friend = new Friend();
    //     return false;
    // }

    // #[Route('/testtype', name: 'testtype')]
    // public function testType()
    // {
    //     $repo = new TypeRepository($this->manager);
    //     $type = new Type();
    //     $type->setName('UNICORN');
    //     $repo->add($type);
    //     $type = new Type();
    //     $type->setName('GOD');
    //     $repo->add($type);
    //     $type = new Type();
    //     $type->setName('HOOMAN');
    //     $repo->add($type);
    //     $type = new Type();
    //     $type->setName('NOOB');
    //     $repo->add($type, true);
    //     dd('added types');
    // }

    // #[Route('/testfriend', name: 'testfriend')]
    // public function testFriends(TypeRepository $type)
    // {
    //     $repo = new FriendRepository($this->manager);
    //     $types = $type->findAll();
    //     for ($i = 0; $i < 10; $i++) {
    //         $friend = new Friend();
    //         $friend->setName('ami '.$i+1);
    //         $friend->setType($types[mt_rand(0, 3)]->getId());
    //         $friend->setValue(mt_rand(1, 100));
    //         $friend->setTags(array('1' => 'tag1', '2' => 'tag2'));
    //         $repo->add($friend, $i === 9);
    //     }
    //     dd('added friends');
 
    // }
}