<?php

namespace App\Entity\Project;

use App\Entity\EmbeddableMoney;
use Doctrine\ORM\Mapping as ORM;

/**
 * Denormalized totals of the BudgetItems of a Project, kept in sync by `ProjectBudgetListener`.\
 * All `null` until first calculated.
 */
#[ORM\Embeddable]
class ProjectBudget
{
    #[ORM\Embedded(class: EmbeddableMoney::class)]
    public ?EmbeddableMoney $minimumMoney = null;

    #[ORM\Embedded(class: EmbeddableMoney::class)]
    public ?EmbeddableMoney $minimumTask = null;

    #[ORM\Embedded(class: EmbeddableMoney::class)]
    public ?EmbeddableMoney $minimumMaterial = null;

    #[ORM\Embedded(class: EmbeddableMoney::class)]
    public ?EmbeddableMoney $minimumInfra = null;

    #[ORM\Embedded(class: EmbeddableMoney::class)]
    public ?EmbeddableMoney $optimumMoney = null;

    #[ORM\Embedded(class: EmbeddableMoney::class)]
    public ?EmbeddableMoney $optimumTask = null;

    #[ORM\Embedded(class: EmbeddableMoney::class)]
    public ?EmbeddableMoney $optimumMaterial = null;

    #[ORM\Embedded(class: EmbeddableMoney::class)]
    public ?EmbeddableMoney $optimumInfra = null;
}
