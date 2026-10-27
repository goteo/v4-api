<?php

namespace App\Tests\Entity\ProjectApi;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Category;
use App\Entity\EmbeddableMoney;
use App\Entity\Project\Project;
use App\Entity\Project\ProjectStatus;
use App\Entity\User\User;
use App\Factory\Project\ProjectFactory;
use App\Factory\User\UserFactory;
use App\Tests\Traits\RequestingTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class MoneyTotalTest extends ApiTestCase
{
    use ResetDatabase;
    use Factories;
    use RequestingTestTrait;

    private const URI = '/v4/projects/money_total';

    private EntityManagerInterface $entityManager;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->owner = UserFactory::new()->withoutPersisting()->create()->_real();

        $this->entityManager->persist($this->owner);
        $this->entityManager->flush();
    }

    private function createCategory(string $name): Category
    {
        $category = new Category();
        $category->setName($name);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    /**
     * @param Category[] $categories
     */
    private function createProjectWithBalance(
        int $amount,
        ProjectStatus $status = ProjectStatus::InCampaign,
        array $categories = [],
    ): void {
        /** @var Project */
        $project = ProjectFactory::new([
            'owner' => $this->owner,
            'status' => $status,
        ])->withoutPersisting()->create()->_real();

        foreach ($categories as $category) {
            $project->addCategory($category);
        }

        $project->getAccounting()->setBalance(new EmbeddableMoney($amount, 'EUR'));

        $this->entityManager->persist($project);
        $this->entityManager->flush();
    }

    public function testEmpty(): void
    {
        $this->request('GET', self::URI, [], Response::HTTP_OK);

        $this->assertJsonContains(['amount' => 0, 'length' => 0]);
    }

    public function testUnfilteredReturnsTotal(): void
    {
        $this->createProjectWithBalance(1000);
        $this->createProjectWithBalance(2500);

        $this->request('GET', self::URI, [], Response::HTTP_OK);

        $this->assertJsonContains(['amount' => 3500, 'currency' => 'EUR', 'length' => 2]);
    }

    public function testFilteredByStatus(): void
    {
        $this->createProjectWithBalance(1000, ProjectStatus::InCampaign);
        $this->createProjectWithBalance(2500, ProjectStatus::InDraft);

        $this->request('GET', self::URI, [
            'query' => ['status' => ProjectStatus::InCampaign->value],
        ], Response::HTTP_OK);

        $this->assertJsonContains(['amount' => 1000, 'length' => 1]);
    }

    public function testFilteredByManyCategoriesDoesNotDuplicate(): void
    {
        $a = $this->createCategory('a');
        $b = $this->createCategory('b');
        $this->createProjectWithBalance(1000, categories: [$a, $b]);

        $this->request('GET', self::URI, [
            'query' => ['categories' => [$a->getId(), $b->getId()]],
        ], Response::HTTP_OK);

        $this->assertJsonContains(['amount' => 1000, 'length' => 1]);
    }
}
