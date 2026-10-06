<?php

namespace App\Tests\Entity\ProjectApi;

use App\Tests\Fixtures\TestUser;
use Symfony\Component\HttpFoundation\Response;

class CreateTest extends ProjectTestCase
{
    protected function getMethod(): string
    {
        return 'POST';
    }

    public function testPostWithoutMandatoryField(): void
    {
        $this->request($this->getMethod(), self::BASE_URI, [
            'headers' => $this->withAuthHeader(TestUser::get()),
            'json' => [
                'subtitle' => 'Education for the Future',
                'territory' => ['country' => 'ES'],
                'description' => 'Detailed project description',
                'deadline' => 'minimum',
                'video' => 'https://www.youtube.com/watch?v=bnrVQHEXmOk',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testPostWithInvalidCategories(): void
    {
        $this->request($this->getMethod(), self::BASE_URI, [
            'headers' => $this->withAuthHeader(TestUser::get()),
            'json' => [
                'subtitle' => 'Education for the Future',
                'territory' => ['country' => 'ES'],
                'description' => 'Detailed project description',
                'deadline' => 'minimum',
                'video' => 'https://www.youtube.com/watch?v=bnrVQHEXmOk',
                'categories' => 'nonexistent-categories',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testPostUnauthorized()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            self::BASE_URI,
            [
                'json' => [
                    'title' => 'ProjectApiTest Project',
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
