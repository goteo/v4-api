<?php

namespace App\Tests\Entity\ProjectApi;

use App\Entity\Category;
use App\Entity\EmbeddableMoney;
use App\Entity\Project\Project;
use App\Entity\Project\ProjectStatus;
use App\Factory\CategoryFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class MoneyTotalTest extends ProjectTestCase
{
    private const URI = '/v4/projects/money_total';

    protected function getMethod(): string
    {
        return 'GET';
    }

    private function createProjectWithBalance(?int $amount, array $attributes = []): Project
    {
        /** @var Project */
        $project = $this->createTestProjectOptimized(1, $attributes)[0]->_real();

        if ($amount !== null) {
            $project->getAccounting()->setBalance(new EmbeddableMoney($amount, 'EUR'));
        }

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->flush();

        return $project;
    }

    public function testEmpty(): void
    {
        $this->request($this->getMethod(), self::URI, [], Response::HTTP_OK);

        $this->assertJsonContains(['amount' => 0, 'length' => 0]);
    }

    public function testUnfilteredReturnsTotal(): void
    {
        $this->createProjectWithBalance(1000);
        $this->createProjectWithBalance(2500);
        $this->createProjectWithBalance(null);

        $this->request($this->getMethod(), self::URI, [], Response::HTTP_OK);

        $this->assertJsonContains(['amount' => 3500, 'currency' => 'EUR', 'length' => 2]);
    }

    public function testFilteredByStatus(): void
    {
        $this->createProjectWithBalance(1000, ['status' => ProjectStatus::InCampaign]);
        $this->createProjectWithBalance(2500, ['status' => ProjectStatus::InDraft]);

        $this->request($this->getMethod(), self::URI, [
            'query' => ['status' => ProjectStatus::InCampaign->value],
        ], Response::HTTP_OK);

        $this->assertJsonContains(['amount' => 1000, 'length' => 1]);
    }

    public function testFilteredByManyCategoriesDoesNotDuplicate(): void
    {
        /** @var Category */
        $a = CategoryFactory::createOne(['name' => 'a'])->_real();
        /** @var Category */
        $b = CategoryFactory::createOne(['name' => 'b'])->_real();
        $this->createProjectWithBalance(1000, ['categories' => [$a, $b]]);

        $this->request($this->getMethod(), self::URI, [
            'query' => ['categories' => [$a->getId(), $b->getId()]],
        ], Response::HTTP_OK);

        $this->assertJsonContains(['amount' => 1000, 'length' => 1]);
    }
}
