<?php

namespace tests\FunctionalTests;

use App\Service\AuthorizedClientFetcher;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApplicationAvailabilityFunctionalTest extends WebTestCase
{


    public function testAuthIsSuccessful()
    {
        $client = self::createClient();
        $client->request('POST', '/auth/register',
            [
                'email' => 'Func@gmail.com',
                'password' => 'FuncPass123',
                'first_name' => 'Func',
                'last_name' => 'Tester',
                'phone_number' => '+37067111111'
            ]);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $client->request(
            'POST',
            '/api/login_check',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode(array(
                'username' => 'Func@gmail.com',
                'password' => 'FuncPass123',
            ))
        );
        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    /**
     * @dataProvider getUrlProvider
     * @param $getUrl
     */
    public function testGetAPIIsSuccessful($getUrl)
    {
        $authFetcher = new AuthorizedClientFetcher();
        $client = $authFetcher->createAuthorizedClient();

        $client->request('GET', $getUrl);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    /**
     * @dataProvider postUrlProvider
     * @param $postUrl
     */
    public function testPostAPIIsSuccessful($postUrl)
    {
        $authFetcher = new AuthorizedClientFetcher();
        $client = $authFetcher->createAuthorizedClient();

        $client->request('POST', $postUrl);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function getUrlProvider()
    {
        yield ['/api/phonebookEntries/'];
        yield ['/api/phonebookEntries/16'];
        yield ['/api/friendRequests/getSentRequests'];
        yield ['/api/friendRequests/getReceivedRequests'];
        yield ['/api/friendRequests/inviteOptions'];
    }

    public function postUrlProvider()
    {
        yield ['/api/friendRequests/send/14'];
        yield ['/api/friendRequests/decline/3'];
        yield ['/api/phonebookEntries/stopSharing/6'];
        yield ['/api/friendRequests/accept/3']; //other urls tested in controllers
    }

}