<?php

namespace App\Tests\Entity\UserApi;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Theme;
use App\Entity\User\UserType;
use App\Factory\CategoryFactory;
use App\Factory\User\UserFactory;
use App\Tests\Fixtures\TestUser;
use App\Tests\Traits\RequestingTestTrait;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

use function Zenstruck\Foundry\Persistence\persist;

class UpdateTest extends ApiTestCase
{
    use ResetDatabase;
    use Factories;
    use RequestingTestTrait;

    public function testUpdateProfileFields(): void
    {
        $user = TestUser::get();
        $category = CategoryFactory::createOne(['name' => 'test']);
        $theme = persist(Theme::class, ['name' => 'test']);
        $uri = \sprintf('/v4/users/%d', $user->getId());

        $this->request('PATCH', $uri, [
            'headers' => $this->withAuthHeader($user),
            'json' => [
                'shareLocation' => true,
                'isAnonymous' => true,
                'referralSource' => 'social_media',
                'preferredCategories' => [\sprintf('/v4/categories/%s', $category->getId())],
                'preferredThemes' => [\sprintf('/v4/themes/%s', $theme->getId())],
            ],
        ], Response::HTTP_OK);

        $this->assertJsonContains([
            'shareLocation' => true,
            'isAnonymous' => true,
            'referralSource' => 'social_media',
        ]);

        $data = $this->request('GET', $uri, ['headers' => $this->withAuthHeader($user)])->toArray();
        $this->assertCount(1, $data['preferredCategories']);
        $this->assertCount(1, $data['preferredThemes']);
    }

    public function testPrivateFieldsHiddenFromOthers(): void
    {
        $user = TestUser::get();
        $other = UserFactory::createOne(['handle' => 'other_user', 'email' => 'other@example.com']);

        $data = $this->request('GET', \sprintf('/v4/users/%d', $user->getId()), [
            'headers' => $this->withAuthHeader($other),
        ], Response::HTTP_OK)->toArray();

        $this->assertArrayNotHasKey('shareLocation', $data);
        $this->assertArrayNotHasKey('isAnonymous', $data);
        $this->assertArrayNotHasKey('referralSource', $data);
    }

    public function testUpdatePersonFields(): void
    {
        $user = TestUser::get();

        $this->request('PATCH', \sprintf('/v4/users/%d/person', $user->getId()), [
            'headers' => $this->withAuthHeader($user),
            'json' => [
                'taxIdType' => 'passport',
                'birthYear' => 1990,
                'gender' => 'non_binary',
            ],
        ], Response::HTTP_OK);

        $this->assertJsonContains([
            'taxIdType' => 'passport',
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

        $this->request('PATCH', \sprintf('/v4/users/%d/person', $user->getId()), [
            'headers' => $this->withAuthHeader($user),
            'json' => ['birthYear' => (int) date('Y') + 1],
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
                'taxIdType' => 'national_id',
                'legalForm' => 'association',
                'publicEntity' => true,
            ],
        ], Response::HTTP_OK);

        $this->assertJsonContains([
            'taxIdType' => 'national_id',
            'legalForm' => 'association',
            'publicEntity' => true,
        ]);
    }
}
