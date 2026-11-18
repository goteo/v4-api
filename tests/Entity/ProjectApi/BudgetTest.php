<?php

namespace App\Tests\Entity\ProjectApi;

use App\Tests\Fixtures\TestUser;

class BudgetTest extends ProjectTestCase
{
    private const ITEMS_URI = '/v4/project_budget_items';

    protected function getMethod(): string
    {
        return 'GET';
    }

    private array $ownerHeaders;

    private function requestAsOwner(string $method, string $uri, ?array $json = null): array
    {
        // Resolved once, the User proxy can't be refreshed after the requests
        $this->ownerHeaders ??= $this->withAuthHeader(TestUser::get());

        $options = ['headers' => $this->ownerHeaders];
        if ($json !== null) {
            $options['json'] = $json;
        }

        $response = $this->request($method, $uri, $options);
        $this->assertResponseIsSuccessful();

        return \json_decode($response->getContent(false), true) ?? [];
    }

    private function createItem(string $type, string $deadline, int $amount): int
    {
        return $this->requestAsOwner('POST', self::ITEMS_URI, [
            'project' => $this->getUri(1),
            'type' => $type,
            'title' => 'Item',
            'description' => 'An item',
            'money' => ['amount' => $amount, 'currency' => 'EUR'],
            'deadline' => $deadline,
        ])['id'];
    }

    /**
     * @return array<string, array<string, int>> Budget amounts by deadline and summary key
     */
    private function getBudgetAmounts(): array
    {
        $budget = $this->requestAsOwner('GET', $this->getUri(1))['budget'];

        $amounts = [];
        foreach (['minimum', 'optimum'] as $deadline) {
            foreach (['money', 'task', 'material', 'infra'] as $key) {
                $amounts[$deadline][$key] = $budget[$deadline][$key]['amount'];
            }
        }

        return $amounts;
    }

    public function testBudgetIsZeroWithoutItems(): void
    {
        $this->createTestProjectOptimized();

        $zero = ['money' => 0, 'task' => 0, 'material' => 0, 'infra' => 0];

        $this->assertEquals(['minimum' => $zero, 'optimum' => $zero], $this->getBudgetAmounts());
    }

    public function testBudgetFollowsBudgetItems(): void
    {
        $this->createTestProjectOptimized();

        $task = $this->createItem('task', 'minimum', 1000);
        $material = $this->createItem('material', 'minimum', 500);
        $this->createItem('infrastructure', 'optimum', 200);

        $this->assertEquals([
            'minimum' => ['money' => 1500, 'task' => 1000, 'material' => 500, 'infra' => 0],
            'optimum' => ['money' => 1700, 'task' => 1000, 'material' => 500, 'infra' => 200],
        ], $this->getBudgetAmounts());

        $this->requestAsOwner('PATCH', self::ITEMS_URI."/$task", [
            'money' => ['amount' => 2000, 'currency' => 'EUR'],
        ]);
        $this->requestAsOwner('DELETE', self::ITEMS_URI."/$material");

        $expected = [
            'minimum' => ['money' => 2000, 'task' => 2000, 'material' => 0, 'infra' => 0],
            'optimum' => ['money' => 2200, 'task' => 2000, 'material' => 0, 'infra' => 200],
        ];

        $this->assertEquals($expected, $this->getBudgetAmounts());

        // Updating the Project itself must not touch the stored budget
        $this->requestAsOwner('PATCH', $this->getUri(1), ['title' => 'New project title']);

        $this->assertEquals($expected, $this->getBudgetAmounts());
    }
}
