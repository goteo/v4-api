<?php

namespace App\Tests\Service;

use App\Service\UserService;
use App\Validator\UserHandle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\ResetDatabase;

class UserServiceTest extends KernelTestCase
{
    use ResetDatabase;

    public function testGeneratesValidUniqueHandles()
    {
        self::bootKernel();

        /** @var UserService */
        $userService = static::getContainer()->get(UserService::class);

        /** @var ValidatorInterface */
        $validator = static::getContainer()->get(ValidatorInterface::class);

        $handles = [];
        for ($i = 0; $i < 128; ++$i) {
            $handle = $userService->generateHandle();

            $this->assertCount(0, $validator->validate($handle, new UserHandle()));

            $handles[] = $handle;
        }

        $this->assertSameSize($handles, \array_unique($handles));
    }
}
