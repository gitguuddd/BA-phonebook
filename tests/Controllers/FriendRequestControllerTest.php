<?php


namespace App\Tests\Controllers;


use App\Service\AuthorizedClientFetcher;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FriendRequestControllerTest extends WebTestCase
{
    public function testGetSentRequests()
    {
        $authFetcher = new AuthorizedClientFetcher();
        $client = $authFetcher->createAuthorizedClient();
        $client->request('GET', '/api/friendRequests/getSentRequests');
        $this->assertCount(3, json_decode($client->getResponse()->getContent())); //Sent exactly 3 friend requests
    }

    public function testGetReceivedRequests()
    {
        $authFetcher = new AuthorizedClientFetcher();
        $client = $authFetcher->createAuthorizedClient();
        $client->request('GET', '/api/friendRequests/getReceivedRequests');
        $this->assertCount(2, json_decode($client->getResponse()->getContent())); //Received exactly 3 friend requests
    }

    public function testSendRequest()
    {
        $authFetcher = new AuthorizedClientFetcher();
        $client = $authFetcher->createAuthorizedClient();

        $client->request('POST', '/api/friendRequests/send/3');
        $this->assertFalse($client->getResponse()->isSuccessful()); //Can't send a request to yourself
        $client->request('POST', '/api/friendRequests/send/2');
        $this->assertFalse($client->getResponse()->isSuccessful()); //Can't send a request to a friend
        $client->request('POST', '/api/friendRequests/send/7');
        $this->assertFalse($client->getResponse()->isSuccessful()); //Can't send a request to a person with existing common friend request entry (received)
        $client->request('POST', '/api/friendRequests/send/10');
        $this->assertFalse($client->getResponse()->isSuccessful()); //Can't send a request to a person with existing common friend request entry (sent)
        $client->request('GET', '/api/friendRequests/getSentRequests');
        $this->assertCount(3, json_decode($client->getResponse()->getContent())); //Sent exactly 3 friend requests
        $client->request('POST', '/api/friendRequests/send/5');
        $this->assertTrue($client->getResponse()->isSuccessful()); //Request sent successfully
        $client->request('GET', '/api/friendRequests/getSentRequests');
        $this->assertCount(4, json_decode($client->getResponse()->getContent())); //Sent exactly 4 friend requests
    }

    public function testRequestDecline()
    {
        $authFetcher = new AuthorizedClientFetcher();
        $client = $authFetcher->createAuthorizedClient();

        $client->request('GET', '/api/friendRequests/getSentRequests');
        $this->assertCount(3, json_decode($client->getResponse()->getContent())); //Sent exactly 3 friend requests
        $client->request('GET', '/api/friendRequests/getReceivedRequests');
        $this->assertCount(2, json_decode($client->getResponse()->getContent())); //Received exactly 2 friend requests
        $client->request('POST', '/api/friendRequests/decline/1');
        $this->assertTrue($client->getResponse()->isSuccessful()); //Declined successfully
        $client->request('POST', '/api/friendRequests/decline/3');
        $this->assertTrue($client->getResponse()->isSuccessful()); //Declined successfully
        $client->request('GET', '/api/friendRequests/getSentRequests');
        $this->assertCount(2, json_decode($client->getResponse()->getContent())); //Sent exactly 2 friend requests
        $client->request('GET', '/api/friendRequests/getReceivedRequests');
        $this->assertCount(1, json_decode($client->getResponse()->getContent())); //Received exactly 1 friend requests
        $client->request('POST', '/api/friendRequests/decline/2');
        $this->assertFalse($client->getResponse()->isSuccessful()); //Can't decline a request that is not associated with this user
        $client->request('POST', '/api/friendRequests/decline/400');
        $this->assertFalse($client->getResponse()->isSuccessful()); //Friend request doesn't exist
    }

    public function testRequestAccept()
    {
        $authFetcher = new AuthorizedClientFetcher();
        $client = $authFetcher->createAuthorizedClient();

        $client->request('GET', '/api/friendRequests/getReceivedRequests');
        $this->assertCount(2, json_decode($client->getResponse()->getContent())); //Received exactly 2 friend requests
        $client->request('POST', '/api/friendRequests/accept/1');
        $this->assertFalse($client->getResponse()->isSuccessful()); //Only receiver can accept
        $client->request('POST', '/api/friendRequests/accept/10');
        $this->assertFalse($client->getResponse()->isSuccessful()); //Can't accept friend requests not addressed to user
        $client->request('POST', '/api/friendRequests/accept/400');
        $this->assertFalse($client->getResponse()->isSuccessful()); //Friend request doesn't exist
        $client->request('GET', '/api/phonebookEntries/');
        $entries = json_decode($client->getResponse()->getContent())[0];
        $this->assertCount(4, $entries->my_friends); //Contains exactly 3 friend associations
        $client->request('POST', '/api/friendRequests/accept/3');
        $this->assertTrue($client->getResponse()->isSuccessful()); //Friend request accepted successfully
        $client->request('GET', '/api/phonebookEntries/');
        $entries = json_decode($client->getResponse()->getContent())[0];
        $this->assertCount(5, $entries->my_friends); //Contains exactly 4 friend associations
        $client->request('GET', '/api/friendRequests/getReceivedRequests');
        $this->assertCount(1, json_decode($client->getResponse()->getContent())); //Received exactly 1 friend request
    }

    public function testGetInviteOptions()
    {
        $authFetcher = new AuthorizedClientFetcher();
        $client = $authFetcher->createAuthorizedClient();
        $client->request('GET', '/api/friendRequests/inviteOptions');
        $this->assertCount(6, json_decode($client->getResponse()->getContent())); //Received exactly 6 invite options
        $client->request('POST', '/api/friendRequests/send/4');
        $this->assertTrue($client->getResponse()->isSuccessful()); //Request sent successfully
        $client->request('GET', '/api/friendRequests/inviteOptions');
        $this->assertCount(5, json_decode($client->getResponse()->getContent())); //Received exactly 5 invite options
        $client->request('POST', '/api/phonebookEntries/stopSharing/2');
        $this->assertTrue($client->getResponse()->isSuccessful()); //Sharing stopped successfully
        $client->request('GET', '/api/friendRequests/inviteOptions');
        $this->assertCount(6, json_decode($client->getResponse()->getContent())); //Received exactly 6 invite options

    }
}