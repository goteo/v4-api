<?php

namespace App\Factory\User;

use App\Entity\User\User;
use App\Entity\User\UserType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher) {}

    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array|callable
    {
        return $this->defaultsOptimized();
    }

    protected static function defaultsOptimized(): array|callable
    {
        return [
            'email' => 'user@example.com',
            'handle' => 'user',
            'password' => 'password123',
        ];
    }

    protected static function defaultsFull(): array|callable
    {
        return [
            'active' => self::faker()->boolean(),
            'dateCreated' => self::faker()->dateTime(),
            'dateUpdated' => self::faker()->dateTime(),
            'email' => self::faker()->email(),
            'emailConfirmed' => self::faker()->boolean(),
            'handle' => self::faker()->userName(),
            'migrated' => self::faker()->boolean(),
            'password' => self::faker()->password(),
            'roles' => [],
            'type' => UserType::Individual,
        ];
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(function (User $user) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
        });
    }

    /**
     * Create users with optimized or complete values ​​according to the parameter.
     */
    public static function createWithMode(int $count = 1, array $overrides = [], bool $optimized = false): void
    {
        $defaults = $optimized ? self::defaultsOptimized() : self::defaultsFull();
        self::createMany($count, array_merge($defaults, $overrides));
    }

    public function test(): self
    {
        return $this->with([
            'handle' => 'test_user',
            'email' => 'testuser@example.com',
            'password' => 'T3stUs3rP455w0rd',
        ]);
    }
}
