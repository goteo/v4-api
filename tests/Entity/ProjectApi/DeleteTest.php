<?php

namespace App\Tests\Entity\ProjectApi;

use App\Factory\User\UserFactory;
use App\Tests\Fixtures\TestUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DeleteTest extends ProjectTestCase
{
    protected function getMethod(): string
    {
        return Request::METHOD_DELETE;
    }

    // Runable Tests

    public function testDeleteWithValidToken(): void
    {
        $this->createTestProjectOptimized();

        $this->request(Request::METHOD_DELETE, $this->getUri(1), ['headers' => $this->withAuthHeader(TestUser::get())]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testDeleteUnauthorized()
    {
        $this->createTestProjectOptimized();

        static::createClient()->request(Request::METHOD_DELETE, $this->getUri(1));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testDeleteNotFound(): void
    {
        $this->testOneNotFound();
    }

    public function testDeleteForbidden(): void
    {
        $this->createTestProjectOptimized();

        $otherUser = UserFactory::new(['handle' => 'other_user', 'email' => 'otheruser@example.com'])->create();
        $this->request($this->getMethod(), $this->getUri(1), [
            'headers' => $this->withAuthHeader($otherUser),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
