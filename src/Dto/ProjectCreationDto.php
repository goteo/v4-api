<?php

namespace App\Dto;

use ApiPlatform\Metadata as API;
use App\ApiResource\CategoryApiResource;
use App\Entity\Project\Project;
use App\Entity\Project\ProjectCalendar;
use App\Entity\Project\ProjectStatus;
use App\Entity\Territory;
use App\Validator\NotExisting;
use Symfony\Component\Validator\Constraints as Assert;

class ProjectCreationDto
{
    use CategoryInputDtoTrait;

    /**
     * Main headline for the Project. Must include at least one character between a-Z.
     */
    #[Assert\NotBlank()]
    #[Assert\Regex('/[a-zA-Z]{1,}/')]
    #[Assert\Length(min: 3)]
    #[NotExisting(Project::class, 'title')]
    public string $title;

    /**
     * Secondary headline for the Project.
     */
    #[Assert\NotBlank()]
    public string $subtitle;

    /**
     * List of Categories.
     *
     * @var CategoryApiResource[]
     */
    #[Assert\NotBlank()]
    #[Assert\Count(min: 1, max: 2)]
    #[API\ApiProperty(writableLink: false, openapiContext: self::CATEGORIES_OPENAPI_CONTEXT)]
    public array $categories;

    /**
     * ISO 3166 data about the Project's territory of interest.
     */
    #[Assert\Valid()]
    #[Assert\NotBlank()]
    public Territory $territory;

    /**
     * Deadlines and important Project dates.
     */
    #[Assert\Valid()]
    #[Assert\NotBlank()]
    public ProjectCalendar $calendar;

    #[API\ApiProperty(writable: false)]
    public ProjectStatus $status = ProjectStatus::InDraft;
}
