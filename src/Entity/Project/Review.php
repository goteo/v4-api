<?php

namespace App\Entity\Project;

use App\Entity\DateCreatedTrait;
use App\Entity\DateUpdatedTrait;
use App\Entity\User\User;
use App\Repository\Project\ReviewRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
class Review
{
    use DateCreatedTrait;
    use DateUpdatedTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\ManyToOne]
    private ?User $reviewer = null;

    #[ORM\Column(enumType: ReviewType::class)]
    private ?ReviewType $type = null;

    /**
     * @var Collection<int, ReviewArea>
     */
    #[ORM\OneToMany(targetEntity: ReviewArea::class, mappedBy: 'review', cascade: ['persist'])]
    private Collection $areas;

    public function __construct()
    {
        $this->areas = new ArrayCollection();
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

    public function getReviewer(): ?User
    {
        return $this->reviewer;
    }

    public function setReviewer(?User $reviewer): static
    {
        $this->reviewer = $reviewer;

        return $this;
    }

    public function getType(): ?ReviewType
    {
        return $this->type;
    }

    public function setType(ReviewType $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, ReviewArea>
     */
    public function getAreas(): Collection
    {
        return $this->areas;
    }

    public function addArea(ReviewArea $area): static
    {
        if (!$this->areas->contains($area)) {
            $this->areas->add($area);
            $area->setReview($this);
        }

        return $this;
    }

    public function removeArea(ReviewArea $area): static
    {
        if ($this->areas->removeElement($area)) {
            // set the owning side to null (unless already changed)
            if ($area->getReview() === $this) {
                $area->setReview(null);
            }
        }

        return $this;
    }
}
