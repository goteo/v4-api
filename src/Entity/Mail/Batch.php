<?php

namespace App\Entity\Mail;

use App\Entity\DateCreatedTrait;
use App\Entity\DateUpdatedTrait;
use App\Entity\LocalizedTrait;
use App\Mapping\Provider\EntityMapProvider;
use App\Repository\Mail\BatchRepository;
use AutoMapper\Attribute\MapProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[MapProvider(EntityMapProvider::class)]
#[ORM\Entity(repositoryClass: BatchRepository::class)]
class Batch
{
    use DateCreatedTrait;
    use DateUpdatedTrait;
    use LocalizedTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $sender = null;

    #[ORM\Column(length: 255)]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $body = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(enumType: BatchStatus::class)]
    private ?BatchStatus $status = null;

    #[ORM\ManyToOne(inversedBy: 'batches')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Audience $audience = null;

    /**
     * @var Collection<int, BatchRecipient>
     */
    #[ORM\OneToMany(targetEntity: BatchRecipient::class, mappedBy: 'batch')]
    private Collection $recipients;

    public function __construct()
    {
        $this->recipients = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSender(): ?string
    {
        return $this->sender;
    }

    public function setSender(string $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeImmutable $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getStatus(): ?BatchStatus
    {
        return $this->status;
    }

    public function setStatus(BatchStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAudience(): ?Audience
    {
        return $this->audience;
    }

    public function setAudience(?Audience $audience): static
    {
        $this->audience = $audience;

        return $this;
    }

    /**
     * @return Collection<int, BatchRecipient>
     */
    public function getRecipients(): Collection
    {
        return $this->recipients;
    }

    public function addRecipient(BatchRecipient $recipient): static
    {
        if (!$this->recipients->contains($recipient)) {
            $this->recipients->add($recipient);
            $recipient->setBatch($this);
        }

        return $this;
    }

    public function removeRecipient(BatchRecipient $recipient): static
    {
        if ($this->recipients->removeElement($recipient)) {
            // set the owning side to null (unless already changed)
            if ($recipient->getBatch() === $this) {
                $recipient->setBatch(null);
            }
        }

        return $this;
    }
}
