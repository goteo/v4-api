<?php

namespace App\Entity\Project;

use App\Entity\DateCreatedTrait;
use App\Entity\DateUpdatedTrait;
use App\Mapping\Provider\EntityMapProvider;
use App\Repository\Project\ReviewAreaRepository;
use AutoMapper\Attribute\MapProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[MapProvider(EntityMapProvider::class)]
#[ORM\Entity(repositoryClass: ReviewAreaRepository::class)]
class ReviewArea
{
    use DateCreatedTrait;
    use DateUpdatedTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'areas')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Review $review = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summary = null;

    #[ORM\Column(enumType: ReviewAreaRisk::class)]
    private ReviewAreaRisk $risk = ReviewAreaRisk::High;

    /**
     * @var Collection<int, ReviewComment>
     */
    #[ORM\OneToMany(targetEntity: ReviewComment::class, mappedBy: 'area')]
    private Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReview(): ?Review
    {
        return $this->review;
    }

    public function setReview(?Review $review): static
    {
        $this->review = $review;

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

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): static
    {
        $this->summary = $summary;

        return $this;
    }

    public function getRisk(): ?ReviewAreaRisk
    {
        return $this->risk;
    }

    public function setRisk(?ReviewAreaRisk $risk): static
    {
        $this->risk = $risk;

        return $this;
    }

    /**
     * @return Collection<int, ReviewComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(ReviewComment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setArea($this);
        }

        return $this;
    }

    public function removeComment(ReviewComment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getArea() === $this) {
                $comment->setArea(null);
            }
        }

        return $this;
    }
}
