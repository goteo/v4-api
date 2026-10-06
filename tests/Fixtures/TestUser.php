<?php

namespace App\Tests\Fixtures;

use App\Entity\User\User;
use App\Factory\User\UserFactory;
use Zenstruck\Foundry\Test\Factories;

final class TestUser
{
    use Factories;

    private const EMAIL = 'test@example.com';

    public static function get(): User
    {
        $existing = UserFactory::repository()->findOneBy([
            'email' => self::EMAIL,
        ]);

        if ($existing) {
            return $existing;
        }

        return UserFactory::new()
            ->create([
                'email' => self::EMAIL,
                'handle' => 'test_user',
                'password' => 'T35tU53rP455w0rd',
                'roles' => ['ROLE_USER'],
            ])
        ;
    }
}
