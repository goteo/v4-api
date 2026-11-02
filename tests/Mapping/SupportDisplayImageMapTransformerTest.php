<?php

namespace App\Tests\Mapping;

use App\Entity\Matchfunding\MatchCall;
use App\Entity\Project\Support;
use App\Mapping\Transformer\SupportDisplayImageMapTransformer;
use PHPUnit\Framework\TestCase;

class SupportDisplayImageMapTransformerTest extends TestCase
{
    public function testOwnerWithoutImageReturnsNull(): void
    {
        $call = new MatchCall();
        $support = (new Support())->setOrigin($call->getAccounting());

        $this->assertNull((new SupportDisplayImageMapTransformer())->transform(null, $support, []));
    }
}
