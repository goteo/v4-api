<?php

namespace App\ApiResource\Mail;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\ApiResource\User\UserApiResource;
use App\Entity\Mail\Dispatch;
use App\Entity\Mail\DispatchStatus;
use App\State\ApiResourceStateProcessor;
use App\State\ApiResourceStateProvider;

/**
 * MailDispatchs represent delivery of a MailBroadcast to one MailAudience member.
 */
#[API\ApiResource(
    shortName: 'MailDispatch',
    stateOptions: new Options(entityClass: Dispatch::class),
    processor: ApiResourceStateProcessor::class,
    provider: ApiResourceStateProvider::class,
    security: 'is_granted("ROLE_ADMIN")'
)]
#[API\GetCollection()]
#[API\Get()]
class DispatchApiResource
{
    #[API\ApiProperty(identifier: true, writable: false)]
    public int $id;

    /**
     * The MailBroadcast.
     */
    public BroadcastApiResource $batch;

    /**
     * The target User.
     */
    public UserApiResource $user;

    /**
     * The status of the delivery.
     */
    public ?DispatchStatus $status = null;

    /**
     * The date at which the last delivery attempt happened.
     */
    public ?\DateTimeImmutable $sentAt;

    /**
     * If an error happened during delivery, details will appear here.
     */
    public ?string $error;
}
