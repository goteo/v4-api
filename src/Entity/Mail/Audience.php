<?php

namespace App\Entity\Mail;

use App\Mapping\Provider\EntityMapProvider;
use App\Repository\Mail\AudienceRepository;
use AutoMapper\Attribute\MapProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[MapProvider(EntityMapProvider::class)]
#[ORM\Entity(repositoryClass: AudienceRepository::class)]
class Audience
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private array $criteria = [];

    /**
     * @var Collection<int, Broadcast>
     */
    #[ORM\OneToMany(targetEntity: Broadcast::class, mappedBy: 'audience')]
    private Collection $broadcasts;

    public function __construct()
    {
        $this->broadcasts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return AudienceCriteria[]
     */
    public function getCriteria(): array
    {
        return array_map(fn($v) => AudienceCriteria::fromArray($v), $this->criteria);
    }

    /**
     * @param AudienceCriteria[] $criteria
     */
    public function setCriteria(array $criteria): static
    {
        $this->criteria = array_map(fn($c) => $c->toArray(), $criteria);

        return $this;
    }

    /**
     * @return Collection<int, Broadcast>
     */
    public function getBroadcastes(): Collection
    {
        return $this->broadcasts;
    }

    public function addBroadcast(Broadcast $broadcast): static
    {
        if (!$this->broadcasts->contains($broadcast)) {
            $this->broadcasts->add($broadcast);
            $broadcast->setAudience($this);
        }

        return $this;
    }

    public function removeBroadcast(Broadcast $broadcast): static
    {
        if ($this->broadcasts->removeElement($broadcast)) {
            // set the owning side to null (unless already changed)
            if ($broadcast->getAudience() === $this) {
                $broadcast->setAudience(null);
            }
        }

        return $this;
    }
}
