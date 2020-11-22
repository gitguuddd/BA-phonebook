<?php


namespace App\Tests\Controllers;


use App\Service\AuthorizedClientFetcher;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PhonebookEntryControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $authFetcher = new AuthorizedClientFetcher();
        $client = $authFetcher->createAuthorizedClient();
        $client->request('GET', '/api/phonebookEntries/');
        $entries = json_decode($client->getResponse()->getContent())[0];

        $this->assertCount(4, $entries->my_friends); //Contains exactly 4 friend associations
    }

    public function testCreate()
    {
        $authFetcher = new AuthorizedClientFetcher();
        $client = $authFetcher->createAuthorizedClient();
        $data = [
            'first_name' => 'Testname',
            'last_name' => 'Lastname',
            'phone_number' => '+37067639888'
        ];
        $incompleteData =
            [
                'last_name' => 'Lastname',
                'phone_number' => '+37067639888'
            ];

        $invalidData = [
            'first_name' => 'Testname',
            'last_name' => 'Lastname',
            'phone_number' => '11111111'
        ];
        $client->request('POST', '/api/phonebookEntries/', $data);

        $this->assertFalse($client->getResponse()->isSuccessful()); //Personal entry already exists

        $client->request('DELETE', '/api/phonebookEntries/16');

        $this->assertTrue($client->getResponse()->isSuccessful()); //Deleted successfully

        $client->request('POST', '/api/phonebookEntries/', $incompleteData);

        $this->assertFalse($client->getResponse()->isSuccessful()); //Missing field

        $client->request('POST', '/api/phonebookEntries/', $invalidData);

        $this->assertFalse($client->getResponse()->isSuccessful()); //Bad phoneNumber format

        $client->request('POST', '/api/phonebookEntries/', $data);

        $this->assertTrue($client->getResponse()->isSuccessful()); //Created successfully
    }

    public function testShow()
    {
        $authFetcher = new AuthorizedClientFetcher();
        $client = $authFetcher->createAuthorizedClient();
        $client->request('GET', '/api/phonebookEntries/16');
        $this->assertTrue($client->getResponse()->isSuccessful()); //Can view own entry
        $client->request('GET', '/api/phonebookEntries/2');
        $this->assertTrue($client->getResponse()->isSuccessful()); //Can view friend entry
        $client->request('GET', '/api/phonebookEntries/999');
        $this->assertFalse($client->getResponse()->isSuccessful()); //Entry doesnt exist
        $client->request('GET', '/api/phonebookEntries/4');
        $this->assertFalse($client->getResponse()->isSuccessful()); //Entry belongs to a non friend user
    }

    public function testUpdate()
    {
        $authFetcher = new AuthorizedClientFetcher();
        $client = $authFetcher->createAuthorizedClient();
        $data = [
            'first_name' => 'Testname',
            'last_name' => 'Lastname',
            'phone_number' => '+37067639888'
        ];
        $incompleteData =
            [
                'last_name' => 'Lastname',
                'phone_number' => '+37067639888'
            ];

        $invalidData = [
            'first_name' => 'Testname',
            'last_name' => 'Lastname',
            'phone_number' => '11111111'
        ];
        $client->request('PUT', '/api/phonebookEntries/16', $data);

        $this->assertEquals('Testname', json_decode($client->getResponse()->getContent())->first_name); //Update successful


        $client->request('POST', '/api/phonebookEntries/', $incompleteData);

        $this->assertFalse($client->getResponse()->isSuccessful()); //Missing field

        $client->request('POST', '/api/phonebookEntries/', $invalidData);

        $this->assertFalse($client->getResponse()->isSuccessful()); //Bad phoneNumber format

    }

    public function testDelete()
    {
        $authFetcher = new AuthorizedClientFetcher();
        $client = $authFetcher->createAuthorizedClient();
        $client->request('GET', '/api/phonebookEntries/16');
        $this->assertTrue($client->getResponse()->isSuccessful()); //Entry exists
        $client->request('DELETE', '/api/phonebookEntries/16');
        $this->assertTrue($client->getResponse()->isSuccessful()); //Deleted successfully
        $client->request('GET', '/api/phonebookEntries/16');
        $this->assertFalse($client->getResponse()->isSuccessful()); //Entry not found
        $client->request('DELETE', '/api/phonebookEntries/14');
        $this->assertFalse($client->getResponse()->isSuccessful()); //Deleted successfully
    }

    public function testStopSharing()
    {
        $authFetcher = new AuthorizedClientFetcher();
        $client = $authFetcher->createAuthorizedClient();
        $client->request('GET', '/api/phonebookEntries/');
        $entries = json_decode($client->getResponse()->getContent())[0];
        $this->assertCount(4, $entries->my_friends); //Contains exactly 4 friend associations
        $client->request('POST', '/api/phonebookEntries/stopSharing/2');
        $this->assertTrue($client->getResponse()->isSuccessful()); //Sharing stopped successfully
        $client->request('GET', '/api/phonebookEntries/');
        $entries = json_decode($client->getResponse()->getContent())[0];
        $this->assertCount(3, $entries->my_friends); //Contains exactly 3 friend associations
    }
}