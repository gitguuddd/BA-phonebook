<?php

namespace App\Entity;

use App\Repository\FriendRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\AccessorOrder;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=FriendRequestRepository::class)
 * @AccessorOrder("custom", custom = {"id", "sender", "receiver"})
 */
class FriendRequest
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"list_phonebookInviteOptions"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="sentRequests")
     * @ORM\JoinColumn(nullable=false, name="sender_id")
     * @Assert\NotBlank
     * @Groups({"list_phonebookRequestsReceived"})
     */
    private $sender;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="receivedRequests")
     * @ORM\JoinColumn(nullable=false,name="receiver_id")
     * @Assert\NotBlank
     * @Groups({"list_phonebookRequestsSent"})
     */
    private $receiver;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSenderId(): ?int
    {
        return $this->getSender()->getId();
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getReceiverId(): ?int
    {
        return $this->getReceiver()->getId();
    }

    public function getReceiver(): ?User
    {
        return $this->receiver;
    }

    public function setReceiver(?User $receiver): self
    {
        $this->receiver = $receiver;

        return $this;
    }
}
