<?php

namespace App\Tests\Entity\ProjectApi;

use App\Entity\Project\Project;
use App\Entity\Project\ProjectStatus;
use App\Tests\Fixtures\TestUser;

use function Zenstruck\Foundry\Persistence\save;

class GetOneTest extends ProjectTestCase
{
    // Auxiliary functions

    protected function getMethod(): string
    {
        return 'GET';
    }

    private function getSerializedProject(Project $project)
    {
        return [
            'id' => $project->getId(),
            'title' => $project->getTitle(),
            'subtitle' => $project->getSubtitle(),
            'territory' => ['country' => $project->getTerritory()->country],
            'deadline' => $project->getDeadline()->value,
            'status' => $project->getStatus()->value,
        ];
    }

    private function assertProjectData(array $responseData, Project $project): void
    {
        $expectedData = $this->getSerializedProject($project);

        $this->assertArrayHasKey('id', $responseData);
        $this->assertArraySubset($expectedData, $responseData);
    }

    private function createProjectWithUserOwnedAccounting(): array
    {
        $project = $this->createTestProjectOptimized(1)[0];
        $owner = TestUser::get();
        $userAccounting = $owner->getAccounting();

        $project->setAccounting($userAccounting);
        save($project);

        return [$project, $owner, $userAccounting];
    }

    // TESTS

    // Auxiliary Tests

    private function testSuccessfulGetOneBase(Project $project): void
    {
        $response = $this->request('GET', $this->getUri(1));

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($response->getContent(), true);
        $this->assertProjectData($responseData, $project);
    }

    private function testGetOneFilteredByStatus(ProjectStatus $status): void
    {
        $project = $this->createTestProjectOptimized(1, ['status' => $status])[0];

        $this->testSuccessfulGetOneBase($project);
    }

    // Runable Tests

    public function testGetOneWithValidToken(): void
    {
        $project = $this->createTestProjectOptimized(1)[0];

        $this->testSuccessfulGetOneBase($project);
    }

    public function testGetOneHasEmptySdgs(): void
    {
        $this->createTestProjectOptimized(1);

        $response = $this->request('GET', $this->getUri(1));

        $this->assertResponseIsSuccessful();
        $this->assertSame([], \json_decode($response->getContent(), true)['sdgs']);
    }

    public function testGetOneFilteredByStatusInFunding(): void
    {
        $this->testGetOneFilteredByStatus(ProjectStatus::InFunding);
    }

    public function testGetOneFilteredByStatusFunded(): void
    {
        $this->testGetOneFilteredByStatus(ProjectStatus::FundingPaid);
    }

    public function testGetOneEmbedsAccounting(): void
    {
        $project = $this->createTestProjectOptimized(1)[0];
        $responseData = $this->request('GET', $this->getUri($project->getId()))->toArray();

        $this->assertIsArray($responseData['accounting']);
        $this->assertSame('Accounting', $responseData['accounting']['@type']);
        $this->assertSame($project->getAccounting()->getId(), $responseData['accounting']['id']);
        $this->assertSame($responseData['accounting']['currency'], $responseData['accounting']['balance']['currency']);
    }

    public function testGetOneHidesPrivateAccountingBalanceWithoutAuthentication(): void
    {
        [$project, , $userAccounting] = $this->createProjectWithUserOwnedAccounting();
        $responseData = $this->request('GET', $this->getUri($project->getId()))->toArray();

        $this->assertSame($userAccounting->getId(), $responseData['accounting']['id']);
        $this->assertArrayNotHasKey('balance', $responseData['accounting']);
    }

    public function testGetOneShowsPrivateAccountingBalanceToItsOwner(): void
    {
        [$project, $owner, $userAccounting] = $this->createProjectWithUserOwnedAccounting();
        $responseData = $this->request('GET', $this->getUri($project->getId()), [
            'headers' => $this->withAuthHeader($owner),
        ])->toArray();

        $this->assertSame($userAccounting->getId(), $responseData['accounting']['id']);
        $this->assertArrayHasKey('balance', $responseData['accounting']);
        $this->assertSame($userAccounting->getCurrency(), $responseData['accounting']['balance']['currency']);
    }

    public function testGetOneUnauthorized(): void
    {
        $this->createTestProjectOptimized(1);

        $this->request('GET', $this->getUri(1));

        $this->assertResponseIsSuccessful();
    }

    public function testGetOneNotFound(): void
    {
        $this->testOneNotFound();
    }
}
