<?php

namespace App\Entity\Project;

use App\Entity\DateCreatedTrait;
use App\Entity\DateUpdatedTrait;
use App\Entity\LocalizedInterface;
use App\Entity\LocalizedTrait;
use App\Mapping\Provider\EntityMapProvider;
use App\Repository\Project\CollaborationRepository;
use AutoMapper\Attribute\MapProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[MapProvider(EntityMapProvider::class)]
#[ORM\Table(name: 'project_collaboration')]
#[ORM\Entity(repositoryClass: CollaborationRepository::class)]
class Collaboration implements LocalizedInterface
{
    use LocalizedTrait;
    use DateCreatedTrait;
    use DateUpdatedTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'collaborations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column(length: 255)]
    #[Gedmo\Translatable()]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Gedmo\Translatable()]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $isFulfilled = null;

    /**
     * @var Collection<int, CollaborationCandidacy>
     */
    #[ORM\OneToMany(targetEntity: CollaborationCandidacy::class, mappedBy: 'collaboration')]
    private Collection $candidacies;

    public function __construct()
    {
        $this->candidacies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isFulfilled(): ?bool
    {
        return $this->isFulfilled;
    }

    public function setFulfilled(bool $isFulfilled): static
    {
        $this->isFulfilled = $isFulfilled;

        return $this;
    }

    /**
     * @return Collection<int, CollaborationCandidacy>
     */
    public function getCandidacies(): Collection
    {
        return $this->candidacies;
    }

    public function addCandidacy(CollaborationCandidacy $candidacy): static
    {
        if (!$this->candidacies->contains($candidacy)) {
            $this->candidacies->add($candidacy);
            $candidacy->setCollaboration($this);
        }

        return $this;
    }

    public function removeCandidacy(CollaborationCandidacy $candidacy): static
    {
        if ($this->candidacies->removeElement($candidacy)) {
            // set the owning side to null (unless already changed)
            if ($candidacy->getCollaboration() === $this) {
                $candidacy->setCollaboration(null);
            }
        }

        return $this;
    }
}
