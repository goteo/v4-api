<?php

namespace App\Entity\Project;

use Doctrine\ORM\Mapping as ORM;

/**
 * Denormalized totals of the BudgetItems of a Project, kept in sync by `ProjectBudgetListener`.\
 * Amounts are in minor units of `currency`. All `null` until first calculated.
 */
#[ORM\Embeddable]
class ProjectBudget
{
    #[ORM\Column(nullable: true)]
    public ?string $currency = null;

    #[ORM\Column(nullable: true)]
    public ?int $minimumMoney = null;

    #[ORM\Column(nullable: true)]
    public ?int $minimumTask = null;

    #[ORM\Column(nullable: true)]
    public ?int $minimumMaterial = null;

    #[ORM\Column(nullable: true)]
    public ?int $minimumInfra = null;

    #[ORM\Column(nullable: true)]
    public ?int $optimumMoney = null;

    #[ORM\Column(nullable: true)]
    public ?int $optimumTask = null;

    #[ORM\Column(nullable: true)]
    public ?int $optimumMaterial = null;

    #[ORM\Column(nullable: true)]
    public ?int $optimumInfra = null;
}
