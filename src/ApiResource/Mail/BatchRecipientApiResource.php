<?php

namespace App\ApiResource\Mail;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\ApiResource\User\UserApiResource;
use App\Entity\Mail\BatchRecipient;
use App\Entity\Mail\BatchRecipientStatus;
use App\State\ApiResourceStateProcessor;
use App\State\ApiResourceStateProvider;

/**
 * MailBatchRecipients represent delivery of a MailBatch to one MailAudience member.
 */
#[API\ApiResource(
    shortName: 'MailBatchRecipient',
    stateOptions: new Options(entityClass: BatchRecipient::class),
    processor: ApiResourceStateProcessor::class,
    provider: ApiResourceStateProvider::class,
    security: 'is_granted("ROLE_ADMIN")'
)]
#[API\GetCollection()]
#[API\Get()]
class BatchRecipientApiResource
{
    #[API\ApiProperty(identifier: true, writable: false)]
    public int $id;

    /**
     * The MailBatch.
     */
    public BatchApiResource $batch;

    /**
     * The target User.
     */
    public UserApiResource $user;

    /**
     * The status of the delivery.
     */
    public ?BatchRecipientStatus $status = null;

    /**
     * The date at which the last delivery attempt happened.
     */
    public ?\DateTimeImmutable $sentAt;

    /**
     * If an error happened during delivery, details will appear here.
     */
    public ?string $error;
}
