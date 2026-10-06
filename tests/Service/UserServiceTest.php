<?php

namespace App\Tests\Service;

use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

class UserServiceTest extends KernelTestCase
{
    use ResetDatabase;

    public function testGeneratesValidHandles()
    {
        self::bootKernel();

        /** @var UserService */
        $userService = static::getContainer()->get(UserService::class);

        for ($i = 0; $i < 1000; ++$i) {
            $handle = $userService->generateHandle();

            $this->assertMatchesRegularExpression('/^[a-z_]+_\d{4}$/', $handle);
            $this->assertLessThanOrEqual(30, \strlen($handle));
        }
    }
}
