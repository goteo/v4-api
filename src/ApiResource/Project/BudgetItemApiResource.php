<?php

namespace App\ApiResource\Project;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\ApiResource\LocalizedApiResourceTrait;
use App\ApiResource\MoneyInput;
use App\Entity\Project\BudgetItem;
use App\Entity\Project\BudgetItemType;
use App\Entity\Project\ProjectDeadline;
use App\State\ApiResourceStateProcessor;
use App\State\ApiResourceStateProvider;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A Project's budget is composed via BudgetItem records.\
 * \
 * Each BudgetItem describes one specific monetary necessity. The total budget of a Project is then calculated from the related BudgetItems.
 */
#[API\ApiResource(
    shortName: 'ProjectBudgetItem',
    stateOptions: new Options(entityClass: BudgetItem::class),
    provider: ApiResourceStateProvider::class,
    processor: ApiResourceStateProcessor::class,
    securityPostDenormalize: 'is_granted("PROJECT_EDIT", object.project)',
    securityPostDenormalizeMessage: 'You do not have permission to add BudgetItems to that Project'
)]
class BudgetItemApiResource
{
    use LocalizedApiResourceTrait;

    #[API\ApiProperty(identifier: true, writable: false)]
    public int $id;

    #[Assert\NotBlank()]
    #[API\ApiFilter(filterClass: SearchFilter::class, strategy: 'exact')]
    public ProjectApiResource $project;

    /**
     * The type of need this item solves.
     */
    #[Assert\NotBlank()]
    #[API\ApiFilter(filterClass: SearchFilter::class, strategy: 'exact')]
    public BudgetItemType $type;

    /**
     * A short, descriptive string for the item.
     */
    #[Assert\NotBlank()]
    public string $title;

    /**
     * Detailed information about the item.
     */
    #[Assert\NotBlank()]
    public string $description;

    /**
     * The amount of money required for this item.
     */
    #[Assert\NotBlank()]
    #[Assert\Valid()]
    public MoneyInput $money;

    /**
     * Defines the budget category for this item within the project.
     *
     * This field specifies whether the budget item belongs to the minimum or optimum budget:
     */
    #[Assert\NotBlank()]
    #[API\ApiFilter(filterClass: SearchFilter::class, strategy: 'exact')]
    public ProjectDeadline $deadline;
}
