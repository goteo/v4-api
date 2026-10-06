<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;

#[ORM\Entity()]
class OAuth2Client extends AbstractClient
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 32)]
    protected string $identifier;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isConsented = false;

    /**
     * Returns true if the client can skip the consent page.
     */
    public function isConsented(): bool
    {
        return $this->isConsented;
    }

    public function setConsented(bool $isConsented): void
    {
        $this->isConsented = $isConsented;
    }
}
