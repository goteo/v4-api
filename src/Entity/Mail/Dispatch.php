<?php

namespace App\Entity\Mail;

use App\Entity\User\User;
use App\Mapping\Provider\EntityMapProvider;
use App\Repository\Mail\DispatchRepository;
use AutoMapper\Attribute\MapProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[MapProvider(EntityMapProvider::class)]
#[ORM\Entity(repositoryClass: DispatchRepository::class)]
#[ORM\UniqueConstraint(fields: ['broadcast', 'user'])]
class Dispatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'dispatchs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Broadcast $broadcast = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $error = null;

    #[ORM\Column(enumType: DispatchStatus::class)]
    private ?DispatchStatus $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBroadcast(): ?Broadcast
    {
        return $this->broadcast;
    }

    public function setBroadcast(?Broadcast $broadcast): static
    {
        $this->broadcast = $broadcast;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): static
    {
        $this->error = $error;

        return $this;
    }

    public function getStatus(): ?DispatchStatus
    {
        return $this->status;
    }

    public function setStatus(DispatchStatus $status): static
    {
        $this->status = $status;

        return $this;
    }
}
