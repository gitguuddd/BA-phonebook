<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"list_phonebookInviteOptions", "list_phonebookEntries"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\NotBlank
     * @Assert\Email(
     *     message = "The email '{{ value }}' is not a valid email."
     * )
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];
    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Assert\Regex(
     *     pattern = "/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z]).{8,}$/",
     *     message = "Password must be atleast 8 characters long, contain atleast one upper case letter, and atleast one number"
     *     )
     */
    private $password;

    /**
     * @ORM\OneToOne(targetEntity=PhonebookEntry::class, mappedBy="user", cascade={"persist", "remove"})
     * @Groups({"list_phonebookInviteOptions","list_phonebookEntries"})
     */
    private $phonebookEntry;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, mappedBy="myFriends", cascade={"persist"})
     */
    private $friendsWithMe;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="friendsWithMe",cascade={"persist"})
     * @ORM\JoinTable(name="friends",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="friend_user_id", referencedColumnName="id")}
     *      )
     * @Groups({ "list_phonebookEntries"})
     * @Serializer\MaxDepth(1)
     */
    private $myFriends;

    /**
     * @ORM\OneToMany(targetEntity=FriendRequest::class, mappedBy="sender", orphanRemoval=true)
     */
    private $sentRequests;

    /**
     * @ORM\OneToMany(targetEntity=FriendRequest::class, mappedBy="receiver", orphanRemoval=true)
     */
    private $receivedRequests;

    public function __construct()
    {
        $this->friendsWithMe = new ArrayCollection();
        $this->myFriends = new ArrayCollection();
        $this->sentRequests = new ArrayCollection();
        $this->receivedRequests = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string)$this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string)$this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getPhonebookEntry(): ?PhonebookEntry
    {
        return $this->phonebookEntry;
    }

    public function setPhonebookEntry(?PhonebookEntry $phonebookEntry): self
    {
        $this->phonebookEntry = $phonebookEntry;

        // set the owning side of the relation if necessary
        if ($phonebookEntry->getUser() !== $this) {
            $phonebookEntry->setUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getFriendsWithMe(): Collection
    {
        return $this->friendsWithMe;
    }

    public function addFriendsWithMe(self $friendsWithMe): self
    {
        if (!$this->friendsWithMe->contains($friendsWithMe)) {
            $this->friendsWithMe[] = $friendsWithMe;
        }

        return $this;
    }

    public function removeFriendsWithMe(self $friendsWithMe): self
    {
        $this->friendsWithMe->removeElement($friendsWithMe);

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getMyFriends(): Collection
    {
        return $this->myFriends;
    }

    public function addMyFriend(self $myFriend): self
    {
        if (!$this->myFriends->contains($myFriend)) {
            $this->myFriends[] = $myFriend;
            $myFriend->addFriendsWithMe($this);
        }

        return $this;
    }

    public function removeMyFriend(self $myFriend): self
    {
        if ($this->myFriends->removeElement($myFriend)) {
            $myFriend->removeFriendsWithMe($this);
        }

        return $this;
    }

    /**
     * @return Collection|FriendRequest[]
     */
    public function getSentRequests(): Collection
    {
        return $this->sentRequests;
    }

    public function addSentRequest(FriendRequest $sentRequest): self
    {
        if (!$this->sentRequests->contains($sentRequest)) {
            $this->sentRequests[] = $sentRequest;
            $sentRequest->setSender($this);
        }

        return $this;
    }

    public function removeSentRequest(FriendRequest $sentRequest): self
    {
        if ($this->sentRequests->removeElement($sentRequest)) {
            // set the owning side to null (unless already changed)
            if ($sentRequest->getSender() === $this) {
                $sentRequest->setSender(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|FriendRequest[]
     */
    public function getReceivedRequests(): Collection
    {
        return $this->receivedRequests;
    }

    public function addReceivedRequest(FriendRequest $receivedRequest): self
    {
        if (!$this->receivedRequests->contains($receivedRequest)) {
            $this->receivedRequests[] = $receivedRequest;
            $receivedRequest->setReceiver($this);
        }

        return $this;
    }

    public function removeReceivedRequest(FriendRequest $receivedRequest): self
    {
        if ($this->receivedRequests->removeElement($receivedRequest)) {
            // set the owning side to null (unless already changed)
            if ($receivedRequest->getReceiver() === $this) {
                $receivedRequest->setReceiver(null);
            }
        }

        return $this;
    }

}
