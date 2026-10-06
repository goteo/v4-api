<?php

namespace App\Entity\Project;

use App\Entity\DateCreatedTrait;
use App\Entity\DateUpdatedTrait;
use App\Entity\User\User;
use App\Mapping\Provider\EntityMapProvider;
use App\Repository\Project\CollaborationCandidacyRepository;
use AutoMapper\Attribute\MapProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[MapProvider(EntityMapProvider::class)]
#[ORM\Table(name: 'project_collaboration_candidacy')]
#[ORM\Entity(repositoryClass: CollaborationCandidacyRepository::class)]
class CollaborationCandidacy
{
    use DateCreatedTrait;
    use DateUpdatedTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'candidacies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Collaboration $collaboration = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(enumType: CollaborationCandidacyStatus::class)]
    private ?CollaborationCandidacyStatus $status = CollaborationCandidacyStatus::ToReview;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCollaboration(): ?Collaboration
    {
        return $this->collaboration;
    }

    public function setCollaboration(?Collaboration $collaboration): static
    {
        $this->collaboration = $collaboration;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): ?CollaborationCandidacyStatus
    {
        return $this->status;
    }

    public function setStatus(CollaborationCandidacyStatus $status): static
    {
        $this->status = $status;

        return $this;
    }
}
