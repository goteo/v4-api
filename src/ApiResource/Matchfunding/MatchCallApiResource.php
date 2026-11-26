<?php

namespace App\ApiResource\Matchfunding;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\ApiResource\Accounting\AccountingApiResource;
use App\ApiResource\User\UserApiResource;
use App\Entity\Matchfunding\MatchCall;
use App\Entity\Matchfunding\MatchCallStatus;
use App\Entity\Territory;
use App\State\ApiResourceStateProcessor;
use App\State\ApiResourceStateProvider;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A MatchCall is a managed event which accepts MatchCallSubmissions from Projects to receive *matchfunding* financement.
 * This means money going to a Project in a MatchCall can be matched with funds from the MatchCall accounting.
 * \
 * \
 * MatchCallSubmissions from Projects can be accepted or rejected by the managers.
 * \
 * \
 * How and when does a MatchCall match funds is determined by the MatchStrategy.
 * Everytime there is a Charge item going to a Project accepted in a MatchCall the strategy is evaluated for matching.
 * The strategy defines a series of rules that determine if the Charge is eligible for triggering the matching of funds.
 * When a Charge is to be matched the strategy uses a MatchFormula,
 * which is a predefined implementation for common mathematical operations used to match money.
 * The formula is fine-tuned in the strategy by defining the variables in the formula.
 */
#[API\ApiResource(
    shortName: 'MatchCall',
    stateOptions: new Options(entityClass: MatchCall::class),
    provider: ApiResourceStateProvider::class,
    processor: ApiResourceStateProcessor::class
)]
class MatchCallApiResource
{
    #[API\ApiProperty(identifier: true, writable: false)]
    public int $id;

    /**
     * The Accounting which holds and spends the funds for this MatchCall.
     */
    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public AccountingApiResource $accounting;

    /**
     * The MatchStrategies define the matching behaviour for this MatchCall.
     *
     * @var MatchStrategyApiResource[]
     */
    #[API\ApiProperty(writable: false)]
    public array $strategies;

    /**
     * A list of the MatchCallSubmissions received by this MatchCall.
     *
     * @var MatchCallSubmissionApiResource[]
     */
    #[API\ApiProperty(writable: false)]
    public array $submissions;

    /**
     * A list of Users who can modify this MatchCall.
     *
     * @var UserApiResource[]
     */
    public array $managers;

    /**
     * Main display title.
     */
    #[Assert\NotBlank()]
    #[API\ApiFilter(SearchFilter::class, strategy: 'partial')]
    public string $title;

    /**
     * Long-form secondary display text.
     */
    #[API\ApiFilter(SearchFilter::class, strategy: 'partial')]
    public string $description;

    /**
     * URL to the image or logo of this MatchCall.
     */
    #[Assert\Url()]
    public ?string $image = null;

    /**
     * Codes for the territory of interest in this MatchCall.
     */
    #[Assert\NotBlank()]
    #[Assert\Valid]
    #[API\ApiFilter(
        SearchFilter::class,
        strategy: 'exact',
        properties: ['territory.country', 'territory.subLvl1', 'territory.subLvl2'],
    )]
    public Territory $territory;

    /**
     * The current status of the MatchCall.
     */
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public MatchCallStatus $status = MatchCallStatus::InEditing;
}
