<?php

namespace App\Entity\User;

use App\Mapping\Provider\PersonMapProvider;
use App\Repository\User\PersonRepository;
use AutoMapper\Attribute\MapProvider;
use Doctrine\ORM\Mapping as ORM;
use Kyzegs\DoctrineEncryptionBundle\Attribute\BlindIndex;
use Kyzegs\DoctrineEncryptionBundle\Attribute\Encrypted;

/**
 * Person is the detailed data of an individual behind a User,
 * we keep their data separated from the User record to allow for other User types,
 * like the ones owned by an organization, to exist all at the same level.
 * \
 * Sensitive personal data is encrypted before being stored on the database.
 */
#[MapProvider(PersonMapProvider::class)]
#[ORM\Table(name: 'user_person')]
#[ORM\Entity(repositoryClass: PersonRepository::class)]
class Person
{
    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'person', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * Personal ID for tax purposes. e.g: NIF, Steuernummer, TIN ID, etc.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Encrypted()]
    private ?string $taxId = null;

    #[ORM\Column(type: 'string', length: 64, unique: true, nullable: true)]
    #[BlindIndex(sourceField: 'taxId', normalizer: BlindIndex::NORMALIZE_UPPERCASE)]
    private ?string $taxIdLookup = null;

    /**
     * First-part of the name of the person, usually the given name(s). e.g: John, Juan.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    /**
     * Last-part of the name of the person, usually the family name(s). e.g: Smith, Herrera García.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    public static function for(User $user): Person
    {
        $person = new Person();
        $person->setUser($user);

        return $person;
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

    public function setTaxId(?string $taxId): static
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

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }
}
