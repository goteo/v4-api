<?php

namespace App\Tests\Entity\ProjectApi;

use App\Factory\User\UserFactory;
use App\Tests\Fixtures\TestUser;
use Symfony\Component\HttpFoundation\Response;

class UpdateTest extends ProjectTestCase
{
    // Auxiliary functions

    protected function getMethod(): string
    {
        return 'PATCH';
    }

    // Runable Tests

    public function testUpdateSuccessful(): void
    {
        $this->createTestProjectOptimized();

        $this->request($this->getMethod(), $this->getUri(1), [
            'headers' => $this->withAuthHeader(TestUser::get()),
            'json' => [
                'title' => 'New project title',
                'descBrief' => 'Updated Project\'s brief description',
            ],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testUpdateUnauthorized(): void
    {
        $this->createTestProjectOptimized();

        static::createClient()->request($this->getMethod(), $this->getUri(1));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testUpdateNotFound(): void
    {
        $this->testOneNotFound();
    }

    public function testUpdateInvalidInput(): void
    {
        $this->createTestProjectOptimized();

        $this->request($this->getMethod(), $this->getUri(1), [
            'headers' => $this->withAuthHeader(TestUser::get()),
            'json' => [
                'title' => 'New project title',
                'categories' => ['invalid-category'],
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    private function patchSdgs(mixed $sdgs): void
    {
        $this->request($this->getMethod(), $this->getUri(1), [
            'headers' => $this->withAuthHeader(TestUser::get()),
            'json' => ['sdgs' => $sdgs],
        ]);
    }

    private function getSdgs(): array
    {
        $response = $this->request('GET', $this->getUri(1));

        return \json_decode($response->getContent(), true)['sdgs'];
    }

    public function testUpdateSdgs(): void
    {
        $this->createTestProjectOptimized();

        $this->patchSdgs([11, 4]);

        $this->assertResponseIsSuccessful();
        $this->assertSame([4, 11], $this->getSdgs());
    }

    public function testUpdateSdgsToEmpty(): void
    {
        $this->createTestProjectOptimized(1, ['sdgs' => [4, 11]]);

        $this->patchSdgs([]);

        $this->assertResponseIsSuccessful();
        $this->assertSame([], $this->getSdgs());
    }

    public function testUpdateWithoutSdgsKeepsThem(): void
    {
        $this->createTestProjectOptimized(1, ['sdgs' => [4, 11]]);

        $this->request($this->getMethod(), $this->getUri(1), [
            'headers' => $this->withAuthHeader(TestUser::get()),
            'json' => ['title' => 'New project title'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame([4, 11], $this->getSdgs());
    }

    public static function invalidSdgsProvider(): array
    {
        return [
            'too many' => [[1, 2, 3, 4]],
            'below range' => [[0]],
            'above range' => [[18]],
            'duplicated' => [[4, 4]],
        ];
    }

    /**
     * @dataProvider invalidSdgsProvider
     */
    public function testUpdateSdgsInvalid(array $sdgs): void
    {
        $this->createTestProjectOptimized();

        $this->patchSdgs($sdgs);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSame([], $this->getSdgs());
    }

    public function testUpdateForbidden(): void
    {
        $this->createTestProjectOptimized();

        $otherUser = UserFactory::new(['handle' => 'other_user', 'email' => 'otheruser@example.com'])->create();
        $this->request($this->getMethod(), $this->getUri(1), [
            'headers' => $this->withAuthHeader($otherUser),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
