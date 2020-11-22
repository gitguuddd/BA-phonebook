<?php

namespace App\Controller;

use App\Entity\FriendRequest;
use App\Entity\User;
use App\Repository\FriendRequestRepository;
use App\Repository\UserRepository;
use App\Service\ViolationTransformer;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/friendRequests", name="friend_request_api")
 */
class FriendRequestController extends AbstractController
{
    /**
     * @var Serializer
     */
    private $serializer;
    /**
     * @var FriendRequestRepository
     */
    private $friendRequestRepository;

    public function __construct(SerializerInterface $serializer, FriendRequestRepository $friendRequestRepository)
    {
        $this->serializer = $serializer;
        $this->friendRequestRepository = $friendRequestRepository;
    }


    /**
     * @Route("/getSentRequests", name="_get_sent", methods={"GET"})
     */
    public function getSentRequests(): Response
    {
        $user = $this->getUser();
        $sentRequests = $this->friendRequestRepository->findSentFriendRequests($user->getId());
        $json = $this->serializer->serialize($sentRequests, 'json', SerializationContext::create()->setGroups(array('list_phonebookInviteOptions', 'list_phonebookRequestsSent')));
        return new Response($json);


    }

    /**
     * @Route("/getReceivedRequests", name="_get_received", methods={"GET"})
     */
    public function getReceivedRequests(): Response
    {
        $user = $this->getUser();
        $sentRequests = $this->friendRequestRepository->findReceivedFriendRequests($user->getId());
        $json = $this->serializer->serialize($sentRequests, 'json', SerializationContext::create()->setGroups(array('list_phonebookInviteOptions', 'list_phonebookRequestsReceived')));
        return new Response($json);
    }

    /**
     * @Route("/send/{id}", name="_send_request", methods={"POST"})
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     * @param ViolationTransformer $violationTransformer
     * @param User $receiver
     * @return Response
     */
    public function sendRequest(UserRepository $userRepository, EntityManagerInterface $em, ValidatorInterface $validator, ViolationTransformer $violationTransformer, User $receiver): Response
    {
        $user = $this->getUser();
        $friends = $userRepository->findFriends($user->getId());
        $friendIds = array_column($friends, 'u2_id');
        $sentRequests = $this->friendRequestRepository->findSentFriendRequests($user->getId(), true);
        $sentRequestsReceiverIds = array_column($sentRequests, 'u_id');
        $receivedRequests = $this->friendRequestRepository->findReceivedFriendRequests($user->getId(), true);
        $receivedRequestsSenderIds = array_column($receivedRequests, 'u_id');
        if ($receiver->getId() == $user->getId()) {
            $data = [
                'errors' => "Can't send a friend request to yourself"
            ];
            return new Response(json_encode($data), 400);
        } elseif (in_array($receiver->getId(), $friendIds)) {
            $data = [
                'errors' => "Can't send a friend request to an user, who is already a friend"
            ];
            return new Response(json_encode($data), 400);
        } elseif (in_array($receiver->getId(), $sentRequestsReceiverIds) || in_array($receiver->getId(), $receivedRequestsSenderIds)) {
            $data = [
                'errors' => "Can't send a friend request to an user with a pending friend request"
            ];
            return new Response(json_encode($data), 400);
        }
        $friendRequest = new FriendRequest();
        $friendRequest->setReceiver($receiver);
        $friendRequest->setSender($user);
        $friendRequestErrors = $validator->validate($friendRequest);
        if (count($friendRequestErrors) != 0) {
            $errors = $violationTransformer->transformViolationsList($friendRequestErrors);
            return new Response(json_encode([
                'errors' => $errors
            ]), 400);
        }
        $em->persist($friendRequest);
        $em->flush();


        return new Response('OK');

    }

    /**
     * @Route("/decline/{id}", name="_decline_request", methods={"POST"})
     * @param EntityManagerInterface $em
     * @param FriendRequest $friendRequest
     * @return Response
     */
    public function declineRequest(EntityManagerInterface $em, FriendRequest $friendRequest = null): Response
    {
        $user = $this->getUser();
        if (!$friendRequest) {
            $data = [
                'errors' => "Phonebook entry not found"
            ];
            return new Response(json_encode($data), 404);
        } elseif ($user->getId() != $friendRequest->getSenderId() && $user->getId() != $friendRequest->getReceiverId()) {
            $data = [
                'errors' => "No permissions to decline this request"
            ];
            return new Response(json_encode($data), 400);
        }

        $em->remove($friendRequest);
        $em->flush();

        return new Response('OK');

    }

    /**
     * @Route("/accept/{id}", name="_accept_request", methods={"POST"})
     * @param EntityManagerInterface $em
     * @param UserRepository $userRepository
     * @param FriendRequest $friendRequest
     * @return Response
     */
    public function acceptRequest(EntityManagerInterface $em, UserRepository $userRepository, FriendRequest $friendRequest = null): Response
    {
        $user = $this->getUser();
        if (!$friendRequest) {
            $data = [
                'errors' => "Phonebook entry not found"
            ];
            return new Response(json_encode($data), 404);
        } elseif ($user->getId() != $friendRequest->getReceiverId()) {
            $data = [
                'errors' => "No permissions to accept this request"
            ];
            return new Response(json_encode($data), 400);
        }
        $sender = $userRepository->findOneBy(['id' => $friendRequest->getSenderId()]);
        $user->addMyFriend($sender);

        $em->persist($user);
        $em->remove($friendRequest);
        $em->flush();

        return new Response('OK');

    }

    /**
     * @Route("/inviteOptions", name="_invite_options", methods={"GET"})
     * @param UserRepository $userRepository
     * @return Response
     */
    public function getInvitationOptions(UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        $friends = $userRepository->findFriends($user->getId());
        $friendIds = array_column($friends, 'u2_id');
        $sentRequests = $this->friendRequestRepository->findSentFriendRequests($user->getId(), true);
        $sentRequestsReceiverIds = array_column($sentRequests, 'u_id');
        $receivedRequests = $this->friendRequestRepository->findReceivedFriendRequests($user->getId(), true);
        $receivedRequestsSenderIds = array_column($receivedRequests, 'u_id');
        $notInIds = array_merge($friendIds, $sentRequestsReceiverIds, $receivedRequestsSenderIds);
        $notInIds[] = $user->getId();
        $inviteOptions = $this->friendRequestRepository->findPhonebookInviteOptions($notInIds);
        $json = $this->serializer->serialize($inviteOptions, 'json', SerializationContext::create()->setGroups(array('list_phonebookInviteOptions')));
        return new Response($json);

    }
}
