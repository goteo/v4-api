<?php

namespace App\Tests\Entity;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Project\Project;
use App\Entity\Project\ProjectDeadline;
use App\Entity\Project\ProjectStatus;
use App\Entity\Project\Review;
use App\Entity\Project\ReviewType;
use App\Entity\User\User;
use App\Service\Project\ProjectService;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Test\ResetDatabase;

class ProjectCampaignReviewTest extends ApiTestCase
{
    use ResetDatabase;
    private EntityManagerInterface $entityManager;
    private ProjectService $projectService;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->projectService = static::getContainer()->get(ProjectService::class);
        $this->owner = new User();
        $this->owner->setHandle('test_user');
        $this->owner->setEmail('testuser@example.com');
        $this->owner->setPassword('projectapitestpassword');
        $this->entityManager->persist($this->owner);
        $this->entityManager->flush();
    }

    private function createProjectAndSetToInCampaignReview(): Project
    {
        $project = new Project();
        $project->setTitle('test project title');
        $project->setSubtitle('test project subtitle');
        $project->setOwner($this->owner);
        $project->setStatus(ProjectStatus::InDraft);
        $project->setDeadline(ProjectDeadline::Minimum);
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        $project = $this->projectService->transition($project, ProjectStatus::ToCampaignReview);
        $this->entityManager->flush();

        return $project;
    }

    public function testCreatesReviewOnStatusChange(): void
    {
        $project = $this->createProjectAndSetToInCampaignReview();
        $reviews = $this->entityManager->getRepository(Review::class)->findBy(['project' => $project->getId()]);

        $this->assertCount(1, $reviews);
        $this->assertSame(ReviewType::Campaign->value, $reviews[0]->getType()->value);
    }
}
