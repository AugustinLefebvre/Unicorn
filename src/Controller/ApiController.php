<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use App\Document\Friend;
use App\Document\Type;
use App\Repository\FriendRepository;
use App\Repository\TypeRepository;


class ApiController extends AbstractController
{
    public function __construct(private TypeRepository $typeRepo, private NormalizerInterface $normalizer, private SerializerInterface $serializer) {}

    #[Route('/api/get/friends', name: 'api_get_friend', methods: 'GET')]
    public function getFriends(FriendRepository $repo): JsonResponse
    {
        $friends = $repo->findAll();

        $friendsArray = $this->getTypeNames($friends);
        // return json with http code 200
        return $this->json($friendsArray, 200, []);
    }

    #[Route('/api/get/friendby', name: 'api_get_friend_by_filter', methods: 'GET')]
    public function getFriendByFilter(Request $request, FriendRepository $repo): JsonResponse
    {
        // if filter is a json, return friends with given name parameters
        $filter = $request->getContent();
        json_decode($filter);
        if (json_last_error() === JSON_ERROR_NONE && !is_int(json_decode($filter))) {
            $filter  = json_decode($filter, true);
            return $this->getFriendsByJsonFilter($filter, $repo);
        }

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

        //friends by Tag (only 1 tag filter)
        $friendsByTag = $this->getFriendsByTag($filter, $repo);

        // If no friend is found, return none (204), otherwise merge all matches
        if (count($friendsByName) === 0 && count($friendsByType) === 0 && count($friendsByValue) === 0 && count($friendsByTag) === 0) {
            // return empty json with http code 204
            return $this->json([], 204);
        } else {
            $friends = array_unique(array_merge($friendsByName, $friendsByType, $friendsByValue, $friendsByTag), SORT_REGULAR);
        }

        $friendsArray = $this->getTypeNames($friends);

        // return json with http code 200
        return $this->json($friendsArray, 200);
    }

    public function getTypeByName($name): string|null
    {
        $type = $this->typeRepo->findBy(['name' =>$name]);
        if (count($type) > 0) {
            return $type[0]->getId();
        }
        return null;
    }

    public function getTypeNames(array $friends): array
    {
        $friendsArray = array();
        //get the type name
        foreach ($friends as $friend) {
            $friend = $this->normalizer->normalize($friend);
            $friend['type'] = $this->typeRepo->find($friend['type'])->getName();
            $friendsArray[] = $friend;
        }
        return $friendsArray;
    }

    public function getFriendsByTag($tag, $repo): array
    {
        $friendsArray = array();
        foreach ($repo->findAll() as $friend) {
            if (in_array($tag, $friend->getTags())) {
                $friendsArray[] = $friend;
            }
        }
        return $friendsArray;
    }

    public function getFriendsByJsonFilter(array $filter, FriendRepository $repo): jsonResponse
    {
        // dd($filter);
        if (array_key_exists('type', $filter)) {
            $filter['type'] = $this->getTypeByName($filter['type']);
        }
        $friendsArray = $repo->findBy($filter);
        if (!empty($friendsArray)) {
            return $this->json($this->getTypeNames($friendsArray), 200);
        }
        // return empty json with http code 204
        return $this->json([], 204);
    }

    // #[Route('/api/post', name: 'api_post')]
    // public function post(Request $request, FriendRepository $repo): Response
    // {
    //     $friend = new Friend();
    //     return false;
    // }

    // USE ONCE ONLY, fills DB with test data
    #[Route('/setTestData', name: 'testData')]
    public function testData(FriendRepository $repo)
    {
        //dunno how to fixture with mongo
        // set 4 tags
        $type = new Type();
        $type->setName('UNICORN');
        $this->typeRepo->add($type);
        $type = new Type();
        $type->setName('GOD');
        $this->typeRepo->add($type);
        $type = new Type();
        $type->setName('HOOMAN');
        $this->typeRepo->add($type);
        $type = new Type();
        $type->setName('NOOB');
        $this->typeRepo->add($type, true);
        var_dump('added tags');
        // set 10 friends
        $types = $this->typeRepo->findAll();
        for ($i = 0; $i < 10; $i++) {
            $friend = new Friend();
            if ($i === 0) {
                $friend->setName('GOD');
            } else {
                $friend->setName('ami '.$i+1);
            }
            $friend->setType($types[mt_rand(0, 3)]->getId());
            $friend->setValue(mt_rand(1, 100));
            if ($i === 4) {
                $friend->setTags(array('HOOMAN'));
            } elseif ($i=== 7) {
                $friend->setTags(array('tag'.mt_rand(0, 2), 'ami 3', 'GOD'));
            } else {
                $friend->setTags(array('tag'.mt_rand(0, 2), 'tag'.mt_rand(3, 5)));
            }
            $repo->add($friend, $i === 9);
        }
        dd('added friends');
    }
}