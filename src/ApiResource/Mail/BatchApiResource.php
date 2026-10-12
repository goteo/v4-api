<?php

namespace App\ApiResource\Mail;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\Entity\Address;
use App\Entity\Mail\Batch;
use App\Entity\Mail\BatchStatus;
use App\State\ApiResourceStateProcessor;
use App\State\ApiResourceStateProvider;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * MailBatches store bulk emailing jobs.
 */
#[API\ApiResource(
    shortName: 'MailBatch',
    stateOptions: new Options(entityClass: Batch::class),
    processor: ApiResourceStateProcessor::class,
    provider: ApiResourceStateProvider::class,
    security: 'is_granted("ROLE_ADMIN")'
)]
class BatchApiResource
{
    #[API\ApiProperty(identifier: true, writable: false)]
    public int $id;

    /**
     * The MailAudience receiving this mail.
     */
    #[Assert\NotBlank()]
    public AudienceApiResource $audience;

    /**
     * Sender email address.
     */
    #[Assert\NotBlank()]
    public string $sender;

    /**
     * Email subject.
     */
    #[Assert\NotBlank()]
    public string $subject;

    /**
     * Rich-text available message body. You can include `{{variables}}`, to be interpolated with recipient's data.
     */
    #[Assert\NotBlank()]
    #[API\ApiProperty(
        openapiContext: [
            'type' => 'string',
            'example' => 'Hi {{ recipient.firstName }}',
        ],
    )]
    public string $body;

    /**
     * The status of the batch job.\
     * `in_sending` and `sent` can only be set by the job processor.
     */
    #[Assert\Choice(choices: [BatchStatus::InDraft, BatchStatus::ToSending])]
    public BatchStatus $status = BatchStatus::InDraft;

    /**
     * The date at which the sending started,
     * a future date means the sending will start at that time.
     */
    #[Assert\GreaterThan('now')]
    public \DateTimeImmutable $scheduledAt;
}
