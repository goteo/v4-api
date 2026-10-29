<?php

namespace App\Tests\Entity\UserApi;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User\Organization;
use App\Entity\User\User;
use App\Factory\User\UserFactory;
use App\Tests\Traits\RequestingTestTrait;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SearchTest extends ApiTestCase
{
    use Factories;
    use ResetDatabase;
    use RequestingTestTrait;

    public function testSearchesAcrossOwnAndRelatedFields(): void
    {
        UserFactory::createOne(['handle' => 'by_email', 'email' => 'quixote@example.com']);

        UserFactory::new()
            ->afterInstantiate(fn(User $u) => $u->getPerson()->setLastName('Quixote'))
            ->create(['handle' => 'by_person', 'email' => 'person@example.com']);

        UserFactory::new()
            ->afterInstantiate(fn(User $u) => $u->setOrganization(Organization::for($u)->setLegalName('Quixote S.L.')))
            ->create(['handle' => 'by_organization', 'email' => 'organization@example.com']);

        UserFactory::createOne(['handle' => 'unrelated', 'email' => 'sancho@example.com']);

        $data = $this->request('GET', '/v4/users?q=quixote', [], Response::HTTP_OK)->toArray();

        $handles = array_column($data['member'], 'handle');
        sort($handles);

        $this->assertSame(['by_email', 'by_organization', 'by_person'], $handles);
        $this->assertSame(3, $data['totalItems']);
    }

    public function testEmailFilterMatchesByPrefix(): void
    {
        UserFactory::createOne(['handle' => 'quixote', 'email' => 'quixote@example.com']);
        $admin = UserFactory::createOne(['handle' => 'admin', 'email' => 'admin@example.com', 'roles' => ['ROLE_ADMIN']]);
        $options = ['headers' => $this->withAuthHeader($admin)];

        $data = $this->request('GET', '/v4/users?email=QUIX', $options, Response::HTTP_OK)->toArray();
        $this->assertSame(['quixote'], array_column($data['member'], 'handle'));

        $data = $this->request('GET', '/v4/users?email=example.com', $options, Response::HTTP_OK)->toArray();
        $this->assertSame(0, $data['totalItems']);
    }

    public function testEmailFilterIsAdminOnly(): void
    {
        $this->request('GET', '/v4/users?email=quix', [], Response::HTTP_FORBIDDEN);
        $this->request('GET', '/v4/users?email[]=quix', [], Response::HTTP_FORBIDDEN);
    }
}
