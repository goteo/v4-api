<?php

namespace App\Entity\Mail;

use App\Entity\DateCreatedTrait;
use App\Entity\DateUpdatedTrait;
use App\Entity\LocalizedTrait;
use App\Entity\User\User;
use App\Mapping\Provider\EntityMapProvider;
use App\Repository\Mail\BroadcastRepository;
use AutoMapper\Attribute\MapProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[MapProvider(EntityMapProvider::class)]
#[ORM\Entity(repositoryClass: BroadcastRepository::class)]
class Broadcast
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

    #[ORM\Column(enumType: BroadcastStatus::class)]
    private ?BroadcastStatus $status = null;

    #[ORM\ManyToOne(inversedBy: 'broadcasts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Audience $audience = null;

    /**
     * @var Collection<int, Dispatch>
     */
    #[ORM\OneToMany(targetEntity: Dispatch::class, mappedBy: 'broadcast')]
    private Collection $dispatchs;

    #[ORM\ManyToOne]
    private ?User $dispatchCursor = null;

    public function __construct()
    {
        $this->dispatchs = new ArrayCollection();
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

    public function getStatus(): ?BroadcastStatus
    {
        return $this->status;
    }

    public function setStatus(BroadcastStatus $status): static
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
     * @return Collection<int, Dispatch>
     */
    public function getDispatchs(): Collection
    {
        return $this->dispatchs;
    }

    public function addDispatch(Dispatch $dispatch): static
    {
        if (!$this->dispatchs->contains($dispatch)) {
            $this->dispatchs->add($dispatch);
            $dispatch->setBroadcast($this);
        }

        return $this;
    }

    public function removeDispatch(Dispatch $dispatch): static
    {
        if ($this->dispatchs->removeElement($dispatch)) {
            // set the owning side to null (unless already changed)
            if ($dispatch->getBroadcast() === $this) {
                $dispatch->setBroadcast(null);
            }
        }

        return $this;
    }

    public function getDispatchCursor(): ?User
    {
        return $this->dispatchCursor;
    }

    public function setDispatchCursor(?User $dispatchCursor): static
    {
        $this->dispatchCursor = $dispatchCursor;

        return $this;
    }
}
