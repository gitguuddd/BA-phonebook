<?php


namespace App\Service;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthorizedClientFetcher extends WebTestCase
{
    public function createAuthorizedClient($username = 'johndoe@gmail.com', $password = 'testPass')
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/login_check',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode(array(
                'username' => $username,
                'password' => $password,
            ))
        );

        $data = json_decode($client->getResponse()->getContent(), true);

        self::ensureKernelShutdown(); //FIXME: hacky solution to multiple clients
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $data['token']));

        return $client;
    }
}