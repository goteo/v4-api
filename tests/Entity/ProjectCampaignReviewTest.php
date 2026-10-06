<?php

namespace App\Tests\Entity;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Project\Project;
use App\Entity\Project\ProjectDeadline;
use App\Entity\Project\ProjectStatus;
use App\Entity\Project\Review;
use App\Entity\Project\ReviewType;
use App\Entity\User\User;
use App\Factory\Project\ProjectFactory;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ProjectCampaignReviewTest extends ApiTestCase
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
            'status' => ProjectStatus::InDraft
        ])->_real();
    }

    private function createProjectAndSetToInCampaignReview(): Project
    {
        $project = $this->createTestProject();

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $project->setStatus(ProjectStatus::ToCampaignReview);

        $this->entityManager->flush();

        return $project;
    }

    public function testCreatesReviewOnStatusChange(): void
    {
        $project = $this->createProjectAndSetToInCampaignReview();
        $reviews = $this->entityManager->getRepository(Review::class)->findBy(['project' => $project->getId()]);

        $this->assertNotEmpty($reviews);
        $this->assertEquals(1, \count($reviews));
        $this->assertEquals(ReviewType::Campaign->value, $reviews[0]->getType()->value);
    }
}
