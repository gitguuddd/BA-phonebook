<?php

namespace App\Controller;

use App\Entity\PhonebookEntry;
use App\Entity\User;
use App\Repository\PhonebookEntryRepository;
use App\Repository\UserRepository;
use App\Service\ViolationTransformer;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use JMS\Serializer\SerializationContext;

/**
 * @Route("/api/phonebookEntries", name="phonebook_entry_api")
 */
class PhonebookEntryController extends AbstractController
{

    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var EntityManagerInterface
     */
    private $em;


    public function __construct(SerializerInterface $serializer, EntityManagerInterface $em)
    {
        $this->serializer = $serializer;
        $this->em = $em;
    }

    /**
     * @param PhonebookEntryRepository $phonebookEntryRepository
     * @return Response
     * @Route("/", name="_index", methods={"GET"})
     */
    public function index(PhonebookEntryRepository $phonebookEntryRepository): Response
    {
        $user = $this->getUser();
        $phonebookEntries = $phonebookEntryRepository->findFriendPhonebookEntries($user->getId());
        $json = $this->serializer->serialize($phonebookEntries, 'json', SerializationContext::create()->setGroups(array('list_phonebookEntries', 'show_phonebookEntry'))->enableMaxDepthChecks());
        return new Response($json);
    }


    /**
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param ViolationTransformer $violationTransformer
     * @return Response
     * @Route("/", name="_create", methods={"POST"})
     */
    public function create(Request $request, ValidatorInterface $validator, ViolationTransformer $violationTransformer): Response
    {
        $user = $this->getUser();
        $existingPhonebookEntry = $user->getPhonebookEntry();
        if (!is_null($existingPhonebookEntry)) {
            $data = [
                'errors' => 'Personal phonebook entry already exists'
            ];
            return new Response(json_encode($data), 400);
        }
        $firstName = $request->get('first_name');
        $lastName = $request->get('last_name');
        $phoneNumber = $request->get('phone_number');

        $phonebookEntry = new PhonebookEntry();
        $phonebookEntry->setFirstName($firstName);
        $phonebookEntry->setLastName($lastName);
        $phonebookEntry->setPhoneNumber($phoneNumber);
        $phonebookEntry->setUser($user);
        $phonebookEntryErrors = $validator->validate($phonebookEntry);
        if (count($phonebookEntryErrors) != 0) {
            $errors = $violationTransformer->transformViolationsList($phonebookEntryErrors);
            return new Response(json_encode([
                'errors' => $errors
            ]), 400);
        }

        $this->em->persist($phonebookEntry);
        $this->em->flush();

        $json = $this->serializer->serialize($phonebookEntry, 'json', SerializationContext::create()->setGroups(array('list_phonebookEntries')));

        return new Response($json);


    }

    /**
     * @param PhonebookEntry $phonebookEntry
     * @param UserRepository $userRepository
     * @return Response
     * @Route("/{id}", name="_show", methods={"GET"})
     */
    public function show(UserRepository $userRepository, PhonebookEntry $phonebookEntry = null): Response
    {
        $user = $this->getUser();
        if (!$phonebookEntry) {
            $data = [
                'errors' => "Phonebook entry not found"
            ];
            return new Response(json_encode($data), 404);
        }
        $friends = $userRepository->findFriends($user->getId());
        $friendIds = array_column($friends, 'u2_id');
        if ($user->getId() != $phonebookEntry->getUserId() && !in_array($phonebookEntry->getUserId(), $friendIds)) {
            $data = [
                'errors' => "Cannot view phonebook entries of users that are not friends"
            ];
            return new Response(json_encode($data), 403);
        } else {
            $json = $this->serializer->serialize($phonebookEntry, 'json', SerializationContext::create()->setGroups(array('list_phonebookEntries', 'show_phonebookEntry')));
            return new Response($json);
        }
    }

    /**
     * @Route("/{id}", name="_put", methods={"PUT"})
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param ViolationTransformer $violationTransformer
     * @param PhonebookEntry $phonebookEntry
     * @return Response
     */
    public function update(Request $request, ValidatorInterface $validator, ViolationTransformer $violationTransformer, PhonebookEntry $phonebookEntry = null): Response
    {
        $user = $this->getUser();
        if (!$phonebookEntry) {
            $data = [
                'errors' => "Phonebook entry not found"
            ];
            return new Response(json_encode(json_encode($data), 404));
        } elseif ($user->getId() != $phonebookEntry->getUser()->getId()) {
            $data = [
                'errors' => "Cannot update phonebook entry of another user"
            ];
            return new Response(json_encode($data), 403);
        }
        $firstName = $request->get('first_name');
        $lastName = $request->get('last_name');
        $phoneNumber = $request->get('phone_number');

        $phonebookEntry->setFirstName($firstName);
        $phonebookEntry->setLastName($lastName);
        $phonebookEntry->setPhoneNumber($phoneNumber);
        $phonebookEntryErrors = $validator->validate($phonebookEntry);
        if (count($phonebookEntryErrors) != 0) {
            $errors = $violationTransformer->transformViolationsList($phonebookEntryErrors);
            return new Response(json_encode([
                'errors' => $errors
            ]), 400);
        }

        $this->em->persist($phonebookEntry);
        $this->em->flush();
        $json = $this->serializer->serialize($phonebookEntry, 'json', SerializationContext::create()->setGroups(array('list_phonebookEntries')));


        return new Response($json);

    }

    /**
     * @Route("/{id}", name="_delete", methods={"DELETE"})
     * @param PhonebookEntry $phonebookEntry
     * @return Response
     */
    public function delete(PhonebookEntry $phonebookEntry = null): Response
    {
        $user = $this->getUser();
        if (!$phonebookEntry) {
            $data = [
                'errors' => "Phonebook entry not found"
            ];
            return new Response(json_encode($data), 404);
        } elseif ($user->getId() != $phonebookEntry->getUser()->getId()) {
            $data = [
                'errors' => "Cannot delete phonebook entry of another user"
            ];
            return new Response(json_encode($data), 403);
        }

        $this->em->remove($phonebookEntry);
        $this->em->flush();

        return new Response('OK');
    }

    /**
     * @Route("/stopSharing/{id}", name="_stop_sharing", methods={"POST"})
     * @param UserRepository $userRepository
     * @param User $friend
     * @return Response
     */
    public function stopSharing(UserRepository $userRepository, User $friend = null): Response
    {
        $user = $this->getUser();
        if (!$friend) {
            $data = [
                'errors' => "User not found"
            ];
            return new Response(json_encode($data), 404);
        }
        $friends = $userRepository->findFriends($user->getId());
        $friendIds = array_column($friends, 'u2_id');
        if (!in_array($friend->getId(), $friendIds)) {
            $data = [
                'errors' => "Can't unfriend an user who is not your friend"
            ];
            return new Response(json_encode($data), 400);
        }
        $user->removeMyFriend($friend);
        $this->em->persist($user);
        $this->em->flush();

        return new Response('OK');

    }

    /**
     * @Route("/getPersonal", name="_get_personal", methods={"GET"}, priority=1)
     * @param UserRepository $userRepository
     * @param PhonebookEntryRepository $phonebookEntryRepository
     * @return Response
     */
    public function getPersonalEntry(PhonebookEntryRepository $phonebookEntryRepository): Response
    {
        $user = $this->getUser();
        $phonebookEntry = $phonebookEntryRepository->findOneBy([
            'user' => $user
        ]);
        if (is_null($phonebookEntry)) {
            $data = [
                'errors' => "Phonebook entry not found"
            ];
            return new Response(json_encode($data), 404);
        }
        $json = $this->serializer->serialize($phonebookEntry, 'json', SerializationContext::create()->setGroups(array('list_phonebookEntries', 'show_phonebookEntry')));
        return new Response($json);

    }


}
