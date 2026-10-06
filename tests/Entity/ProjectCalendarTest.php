<?php

namespace App\Tests\Entity;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Project\Project;
use App\Entity\Project\ProjectDeadline;
use App\Entity\Project\ProjectStatus;
use App\Entity\User\User;
use App\Factory\Project\ProjectFactory;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ProjectCalendarTest extends ApiTestCase
{
    use ResetDatabase;
    use Factories;

    private EntityManagerInterface $entityManager;
    private User $owner;

    public function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->owner = $this->createTestUser();
        $this->entityManager->persist($this->owner);
        $this->entityManager->flush();
    }

    private function createTestUser(string $handle = 'test_user', string $email = 'testuser@example.com'): User
    {
        $user = new User();
        $user->setHandle($handle);
        $user->setEmail($email);
        $user->setPassword('projectapitestpassword');

        return $user;
    }

    private function createTestProject(ProjectDeadline $deadline = ProjectDeadline::Minimum): Project
    {
        return ProjectFactory::createOne([
            'owner' => $this->owner,
            'deadline' => $deadline,
        ])->_real();
    }

    private function createProjectAndSetToInCampaign(
        ProjectDeadline $deadline = ProjectDeadline::Minimum,
    ): Project {
        $project = $this->createTestProject($deadline);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $project->setStatus(ProjectStatus::InCampaign);
        $this->entityManager->flush();

        return $project;
    }

    public function testReleaseDateUpdatesOnCampaignStart(): void
    {
        $project = $this->createProjectAndSetToInCampaign();

        $this->assertNotNull($project->getCalendar()->release);
    }

    public function testMinimumDeadlineIsSetCorrectly(): void
    {
        $project = $this->createProjectAndSetToInCampaign(ProjectDeadline::Minimum);

        $releaseDate = $project->getCalendar()->release;
        $minimumDeadline = $project->getCalendar()->minimum;

        $this->assertNotNull($minimumDeadline);
        $this->assertGreaterThan($releaseDate, $minimumDeadline);
        $this->assertEquals($releaseDate->modify('+40 days'), $minimumDeadline);
    }

    public function testOptimumDeadlineIsSetCorrectly(): void
    {
        $project = $this->createProjectAndSetToInCampaign(ProjectDeadline::Optimum);

        $minimumDeadline = $project->getCalendar()->minimum;
        $optimumDeadline = $project->getCalendar()->optimum;

        $this->assertNotNull($optimumDeadline);
        $this->assertGreaterThan($minimumDeadline, $optimumDeadline);
        $this->assertEquals($minimumDeadline->modify('+40 days'), $optimumDeadline);
    }

    public function testProjectNotInCampaignHasNoCalendar(): void
    {
        $project = $this->createTestProject();

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $this->assertNull($project->getCalendar()->release);
        $this->assertNull($project->getCalendar()->minimum);
        $this->assertNull($project->getCalendar()->optimum);
    }
}
