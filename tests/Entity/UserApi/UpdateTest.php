<?php

namespace App\Tests\Entity\UserApi;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User\UserType;
use App\Factory\CategoryFactory;
use App\Factory\User\UserFactory;
use App\Tests\Fixtures\TestUser;
use App\Tests\Traits\RequestingTestTrait;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class UpdateTest extends ApiTestCase
{
    use ResetDatabase;
    use Factories;
    use RequestingTestTrait;

    public function testUpdateProfileFields(): void
    {
        $user = TestUser::get();
        $category = CategoryFactory::createOne(['name' => 'test']);
        $uri = \sprintf('/v4/users/%d', $user->getId());

        $this->request('PATCH', $uri, [
            'headers' => $this->withAuthHeader($user),
            'json' => [
                'shareLocation' => true,
                'supportAnonymously' => true,
                'referralSource' => 'social_media',
                'categories' => [\sprintf('/v4/categories/%s', $category->getId())],
                'territory' => ['country' => 'ES', 'postalCode' => '07012'],
            ],
        ], Response::HTTP_OK);

        $this->assertJsonContains([
            'shareLocation' => true,
            'supportAnonymously' => true,
            'referralSource' => 'social_media',
            'territory' => ['country' => 'ES', 'postalCode' => '07012'],
        ]);
        $this->assertCount(1, $this->request('GET', $uri, [
            'headers' => $this->withAuthHeader($user),
        ])->toArray()['categories']);
    }

    public function testPrivateFieldsHiddenFromOthers(): void
    {
        $user = TestUser::get();
        $other = UserFactory::createOne(['handle' => 'other_user', 'email' => 'other@example.com']);

        $data = $this->request('GET', \sprintf('/v4/users/%d', $user->getId()), [
            'headers' => $this->withAuthHeader($other),
        ], Response::HTTP_OK)->toArray();

        $this->assertArrayNotHasKey('shareLocation', $data);
        $this->assertArrayNotHasKey('supportAnonymously', $data);
        $this->assertArrayNotHasKey('referralSource', $data);
    }

    public function testUpdatePersonFields(): void
    {
        $user = TestUser::get();

        $this->request('PATCH', \sprintf('/v4/users/%d/person', $user->getId()), [
            'headers' => $this->withAuthHeader($user),
            'json' => [
                'taxIdType' => 'nie',
                'birthYear' => 1990,
                'gender' => 'non_binary',
            ],
        ], Response::HTTP_OK);

        $this->assertJsonContains([
            'taxIdType' => 'nie',
            'birthYear' => 1990,
            'gender' => 'non_binary',
        ]);
    }

    public function testUpdatePersonInvalidBirthYear(): void
    {
        $user = TestUser::get();

        $this->request('PATCH', \sprintf('/v4/users/%d/person', $user->getId()), [
            'headers' => $this->withAuthHeader($user),
            'json' => ['birthYear' => 1800],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdateOrganizationFields(): void
    {
        $user = TestUser::get();

        $this->request('PATCH', \sprintf('/v4/users/%d', $user->getId()), [
            'headers' => $this->withAuthHeader($user),
            'json' => ['type' => UserType::Organization->value],
        ], Response::HTTP_OK);

        $this->request('PATCH', \sprintf('/v4/users/%d/organization', $user->getId()), [
            'headers' => $this->withAuthHeader($user),
            'json' => [
                'taxId' => 'G12345678',
                'legalName' => 'Test Association',
                'taxIdType' => 'nif',
                'legalForm' => 'association',
                'publicEntity' => true,
            ],
        ], Response::HTTP_OK);

        $this->assertJsonContains([
            'taxIdType' => 'nif',
            'legalForm' => 'association',
            'publicEntity' => true,
        ]);
    }
}
