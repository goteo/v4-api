<?php

namespace App\Tests\Entity\Accounting;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Factory\User\UserFactory;
use App\Tests\Traits\RequestingTestTrait;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class GetAllTest extends ApiTestCase
{
    use Factories;
    use ResetDatabase;
    use RequestingTestTrait;

    public function testFilterById(): void
    {
        self::bootKernel();

        [$a, $b] = UserFactory::createMany(3, fn(int $i) => [
            'handle' => "user_$i",
            'email' => "user_$i@example.com",
        ]);
        $ids = [$a->getAccounting()->getId(), $b->getAccounting()->getId()];

        $data = $this->request('GET', '/v4/accountings', [
            'query' => ['id' => $ids],
        ], Response::HTTP_OK)->toArray();

        $this->assertEqualsCanonicalizing($ids, \array_column($data['member'], 'id'));
    }
}
