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
     * @var Collection<int, Batch>
     */
    #[ORM\OneToMany(targetEntity: Batch::class, mappedBy: 'audience')]
    private Collection $batches;

    public function __construct()
    {
        $this->batches = new ArrayCollection();
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
        return $this->criteria;
    }

    /**
     * @param AudienceCriteria[] $criteria
     */
    public function setCriteria(array $criteria): static
    {
        $this->criteria = $criteria;

        return $this;
    }

    /**
     * @return Collection<int, Batch>
     */
    public function getBatches(): Collection
    {
        return $this->batches;
    }

    public function addBatch(Batch $batch): static
    {
        if (!$this->batches->contains($batch)) {
            $this->batches->add($batch);
            $batch->setAudience($this);
        }

        return $this;
    }

    public function removeBatch(Batch $batch): static
    {
        if ($this->batches->removeElement($batch)) {
            // set the owning side to null (unless already changed)
            if ($batch->getAudience() === $this) {
                $batch->setAudience(null);
            }
        }

        return $this;
    }
}
