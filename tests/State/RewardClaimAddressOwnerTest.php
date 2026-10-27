<?php

namespace App\Tests\State;

use App\Entity\Address;
use App\Entity\User\User;
use App\State\Project\RewardClaimStateProcessor;
use PHPUnit\Framework\TestCase;

class RewardClaimAddressOwnerTest extends TestCase
{
    public function testNullAddressIsValid(): void
    {
        $this->assertTrue(RewardClaimStateProcessor::isAddressOwnedBy(null, new User()));
    }

    public function testAddressOfClaimOwnerIsValid(): void
    {
        $owner = new User();
        $address = (new Address())->setUser($owner);

        $this->assertTrue(RewardClaimStateProcessor::isAddressOwnedBy($address, $owner));
    }

    public function testAddressOfOtherUserIsNotValid(): void
    {
        $address = (new Address())->setUser(new User());

        $this->assertFalse(RewardClaimStateProcessor::isAddressOwnedBy($address, new User()));
    }
}
