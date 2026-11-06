<?php

namespace App\OAuth2\Client;

interface TokenOwnerInterface
{
    public function getEmail(): string;

    public function getFirstName(): string;

    public function getLastName(): string;
}
