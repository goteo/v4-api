<?php

namespace App\Tests\Traits;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\User\User;
use App\Security\TestingAuthenticator;
use Symfony\Contracts\HttpClient\ResponseInterface;

trait RequestingTestTrait
{
    protected function request(
        string $method,
        string $uri,
        array $options = [],
        ?int $expectedCode = null,
    ): ResponseInterface {
        /** @var Client */
        $client = static::createClient();
        $response = $client->request($method, $uri, \array_merge_recursive(
            [
                'headers' => [
                    'Content-Type' => match ($method) {
                        'PATCH' => 'application/merge-patch+json',
                        default => 'application/json',
                    },
                ],
            ],
            $options,
        ));

        if ($expectedCode !== null) {
            $this->assertResponseStatusCodeSame($expectedCode);
        }

        return $response;
    }

    protected function withAuthHeader(User $user): array
    {
        return [
            TestingAuthenticator::AUTH_HEADER => $user->getId(),
        ];
    }
}
