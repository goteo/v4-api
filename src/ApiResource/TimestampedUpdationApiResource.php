<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata as API;

trait TimestampedUpdationApiResource
{
    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(DateFilter::class)]
    #[API\ApiFilter(OrderFilter::class)]
    public \DateTimeInterface $dateUpdated;
}
