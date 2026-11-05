<?php

namespace App\Entity\Matchfunding;

use App\Entity\Accounting\Accounting;
use App\Entity\Accounting\AccountingOwnerInterface;
use App\Entity\Territory;
use App\Entity\User\User;
use App\Mapping\Provider\EntityMapProvider;
use App\Repository\Matchfunding\MatchCallRepository;
use AutoMapper\Attribute\MapProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[MapProvider(EntityMapProvider::class)]
#[ORM\Entity(repositoryClass: MatchCallRepository::class)]
class MatchCall implements AccountingOwnerInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'matchCall', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Accounting $accounting = null;

    /**
     * @var Collection<int, MatchStrategy>
     */
    #[ORM\OneToMany(targetEntity: MatchStrategy::class, mappedBy: 'call', cascade: ['persist'])]
    private Collection $strategies;

    /**
     * @var Collection<int, MatchCallSubmission>
     */
    #[ORM\OneToMany(mappedBy: 'call', targetEntity: MatchCallSubmission::class)]
    private Collection $submissions;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    private Collection $managers;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $image = null;

    #[ORM\Embedded(class: Territory::class)]
    private ?Territory $territory = null;

    #[ORM\Column(enumType: MatchCallStatus::class)]
    private ?MatchCallStatus $status = MatchCallStatus::InEditing;

    public function __construct()
    {
        $this->accounting = Accounting::of($this);
        $this->managers = new ArrayCollection();
        $this->submissions = new ArrayCollection();
        $this->strategies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccounting(): ?Accounting
    {
        return $this->accounting;
    }

    public function setAccounting(Accounting $accounting): static
    {
        $this->accounting = $accounting;

        return $this;
    }

    /**
     * @return Collection<int, MatchStrategy>
     */
    public function getStrategies(): Collection
    {
        return $this->strategies;
    }

    /**
     * @param Collection<int, MatchStrategy> $strategies
     */
    public function setStrategies(Collection $strategies): static
    {
        $this->strategies = $strategies;

        return $this;
    }

    public function addStrategy(MatchStrategy $strategy): static
    {
        if (!$this->strategies->contains($strategy)) {
            $this->strategies = $this->strategies->map(function (MatchStrategy $s) use ($strategy) {
                if ($s->getRanking() >= $strategy->getRanking()) {
                    $s->setRanking($s->getRanking() + 1);
                }

                return $s;
            });

            $this->strategies->add($strategy);

            $strategy->setCall($this);
        }

        return $this;
    }

    public function removeStrategy(MatchStrategy $strategy): static
    {
        if ($this->strategies->removeElement($strategy)) {
            $this->strategies = $this->strategies->map(function (MatchStrategy $s) use ($strategy) {
                if ($s->getRanking() > $strategy->getRanking()) {
                    $s->setRanking($s->getRanking() - 1);
                }

                return $s;
            });
        }

        return $this;
    }

    /**
     * @return Collection<int, MatchCallSubmission>
     */
    public function getSubmissions(): Collection
    {
        return $this->submissions;
    }

    public function addSubmission(MatchCallSubmission $submission): static
    {
        if (!$this->submissions->contains($submission)) {
            $this->submissions->add($submission);
            $submission->setCall($this);
        }

        return $this;
    }

    public function removeSubmission(MatchCallSubmission $submission): static
    {
        if ($this->submissions->removeElement($submission)) {
            // set the owning side to null (unless already changed)
            if ($submission->getCall() === $this) {
                $submission->setCall(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getManagers(): Collection
    {
        return $this->managers;
    }

    public function addManager(User $manager): static
    {
        if (!$this->managers->contains($manager)) {
            $this->managers->add($manager);
        }

        return $this;
    }

    public function removeManager(User $manager): static
    {
        $this->managers->removeElement($manager);

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

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(Territory $territory): static
    {
        $this->territory = $territory;

        return $this;
    }

    public function getStatus(): ?MatchCallStatus
    {
        return $this->status;
    }

    public function setStatus(MatchCallStatus $status): static
    {
        $this->status = $status;

        return $this;
    }
}
