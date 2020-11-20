<?php

namespace App\Entity;

use App\Repository\PhonebookEntryRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\AccessorOrder;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\VirtualProperty;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=PhonebookEntryRepository::class)
 * @VirtualProperty(
 *     "userId",
 *     exp="object.getUserId()"
 *  )
 * @AccessorOrder("custom", custom = {"id", "userId", "firstName", "lastName", "phoneNumber"})
 */
class PhonebookEntry
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"list_phonebookEntries","list_phonebookInviteOptions", "show_personalEntry"})
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=User::class, inversedBy="phonebookEntry")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotBlank
     */
    private $user;


    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Regex(
     *     pattern = "/\b([A-ZĄąČčĘęĖėĮįŠšŲųŪūŽž][-,a-z. ']+[ ]*)+/",
     *     message = "First name must not contain numbers or special characters"
     *     )
     * @Groups({"list_phonebookEntries","list_phonebookInviteOptions", "show_personalEntry"})
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Regex(
     *     pattern = "/\b([A-ZĄąČčĘęĖėĮįŠšŲųŪūŽž][-,a-z. ']+[ ]*)+/",
     *     message = "Last name must not contain numbers or special characters"
     *     )
     * @Groups({"list_phonebookEntries","list_phonebookInviteOptions", "show_personalEntry"})
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Regex(
     *     pattern = "/(86|\+3706)\d{3}\d{4}/",
     *     message = "Invalid phone number format"
     *     )
     * @Groups({"list_phonebookEntries", "show_personalEntry"})
     */
    private $phoneNumber;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @VirtualProperty()
     * @Groups({"show_phonebookEntry"})
     *
     */
    public function getUserId(): ?int
    {
        return $this->getUser()->getId();
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }


}
