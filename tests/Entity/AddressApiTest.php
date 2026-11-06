<?php

namespace App\Tests\Entity;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Address;
use App\Entity\User\User;
use App\Factory\User\UserFactory;
use App\Tests\Fixtures\TestUser;
use App\Tests\Traits\RequestingTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class AddressApiTest extends ApiTestCase
{
    use ResetDatabase;
    use Factories;
    use RequestingTestTrait;

    private const BASE_URI = '/v4/addresses';

    public function setUp(): void
    {
        self::bootKernel();
    }

    public function testDeleteOtherUserAddressIsForbidden(): void
    {
        $other = UserFactory::new(['handle' => 'other_user', 'email' => 'other@example.com'])->create()->_real();
        $address = $this->createAddress($other);

        $this->request('DELETE', $this->getUri($address), [
            'headers' => $this->withAuthHeader(TestUser::get()),
        ], Response::HTTP_FORBIDDEN);
    }

    public function testDeleteOwnAddress(): void
    {
        $user = TestUser::get();
        $address = $this->createAddress($user);

        $this->request('DELETE', $this->getUri($address), [
            'headers' => $this->withAuthHeader($user),
        ], Response::HTTP_NO_CONTENT);
    }

    public function testGetOtherUserAddressIsForbidden(): void
    {
        $other = UserFactory::new(['handle' => 'other_user', 'email' => 'other@example.com'])->create()->_real();
        $address = $this->createAddress($other);

        $this->request('GET', $this->getUri($address), [
            'headers' => $this->withAuthHeader(TestUser::get()),
        ], Response::HTTP_FORBIDDEN);
    }

    public function testSetDefaultUnsetsOtherDefaultsOfUser(): void
    {
        $user = TestUser::get();
        $userId = $user->getId();
        $auth = $this->withAuthHeader($user);
        $other = UserFactory::new(['handle' => 'other_user', 'email' => 'other@example.com'])->create()->_real();

        $previous = $this->createAddress($user, true);
        $address = $this->createAddress($user);
        $othersDefault = $this->createAddress($other, true);

        $response = $this->request('PATCH', $this->getUri($address), [
            'headers' => $auth,
            'json' => ['isDefault' => true],
        ], Response::HTTP_OK);

        $this->assertTrue($response->toArray()['isDefault']);

        $response = $this->request('GET', \sprintf('%s?user=/v4/users/%d', self::BASE_URI, $userId), [
            'headers' => $auth,
        ], Response::HTTP_OK);

        $defaults = \array_column($response->toArray()['member'], 'isDefault', 'id');
        $this->assertEquals([$previous->getId() => false, $address->getId() => true], $defaults);

        $this->getEntityManager()->clear();
        $this->assertTrue($this->getEntityManager()->find(Address::class, $othersDefault->getId())->isDefault());
    }

    private function createAddress(User $user, bool $isDefault = false): Address
    {
        $address = (new Address())
            ->setUser($this->getEntityManager()->getReference(User::class, $user->getId()))
            ->setFirstName('Test')
            ->setLastName('User')
            ->setLine1('Carrer Major 1')
            ->setCity('Barcelona')
            ->setPostCode('08001')
            ->setCountry('ES')
            ->setIsDefault($isDefault)
        ;

        $this->getEntityManager()->persist($address);
        $this->getEntityManager()->flush();

        return $address;
    }

    private function getUri(Address $address): string
    {
        return \sprintf('%s/%d', self::BASE_URI, $address->getId());
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }
}
