<?php

namespace App\Controller;

use App\Entity\PhonebookEntry;
use App\Entity\User;
use App\Service\ViolationTransformer;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    /**
     * @Route("/auth/register", name="register", methods={"POST"})
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @param ValidatorInterface $validator
     * @param ViolationTransformer $violationTransformer
     * @return Response
     */
    public function register(Request $request, UserPasswordEncoderInterface $encoder, ValidatorInterface $validator, ViolationTransformer $violationTransformer): Response
    {
        $password = $request->get('password');
        $email = $request->get('email');
        $firstName = $request->get('first_name');
        $lastName = $request->get('last_name');
        $phoneNumber = $request->get('phone_number');

        $user = new User();
        $user->setPassword($password);
        $user->setEmail($email);
        $phonebookEntry = new PhonebookEntry();
        $phonebookEntry->setFirstName($firstName);
        $phonebookEntry->setLastName($lastName);
        $phonebookEntry->setPhoneNumber($phoneNumber);
        $phonebookEntry->setUser($user);

        $userErrors = $validator->validate($user);
        $phonebookEntryErrors = $validator->validate($phonebookEntry);
        if (count($userErrors) != 0 || count($phonebookEntryErrors) != 0) {
            $userErrors = $violationTransformer->transformViolationsList($userErrors);
            $phonebookEntryErrors = $violationTransformer->transformViolationsList($phonebookEntryErrors);
            $errors = array_merge($userErrors, $phonebookEntryErrors);
            return new Response(json_encode([
                'errors' => $errors
            ]), 400);
        } else {
            $user->setPassword($encoder->encodePassword($user, $password));
            $em = $this->getDoctrine()->getManager();
            try {
                $em->persist($user);
                $em->persist($phonebookEntry);
                $em->flush();
            } catch (UniqueConstraintViolationException $e) {
                $data = [
                    'errors' => "User with the provided email already exists"
                ];
                return new Response(json_encode($data), 400);
            }

            return new Response('OK');
        }
    }


}
