<?php

namespace App\State\Project;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Exception\AccessDeniedException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Project\ProjectApiResource;
use App\Dto\ProjectCreationDto;
use App\Dto\ProjectUpdationDto;
use App\Entity\Project\Project;
use App\Mapping\AutoMapper;
use App\Service\Project\CalendarService;
use App\Service\Project\ProjectService;
use App\State\EntityStateProcessor;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ProjectStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityStateProcessor $entityStateProcessor,
        private AutoMapper $autoMapper,
        private Security $security,
        private ProjectService $projectService,
        private CalendarService $calendarService,
    ) {}

    /**
     * @param ProjectCreationDto|ProjectUpdationDto $data
     *
     * @return ProjectApiResource
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $project = match ($data::class) {
            ProjectCreationDto::class => $this->getProjectFromCreation($data),
            ProjectUpdationDto::class => $this->getProjectFromUpdate($data, $context),
            default => throw new \InvalidArgumentException('Unsupported input for Project resource'),
        };

        $project = $this->entityStateProcessor->process($project, $operation, $uriVariables, $context);

        if ($operation instanceof DeleteOperationInterface) {
            return;
        }

        if ($project === null) {
            return null;
        }

        return $this->autoMapper->map($project, ProjectApiResource::class);
    }

    private function getProjectFromCreation(ProjectCreationDto $data): Project
    {
        /** @var Project */
        $project = $this->autoMapper->map($data, Project::class);

        $owner = $this->security->getUser();
        if (!$owner) {
            throw new AuthenticationException();
        }

        $project->setOwner($owner);

        if (!isset($data->calendar->release)) {
            $project->setCalendar($this->calendarService->makeCalendar(
                $project->getDeadline(),
                new \DateTimeImmutable('+28 days')
            ));
        }

        return $project;
    }

    private function getProjectFromUpdate(ProjectUpdationDto $data, array $context): Project
    {
        $actor = $this->security->getUser();
        if (!$actor) {
            throw new AuthenticationException();
        }

        /** @var Project */
        $project = $this->autoMapper->map($context['previous_data'], Project::class);

        if (isset($data->status) && $data->status !== $project->getStatus()) {
            if (!$this->projectService->canTransition($actor, $project, $data->status)) {
                throw new AccessDeniedException(\sprintf(
                    "Cannot move the Project from status '%s' to '%s'",
                    $project->getStatus()->value,
                    $data->status->value
                ));
            }

            $project = $this->projectService->transition($project, $data->status);
        }

        return $this->autoMapper->map($data, $project);
    }
}
