<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\OpenApi;

trait RedocExtensionsTrait
{
    private function getNonGroupTags(array $tags, array $groups)
    {
        $groupTags = \array_merge(...\array_map(fn($g) => $g['tags'], $groups));
        $nonGroupTags = \array_filter($tags, fn($t) => !\in_array($t['name'], $groupTags));

        return [...\array_map(fn($t) => $t['name'], $nonGroupTags)];
    }

    private function getTagGroups(OpenApi $openApi): array
    {
        $tags = $openApi->getTags();

        $groups = [
            [
                'name' => 'Users',
                'tags' => [
                    'User',
                    'Person',
                    'Organization',
                ],
            ],
            [
                'name' => 'Projects',
                'tags' => [
                    'Project',
                    'ProjectReview',
                    'ProjectReviewArea',
                    'ProjectReviewComment',
                    'ProjectReward',
                    'ProjectRewardClaim',
                    'ProjectBudgetItem',
                    'ProjectUpdate',
                    'ProjectSupport',
                    'ProjectCollaboration',
                    'ProjectCollaborationCandidacy',
                ],
            ],
            [
                'name' => 'Categorization',
                'tags' => [
                    'Category',
                    'Theme',
                ],
            ],
            [
                'name' => 'Matchfunding',
                'tags' => [
                    'MatchCall',
                    'MatchCallSubmission',
                    'MatchStrategy',
                    'MatchFormula',
                    'MatchRule',
                ],
            ],
            [
                'name' => 'Gateways',
                'tags' => [
                    'Gateway',
                    'GatewayCharge',
                    'GatewayCheckout',
                ],
            ],
            [
                'name' => 'Accounting',
                'tags' => [
                    'Accounting',
                    'AccountingBalancePoint',
                    'AccountingTransaction',
                ],
            ],
        ];

        return [
            ...$groups,
            [
                'name' => 'Other',
                'tags' => $this->getNonGroupTags($tags, $groups),
            ],
        ];
    }
}
