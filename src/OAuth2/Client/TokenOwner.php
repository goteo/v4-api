<?php

namespace App\OAuth2\Client;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class TokenOwner implements TokenOwnerInterface, ResourceOwnerInterface
{
    public function __construct(
        private string $email,
        private string $firstName,
        private string $lastName,
    ) {}

    public function getId(): mixed
    {
        return $this->email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
        ];
    }
}
