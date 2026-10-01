<?php

namespace App\Entity\Project;

use App\Entity\Address;
use App\Entity\Gateway\Charge;
use App\Entity\User\User;
use App\Entity\UserOwnedInterface;
use App\Entity\UserOwnedTrait;
use App\Mapping\Provider\EntityMapProvider;
use App\Repository\Project\RewardClaimRepository;
use AutoMapper\Attribute\MapProvider;
use Doctrine\ORM\Mapping as ORM;

#[MapProvider(EntityMapProvider::class)]
#[ORM\Table(name: 'project_reward_claim')]
#[ORM\Entity(repositoryClass: RewardClaimRepository::class)]
class RewardClaim implements UserOwnedInterface
{
    use UserOwnedTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\ManyToOne(inversedBy: 'claims', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Reward $reward = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Charge $charge = null;

    #[ORM\Column(enumType: RewardClaimStatus::class)]
    private ?RewardClaimStatus $status = RewardClaimStatus::InPending;

    #[ORM\ManyToOne]
    private ?Address $address = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReward(): ?Reward
    {
        return $this->reward;
    }

    public function setReward(?Reward $reward): static
    {
        $this->reward = $reward;

        return $this;
    }

    public function getCharge(): ?Charge
    {
        return $this->charge;
    }

    public function setCharge(?Charge $charge): static
    {
        $this->charge = $charge;

        return $this;
    }

    public function getStatus(): ?RewardClaimStatus
    {
        return $this->status;
    }

    public function setStatus(RewardClaimStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): static
    {
        $this->address = $address;

        return $this;
    }
}
