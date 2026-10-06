<?php

namespace App\Tests\Entity\ProjectApi;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Territory;
use App\Factory\CategoryFactory;
use App\Factory\Project\ProjectFactory;
use App\Tests\Fixtures\TestUser;
use App\Tests\Traits\RequestingTestTrait;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

use function Zenstruck\Foundry\Persistence\save;

abstract class ProjectTestCase extends ApiTestCase
{
    use ResetDatabase;
    use Factories;
    use RequestingTestTrait;

    protected const BASE_URI = '/v4/projects';

    public function setUp(): void
    {
        self::bootKernel();

        save(CategoryFactory::createOne(['name' => 'test']));
    }

    // Auxiliary Methods

    /**
     * Defines the HTTP method that will be used in the requests made by the tests.
     *
     * @return string The HTTP method to be used for the request
     *                (e.g., 'GET', 'POST', 'PUT', 'DELETE').
     */
    abstract protected function getMethod(): string;

    protected function getUri(?int $id = null): string
    {
        $param = $id == null ? '' : "/$id";

        return self::BASE_URI.$param;
    }

    protected function createTestProjectOptimized(int $count = 1, array $attributes = []): array
    {
        $owner = TestUser::get();
        $territory = new Territory('ES');

        $mergedAttributes = array_merge([
            'owner' => $owner,
            'territory' => $territory,
        ], $attributes);

        return ProjectFactory::createMany($count, $mergedAttributes);
    }

    // Auxiliary Tests

    protected function testOneNotFound(): void
    {
        $this->createTestProjectOptimized(1);

        $this->request($this->getMethod(), $this->getUri(999), [], Response::HTTP_NOT_FOUND);
    }
}
