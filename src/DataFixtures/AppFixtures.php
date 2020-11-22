<?php

namespace App\DataFixtures;

use App\Entity\FriendRequest;
use App\Entity\PhonebookEntry;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Faker\Provider\Base;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;
    /**
     * @var Generator
     */
    private $faker;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
        $this->faker = Factory::create();
        $this->faker->addProvider(new Base($this->faker));
    }

    public function load(ObjectManager $manager)
    {

        //Master user
        $user = new User();
        $user->setPassword($this->encoder->encodePassword($user, 'testPass'));
        $user->setEmail('johndoe@gmail.com');
        $phonebookEntry = new PhonebookEntry();
        $phonebookEntry->setFirstName('Jonathan');
        $phonebookEntry->setLastName('Doe');
        $phonebookEntry->setPhoneNumber('+37061111111');
        $phonebookEntry->setUser($user);
        $user_4 = NULL;
        $counter = 0;
        for ($i = 0; $i < 5; $i++) {
            $secondaryUsers = [];
            $secondaryPhonebookEntries = [];
            for ($j = 0; $j < 3; $j++) {
                // secondary users
                $id = $counter + 1;
                $secondaryUser = new User();
                $secondaryUser->setPassword($this->encoder->encodePassword($user, "testPass{$id}"));
                $secondaryUser->setEmail($this->faker->safeEmail);
                $secondaryPhonebookEntry = new PhonebookEntry();
                $secondaryPhonebookEntry->setFirstName($this->faker->firstName);
                $secondaryPhonebookEntry->setLastName($this->faker->lastName);
                $secondaryPhonebookEntry->setPhoneNumber($this->faker->numerify('+370########'));
                $secondaryPhonebookEntry->setUser($secondaryUser);
                $secondaryUsers[] = $secondaryUser;
                $secondaryPhonebookEntries[] = $secondaryPhonebookEntry;
                $counter++;
            }
            $secondaryUsers[1]->addMyFriend($secondaryUsers[2]); // add third user as friend of second user
            $friendRequest = new FriendRequest();
            if ($i % 2 == 0) {// inverse sender/receiver if mod 2 != 1 , between master user and first secondary user
                $friendRequest->setSender($user);
                $friendRequest->setReceiver($secondaryUsers[0]);
            } else {
                $friendRequest->setSender($secondaryUsers[0]);
                $friendRequest->setReceiver($user);
            }
            $manager->persist($friendRequest);
            $friendRequest = new FriendRequest();
            $friendRequest->setSender($secondaryUsers[0]);
            $friendRequest->setReceiver($secondaryUsers[2]);
            $manager->persist($friendRequest);

            if (!is_null($user_4)) {
                $user_4->addMyFriend($secondaryUsers[2]); // add second user as a second common friend of master user to third user as friend
                $user->addMyFriend($user_4); // add carried over user as a friend to master user
                $manager->persist($user_4);
            }
            $manager->persist($secondaryUsers[0]);
            $user_4 = $secondaryUsers[1]; // carry over second user to next iteration
            foreach ($secondaryPhonebookEntries as $secondaryPhonebookEntry) {
                $manager->persist($secondaryPhonebookEntry); // save phonebookEntries of secondary users
            }
        }
        $manager->persist($user);// save master user data
        $manager->persist($phonebookEntry);
        $manager->flush();
    }
}
