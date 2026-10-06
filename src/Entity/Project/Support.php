<?php

namespace App\Entity\Project;

use App\Entity\Accounting\Accounting;
use App\Entity\Accounting\Transaction;
use App\Entity\EmbeddableMoney;
use App\Entity\Matchfunding\MatchCall;
use App\Mapping\Provider\EntityMapProvider;
use App\Repository\Project\SupportRepository;
use AutoMapper\Attribute\MapProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[MapProvider(EntityMapProvider::class)]
#[ORM\Table(name: 'project_support')]
#[ORM\UniqueConstraint(fields: ['project', 'origin'], name: 'project_origin_unique')]
#[ORM\Entity(repositoryClass: SupportRepository::class)]
class Support
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'supports')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Accounting $origin = null;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\JoinTable(name: 'project_support_trxs')]
    #[ORM\JoinColumn(name: 'support_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'transaction_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: Transaction::class, cascade: ['persist'])]
    private Collection $transactions;

    #[ORM\Embedded(class: EmbeddableMoney::class)]
    private ?EmbeddableMoney $money = null;

    #[ORM\Column]
    private bool $anonymous = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(nullable: true)]
    private ?bool $matchfunding = null;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrigin(): ?Accounting
    {
        return $this->origin;
    }

    public function setOrigin(?Accounting $origin): static
    {
        $this->origin = $origin;
        $this->matchfunding = $origin->getOwnerClass() === MatchCall::class;

        return $this;
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

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        $this->transactions->removeElement($transaction);

        return $this;
    }

    public function getMoney(): ?EmbeddableMoney
    {
        return $this->money;
    }

    public function setMoney(EmbeddableMoney $money): static
    {
        $this->money = $money;

        return $this;
    }

    public function isAnonymous(): bool
    {
        return $this->anonymous;
    }

    public function setAnonymous(bool $anonymous): static
    {
        $this->anonymous = $anonymous;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function isMatchfunding(): ?bool
    {
        return $this->matchfunding;
    }
}
