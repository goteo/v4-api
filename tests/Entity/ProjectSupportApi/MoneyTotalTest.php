<?php

namespace App\Tests\Entity\ProjectSupportApi;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\EmbeddableMoney;
use App\Entity\Project\ProjectStatus;
use App\Entity\Project\Support;
use App\Factory\Project\ProjectFactory;
use App\Tests\Fixtures\TestUser;
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

    private const URI = '/v4/project_supports/money_total';

    public function setUp(): void
    {
        self::bootKernel();
    }

    private function createSupport(?int $amount, ProjectStatus $status = ProjectStatus::InDraft): void
    {
        $owner = TestUser::get();
        $project = ProjectFactory::createOne(['owner' => $owner, 'status' => $status]);

        $support = new Support();
        $support->setProject($project->_real());
        $support->setOrigin($owner->getAccounting());
        if ($amount !== null) {
            $support->setMoney(new EmbeddableMoney($amount, 'EUR'));
        }

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($support);
        $entityManager->flush();
    }

    public function testEmpty(): void
    {
        $this->request('GET', self::URI, [], Response::HTTP_OK);

        $this->assertJsonContains(['amount' => 0, 'length' => 0]);
    }

    public function testUnfilteredReturnsTotal(): void
    {
        $this->createSupport(1000);
        $this->createSupport(2500);
        $this->createSupport(null);

        $this->request('GET', self::URI, [], Response::HTTP_OK);

        $this->assertJsonContains(['amount' => 3500, 'currency' => 'EUR', 'length' => 2]);
    }

    public function testFilteredByProjectStatus(): void
    {
        $this->createSupport(1000, ProjectStatus::InCampaign);
        $this->createSupport(2500, ProjectStatus::InDraft);

        $this->request('GET', self::URI, [
            'query' => ['project.status' => ProjectStatus::InCampaign->value],
        ], Response::HTTP_OK);

        $this->assertJsonContains(['amount' => 1000, 'length' => 1]);
    }
}
