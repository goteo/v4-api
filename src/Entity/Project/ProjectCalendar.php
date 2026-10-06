<?php

namespace App\Entity\Project;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable()]
class ProjectCalendar
{
    /**
     * Public campaign start date.
     */
    #[Assert\NotBlank()]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $release = null;

    /**
     * The minimum budget must be raised by the end of this date,
     * failure to do so will move the Project out of status `in_campaign` into status `unfunded`.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $minimum = null;

    /**
     * If the Project achieved their minimum budget by the minimum deadline,
     * and this deadline is defined, it may still remain `in_campaign` to raise the optimum.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $optimum = null;
}
