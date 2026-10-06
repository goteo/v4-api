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
