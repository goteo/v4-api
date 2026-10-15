<?php

namespace App\ApiResource\Mail;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\Entity\Mail\Audience;
use App\Entity\Mail\AudienceCriteria;
use App\State\ApiResourceStateProcessor;
use App\State\ApiResourceStateProvider;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * MailAudiences are criteria-based, dynamic target lists of recipient Users for MailBatch records.
 */
#[API\ApiResource(
    shortName: 'MailAudience',
    stateOptions: new Options(entityClass: Audience::class),
    processor: ApiResourceStateProcessor::class,
    provider: ApiResourceStateProvider::class,
    security: 'is_granted("ROLE_ADMIN")'
)]
class AudienceApiResource
{
    #[API\ApiProperty(identifier: true, writable: false)]
    public int $id;

    /**
     * A self-descriptive name for the Users described here.
     */
    #[Assert\NotBlank()]
    public string $name;

    /**
     * Criteria used to select the target Users.
     * 
     * @var AudienceCriteria[]
     */
    #[Assert\NotBlank()]
    #[API\ApiProperty(
        openapiContext: [
            'example' => [
                [
                    'subject' => 'user.dateCreated',
                    'operator' => 'gt',
                    'referent' => '2026-01-01'
                ]
            ],
        ],
    )]
    public array $criteria;
}
