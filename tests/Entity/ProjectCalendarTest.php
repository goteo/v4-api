<?php

namespace App\Tests\Entity;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Project\Project;
use App\Entity\Project\ProjectDeadline;
use App\Entity\Project\ProjectStatus;
use App\Entity\User\User;
use App\Factory\Project\ProjectFactory;
use App\Factory\User\UserFactory;
use App\Service\Project\ProjectService;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ProjectCalendarTest extends ApiTestCase
{
    use ResetDatabase;
    use Factories;

    private EntityManagerInterface $entityManager;
    private ProjectService $projectService;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->projectService = static::getContainer()->get(ProjectService::class);

        $this->owner = $this->createTestUser();
    }

    private function createTestUser(string $handle = 'test_user', string $email = 'testuser@example.com'): User
    {
        return UserFactory::new([
            'handle' => $handle,
            'email' => $email,
            'password' => 'projectapitestpassword',
        ])->create()->_real();
    }

    private function createTestProject(ProjectDeadline $deadline = ProjectDeadline::Minimum): Project
    {
        return ProjectFactory::createOne([
            'owner' => $this->owner,
            'status' => ProjectStatus::ToCampaign,
            'deadline' => $deadline,
        ])->_real();
    }

    private function createProjectAndSetToInCampaign(ProjectDeadline $deadline = ProjectDeadline::Minimum): Project
    {
        $project = $this->createTestProject($deadline);
        $project = $this->projectService->transition($project, ProjectStatus::InCampaign);
        $this->entityManager->flush();

        return $project;
    }

    public function testReleaseDateUpdatesOnCampaignStart(): void
    {
        $project = $this->createProjectAndSetToInCampaign();
        $today = new \DateTimeImmutable('today');
        $releaseDate = $project->getCalendar()->release;

        $this->assertNotNull($releaseDate);
        $this->assertGreaterThan($today, $releaseDate);
    }

    public function testMinimumDeadlineIsSetCorrectly(): void
    {
        $project = $this->createProjectAndSetToInCampaign(ProjectDeadline::Minimum);
        $releaseDate = $project->getCalendar()->release;
        $minimumDeadline = $project->getCalendar()->minimum;

        $this->assertNotNull($releaseDate);
        $this->assertNotNull($minimumDeadline);
        $this->assertEquals($releaseDate->modify('+40 days'), $minimumDeadline);
    }

    public function testOptimumDeadlineIsSetCorrectly(): void
    {
        $project = $this->createProjectAndSetToInCampaign(ProjectDeadline::Optimum);
        $minimumDeadline = $project->getCalendar()->minimum;
        $optimumDeadline = $project->getCalendar()->optimum;

        $this->assertNotNull($minimumDeadline);
        $this->assertNotNull($optimumDeadline);
        $this->assertEquals($minimumDeadline->modify('+40 days'), $optimumDeadline);
    }
}
