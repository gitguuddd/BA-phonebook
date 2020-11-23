<?php


namespace App\Service;


use App\Entity\User;
use App\Repository\FriendRequestRepository;
use App\Repository\UserRepository;

class NotInIdsFetcher
{
    public function fetchNotInIds(User $user, FriendRequestRepository $friendRequestRepository, UserRepository $userRepository): array
    {
        $friends = $userRepository->findFriends($user->getId());
        $friendIds = array_column($friends, 'u2_id');
        $sentRequests = $friendRequestRepository->findSentFriendRequests($user->getId(), true);
        $sentRequestsReceiverIds = array_column($sentRequests, 'u_id');
        $receivedRequests = $friendRequestRepository->findReceivedFriendRequests($user->getId(), true);
        $receivedRequestsSenderIds = array_column($receivedRequests, 'u_id');
        $notInIds = array_merge($friendIds, $sentRequestsReceiverIds, $receivedRequestsSenderIds);
        $notInIds[] = $user->getId();
        return $notInIds;
    }
}