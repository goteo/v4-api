<?php

namespace App\ApiResource\Matchfunding;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\ApiResource\MoneyInput;
use App\Entity\Matchfunding\MatchAgainst;
use App\Entity\Matchfunding\MatchStrategy;
use App\Mapping\Transformer\MatchFormulaMapTransformer;
use App\Mapping\Transformer\MatchRulesMapTransformer;
use App\State\ApiResourceStateProvider;
use App\State\Matchfunding\MatchStrategyStateProcessor;
use AutoMapper\Attribute\MapFrom;
use AutoMapper\Attribute\MapTo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A MatchStrategy defines how will a MatchCall perform the match-making process.
 * \
 * The match-making flow is:
 * 1. A Project accepted in a MatchCall receives a successful Charge.
 * 2. The match-making loads the MatchCall's MatchStrategies.
 * 3. For each MatchStrategy the rules are executed, if one rule fails the match-making is cancelled for the strategy.
 * 3. The first valid MatchStrategy's formula function is passed the respective limit, factor and money of the MatchAgainst.
 * 4. The result of the MatchStrategy formula function execution is put in a Transaction from the MatchCall to the Project.
 */
#[API\ApiResource(
    shortName: 'MatchStrategy',
    stateOptions: new Options(entityClass: MatchStrategy::class),
    provider: ApiResourceStateProvider::class,
    processor: MatchStrategyStateProcessor::class,
)]
#[API\GetCollection()]
#[API\Post()]
#[API\Get()]
#[API\Patch(security: 'is_granted("MATCHCALL_EDIT", object.call)')]
#[API\Delete(security: 'is_granted("MATCHCALL_EDIT", object.call)')]
class MatchStrategyApiResource
{
    #[API\ApiProperty(identifier: true, writable: false)]
    public int $id;

    /**
     * The MatchCall to which this strategy belongs to.
     */
    #[Assert\NotBlank()]
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public MatchCallApiResource $call;

    /**
     * The ranking is the index order of this strategy among the others of the MatchCall
     * when this is up for match making. The ranking value of items will be sorted
     * after each change in the number of strategies or the ranking values.
     */
    #[Assert\PositiveOrZero()]
    #[API\ApiFilter(OrderFilter::class)]
    public int $ranking = 0;

    /**
     * The MatchRules used to decide if the match making strategy should be executed or not.
     *
     * @var RuleApiResource[]
     */
    #[MapTo(MatchStrategy::class, property: 'ruleNames', transformer: [self::class, 'rulesToNames'])]
    #[MapFrom(MatchStrategy::class, property: 'ruleNames', transformer: MatchRulesMapTransformer::class)]
    public array $rules;

    /**
     * The MatchFormula used to calculate matched funds.
     */
    #[Assert\NotBlank()]
    #[MapTo(MatchStrategy::class, property: 'formulaName', transformer: 'source.formula.name')]
    #[MapFrom(MatchStrategy::class, property: 'formulaName', transformer: MatchFormulaMapTransformer::class)]
    public FormulaApiResource $formula;

    /**
     * The assigned maximum amount of funding that will be given by the MatchFormula per operation.
     */
    #[Assert\NotBlank()]
    #[Assert\Valid()]
    public MoneyInput $limit;

    /**
     * The `x` factor used to calculate the resulting match of funds with the MatchFormula.
     */
    #[Assert\NotBlank()]
    #[Assert\Positive()]
    public float $factor;

    /**
     * The money to be matched by the formula is
     * - `charge` the money in the Charge item
     * - `budget_min` the minimum in the Project's budget
     * - `budget_opt` the optimum in the Project's budget.
     */
    public MatchAgainst $against = MatchAgainst::DEFAULT;

    /**
     * @param array<int, RuleApiResource>
     *
     * @return array<string>
     */
    public static function rulesToNames(array $values)
    {
        return \array_map(fn($r) => $r->name, $values);
    }
}
