<?php

namespace App\Dto;

use ApiPlatform\Metadata as API;
use App\ApiResource\CategoryApiResource;
use App\Entity\Project\Project;
use App\Entity\Project\ProjectCalendar;
use App\Entity\Project\ProjectDeadline;
use App\Entity\Project\ProjectStatus;
use App\Entity\Territory;
use App\Mapping\Transformer\ProjectVideoMapTransformer;
use AutoMapper\Attribute\MapTo;
use Symfony\Component\Validator\Constraints as Assert;

class ProjectUpdationDto
{
    use CategoryInputDtoTrait;

    #[API\ApiProperty(identifier: true, writable: false)]
    public int $id;

    /**
     * Main headline for the Project.
     */
    #[Assert\Regex('/[a-zA-Z]{1,}/')]
    #[Assert\Length(min: 3)]
    public string $title;

    /**
     * Secondary headline for the Project.
     */
    public string $subtitle;

    /**
     * URL to an image resource to be displayed as header.
     */
    #[Assert\Url()]
    public string $cover;

    /**
     * List of Categories.
     *
     * @var CategoryApiResource[]
     */
    #[Assert\Count(min: 1, max: 2)]
    #[API\ApiProperty(writableLink: false, openapiContext: self::CATEGORIES_OPENAPI_CONTEXT)]
    public array $categories;

    /**
     * ISO 3166 data about the Project's territory of interest.
     */
    #[Assert\Valid()]
    public Territory $territory;

    /**
     * Rich-text (markdown allowed) introduction to the project.
     */
    #[Assert\Length(min: 20)]
    public string $descBrief;

    /**
     * Rich-text (markdown allowed) description on the main features of the project.
     */
    #[Assert\Length(min: 20)]
    public string $descAbout;

    /**
     * Rich-text (markdown allowed) about why this project is important.
     */
    #[Assert\Length(min: 20)]
    public string $descGoal;

    /**
     * Rich-text (markdown allowed) about team and previous experience.
     */
    #[Assert\Length(min: 20)]
    public string $descTeam;

    /**
     * On `minimum`, Project will campaign until the minimum deadline.\
     * On `optimum`, Project will campaing until the minimum deadline,
     * and then until the optimum deadline if it did raise the minimum.
     */
    public ProjectDeadline $deadline;

    /**
     * Deadlines and important Project dates.
     */
    #[Assert\Valid()]
    public ProjectCalendar $calendar;

    /**
     * A URL to a video showcasing the Project.
     */
    #[Assert\Url()]
    #[MapTo(target: Project::class, transformer: ProjectVideoMapTransformer::class)]
    public string $video;

    /**
     * The status of a Project represents how far it is in it's life-cycle.
     */
    public ProjectStatus $status;
}
