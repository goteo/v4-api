<?php

namespace App\Entity\User;

use App\Mapping\Provider\OrganizationMapProvider;
use App\Repository\User\OrganizationRepository;
use AutoMapper\Attribute\MapProvider;
use Doctrine\ORM\Mapping as ORM;
use Kyzegs\DoctrineEncryptionBundle\Attribute\BlindIndex;
use Kyzegs\DoctrineEncryptionBundle\Attribute\Encrypted;

#[MapProvider(OrganizationMapProvider::class)]
#[ORM\Table(name: 'user_organization')]
#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
class Organization
{
    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'organization', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * ID for tax purposes. e.g: NIF (formerly CIF), Umsatzsteuer-Id, EID, etc.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Encrypted()]
    private ?string $taxId = null;

    #[ORM\Column(type: 'string', length: 64, unique: true, nullable: true)]
    #[BlindIndex(sourceField: 'taxId', normalizer: BlindIndex::NORMALIZE_UPPERCASE)]
    private ?string $taxIdLookup = null;

    /**
     * Organization legal name before government,
     * as it appears on legal documents issued by or for this organization.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $legalName = null;

    /**
     * The name under which the organization presents itself.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $businessName = null;

    public static function for(User $user): Organization
    {
        $org = new Organization();
        $org->setUser($user);

        return $org;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getTaxId(): ?string
    {
        return $this->taxId;
    }

    public function setTaxId(string $taxId): static
    {
        $this->taxId = $taxId;

        return $this;
    }

    public function getTaxIdLookup(): ?string
    {
        return $this->taxIdLookup;
    }

    public function setTaxIdLookup(?string $taxIdLookup): static
    {
        $this->taxIdLookup = $taxIdLookup;

        return $this;
    }

    public function getLegalName(): ?string
    {
        return $this->legalName;
    }

    public function setLegalName(string $legalName): static
    {
        $this->legalName = $legalName;

        return $this;
    }

    public function getBusinessName(): ?string
    {
        return $this->businessName;
    }

    public function setBusinessName(?string $businessName): static
    {
        $this->businessName = $businessName;

        return $this;
    }
}
