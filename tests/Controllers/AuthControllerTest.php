<?php


namespace App\Tests\Controllers;


use App\Service\AuthorizedClientFetcher;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    public function testRegistration()
    {
        $authFetcher = new AuthorizedClientFetcher();
        $client = $authFetcher->createAuthorizedClient();
        $client->request('POST', '/auth/register',
            [
                'email' => 'Func@gmail.com',
                'password' => 'FuncPass123',
                'first_name' => 'Func',
                'last_name' => 'Tester',
                'phone_number' => '+37067111111'
            ]);
        $this->assertTrue($client->getResponse()->isSuccessful());
        $client->request('POST', '/auth/register',
            [
                'email' => 'Func@gmail.com',
                'password' => 'FuncPass123',
                'first_name' => 'Func',
                'last_name' => 'Tester',
                'phone_number' => '+37067111112'
            ]);
        $this->assertFalse($client->getResponse()->isSuccessful()); //same email exists
        $client->request('POST', '/auth/register',
            [
                'email' => 'Funcas@gmail.com',
                'password' => 'FuncPass123',
                'first_name' => 'Func',
                'last_name' => 'Tester',
                'phone_number' => '+37067111111'
            ]);
        $this->assertFalse($client->getResponse()->isSuccessful()); //same phone number exists
        $client->request('POST', '/auth/register',
            [
                'email' => 'Func',
                'password' => 'FuncPass123',
                'first_name' => 'Func',
                'last_name' => 'Tester',
                'phone_number' => '+37067111114'
            ]);
        $this->assertFalse($client->getResponse()->isSuccessful()); //Bad email format
        $client->request('POST', '/auth/register',
            [
                'email' => 'Func@gmail.com',
                'password' => 'FuncPass123',
                'first_name' => 'Func',
                'last_name' => 'Tester',
                'phone_number' => '867111117'
            ]);
        $this->assertFalse($client->getResponse()->isSuccessful()); //Bad phone format
    }

    public function testLogin()
    {
        $authFetcher = new AuthorizedClientFetcher();
        $client = $authFetcher->createAuthorizedClient();
        $client->request(
            'POST',
            '/api/login_check',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode(array(
                'username' => 'johndoe@gmail.com',
                'password' => 'testPass',
            ))
        );
        $this->assertTrue($client->getResponse()->isSuccessful()); //user exists
        $client->request(
            'POST',
            '/api/login_check',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode(array(
                'username' => 'johndoeeeeeeee@gmail.com',
                'password' => 'testPass',
            ))
        );
        $this->assertFalse($client->getResponse()->isSuccessful()); //user does not  exist
    }

}