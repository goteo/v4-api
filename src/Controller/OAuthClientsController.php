<?php

namespace App\Controller;

use GuzzleHttp\Psr7\Uri;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Repository\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/')]
final class OAuthClientsController extends AbstractController
{
    public function __construct(
        private ClientManagerInterface $clientManager,
        private ClientRepository $clientRepository,
    ) {}

    #[Route('/', name: 'app_clients')]
    public function authorizationStart(): Response
    {
        $clients = $this->getActiveClients();

        return $this->render('clients/clients.html.twig', [
            'oauth_clients' => $clients,
        ]);
    }

    private function getActiveClients(): array
    {
        $clients = [];
        foreach ($this->clientManager->list(null) as $managedClient) {
            $activeClient = $this->clientRepository->getClientEntity($managedClient->getIdentifier());
            if (!$activeClient) {
                continue;
            }

            $clients[] = [
                'name' => $activeClient->getName(),
                'identifier' => $activeClient->getIdentifier(),
                'baseUri' => $this->getBaseUri($activeClient->getRedirectUri()),
            ];
        }

        return $clients;
    }

    private function getBaseUri(string|array $url): string
    {
        if (empty($url)) {
            return '';
        }

        if (is_array($url)) {
            $url = $url[0];
        }

        $uri = new Uri($url);

        return (string) $uri
            ->withPath('')
            ->withQuery('')
            ->withFragment('');
    }
}
