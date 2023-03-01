<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Doctrine\ODM\MongoDB\DocumentManager;

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
        return $this->json($friendsArray, 200, [], ['groups'=> 'get']);
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
        return $this->json($friendsArray, 200, [], ['groups'=> 'get']);
    }

    #[Route('/api/get/types', name: 'api_get_types')]
    public function getTypes(): JsonResponse
    {
        $types = $this->typeRepo->findAll();
        return $this->json($types, 200, [], ['groups' => 'get']);
    }

    #[Route('/api/post', name: 'api_post', methods: 'POST')]
    public function post(Request $request, FriendRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $this->checkJsonFriendFilters($data, true);
        try {
            $friend = new Friend();
            $friend->setName($data['name']);
            $friend->setType($this->getTypeByName($data['type']));
            $friend->setValue(intval($data['value']));
            if (is_array($data['tags'])) {
                $friend->setTags($data['tags']);
            } else {
                $friend->setTags(array($data['tags']));
            }
            $friend->setAlive(true);
            $repo->add($friend, true);
        } catch (\Throwable $th) {
            throw $th;
        }

        // return the new friend
        return $this->json($this->getTypeNames([$friend]), 200, [], ['groups'=> 'get']);
    }

    #[Route('/api/call/monster', name: 'api_call_monster', methods: 'GET')]
    public function callMonster(Request $request, FriendRepository $repo, DocumentManager $dm): JsonResponse
    {
        $data = $request->getContent();
        
        if (!empty($data) && is_string($data)) {
            // Case: friend is given
            $target = $repo->find($data);
        } else {
            $friends = $repo->findBy(['alive' => true]);
            $val = rand(0, count($friends) - 1);
            $target = $friends[$val];
        }
        $godId = $this->typeRepo->findOneBy(['name' => 'GOD'])->getId();
        $unicornId = $this->typeRepo->findOneBy(['name' => 'UNICORN'])->getId();
        // case given friend is a god
        if ($target->getType() === $godId) {
            return $this->json(['response' => 'the monster tried to eat a God, the God doesnt like it'], 200);
        } elseif ($target->getType() === $unicornId) {
            return $this->json(['response' => 'the monster tried to eat a Unicorn, but unicorns are OP'], 200);
        } else {
            $target->setAlive(false);
            $dm->persist($target);
            $dm->flush();
            return $this->json(['response' => 'the monster ate '.$target->getName().' D:'], 200);
        }

        // case given friend is unicorn
        // case given friend is anything else
        // Case: finding a non god friend
            // $total = $dm->createQueryBuilder(Friend::class)
            // ->field('type')->notEqual($godId)
            // ->field('alive')->equals(true)
            // ->count()
            // ->getQuery()
            // ->execute();
            // $val = rand(0, $total - 1);

            // $targets = $dm->createQueryBuilder(Friend::class)
            // ->field('type')->notEqual($godId)
            // ->field('alive')->equals(true)
            // ->getQuery()
            // ->execute();
            // // dd($targets);
            // foreach ($targets as $i => $target) {
            //     if ($i === $val) {
            //         dd($target);
            //     }
            // }
    }

    #[Route('/api/get/dedFriends', name: 'api_get_dedfriends', methods: 'GET')]
    public function getEatenFriends(FriendRepository $repo): JsonResponse
    {
        // get all dead friends
        $friends = $repo->findBy(['alive' => false]);
        if (count($friends) === 0) {
            return $this->json(['response' => 'all poppy friends are alive'], 204, [], ['groups'=> 'get']);
        }
        return $this->json($this->getTypeNames($friends, true), 200, [], ['groups'=> 'get']);
    }

    #[Route('/api/update/value', name: 'api_update_value', methods: 'POST')]
    public function updateFriendshipValue(Request $request, FriendRepository $repo, DocumentManager $dm): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        // check data
        $this->checkJsonFriendFilters($data);
        if (array_key_exists('id', $data)) {
            $friend = $repo->find($data['id']);
            if ($friend instanceof Friend) {
                if (!array_key_exists('value', $data)) {
                    throw new BadRequestHttpException("You must pass a correct value parameter");
                }
                // check if god
                $godId = $this->typeRepo->findOneBy(['name' => 'GOD'])->getId();
                if ($friend->getType() !== $godId) {
                    // update friendship value of given friend
                    $friend->setValue(intval($data['value']));
                    $dm->persist($friend);
                    $dm->flush();
                    return $this->json($this->getTypeNames([$friend]), 200, [], ['groups'=> 'get']);
                } else {
                    return $this->json(['response' => 'Gods dont accept any change to their friendship'], 200);
                }
            } else {
                throw new BadRequestHttpException("Given Id doesn't match any Poppy friend");
            }
        }
    }

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
            $friend->setAlive(true);
            $repo->add($friend, $i === 9);
        }
        dd('added friends');
    }

    public function getTypeByName($name): string|null
    {
        $type = $this->typeRepo->findOneBy(['name' =>$name]);
        if ($type instanceof Type) {
            return $type->getId();
        }
        return null;
    }

    public function getTypeNames(array $friends, $getDead = false): array
    {
        $friendsArray = array();
        //get the type name
        foreach ($friends as $friend) {
            if (!$getDead && $friend->getAlive()) {
                $friend = $this->normalizer->normalize($friend, null, ['groups' => 'get']);
                $friend['type'] = $this->typeRepo->find($friend['type'])->getName();
                $friendsArray[] = $friend;
            } elseif ($getDead) {
                $friend = $this->normalizer->normalize($friend, null, ['groups' => 'get']);
                $friend['type'] = $this->typeRepo->find($friend['type'])->getName();
                $friendsArray[] = $friend;
            }
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
        $this->checkJsonFriendFilters($filter);
        if (array_key_exists('type', $filter)) {
            $filter['type'] = $this->getTypeByName($filter['type']);
        }
        // $filter['groups'] = 'get';
        // dd($filter);
        $friendsArray = $repo->findBy($filter);
        if (!empty($friendsArray)) {
            return $this->json($this->getTypeNames($friendsArray), 200, [], ['groups'=> 'get']);
        }

       // return no content success
       return $this->json([], 204);
    }

    // data is the list of filters used to do the query, they must match the friends
    public function checkJsonFriendFilters(mixed $data, bool $requireAll = false): void
    {
        if (!is_array($data)) {
            throw new BadRequestHttpException("the request parameter doesnt have the correct format");
        }
        if ($requireAll) {
            if (!array_key_exists('name', $data) || !array_key_exists('type', $data) || !array_key_exists('value', $data) || !array_key_exists('tags', $data)) {
                throw new BadRequestHttpException("the request is missing a parameter");
            }
        }
        if (array_key_exists('id', $data)) {
            if (!is_string($data['id'])) {
                throw new BadRequestHttpException("the id value is incorrect, must be a string");
            }
        }
        // check name
        if (array_key_exists('name', $data)) {
            if (!is_string($data['name'])) {
                throw new BadRequestHttpException("the name value is incorrect, must be a string");
            }
        }
        // check type
        if (array_key_exists('type', $data)) {
            if (is_null($this->getTypeByName($data['type']))) {
                throw new BadRequestHttpException("the type value is incorrect, must be a matching type (check /api/get/types for the correct types)");
            }
        }
        if (array_key_exists('value', $data)) {
            if (!is_int($data['value']) && !is_string($data['value'])) {
                throw new BadRequestHttpException("the friendship value is incorrect, must be an int or a string");
            }
        }
        if (array_key_exists('tags', $data)) {
            if (!is_array($data['tags']) && !is_string($data['tags'])) {
                throw new BadRequestHttpException("the tags value is incorrect, must be an array or a string");
            }
        }
        if (array_key_exists('alive', $data)) {
            if (!is_bool($data['alive'])) {
                throw new BadRequestHttpException("the isAlive value is incorrect, must be a boolean");
            }
        }
    }
}