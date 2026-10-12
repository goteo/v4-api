<?php

namespace App\Entity\Mail;

use App\Entity\User\User;
use App\Repository\Mail\BatchRecipientRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BatchRecipientRepository::class)]
class BatchRecipient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'recipients')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Batch $batch = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $error = null;

    #[ORM\Column(enumType: BatchRecipientStatus::class)]
    private ?BatchRecipientStatus $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBatch(): ?Batch
    {
        return $this->batch;
    }

    public function setBatch(?Batch $batch): static
    {
        $this->batch = $batch;

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

    public function getStatus(): ?BatchRecipientStatus
    {
        return $this->status;
    }

    public function setStatus(BatchRecipientStatus $status): static
    {
        $this->status = $status;

        return $this;
    }
}
