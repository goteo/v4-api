<?php

namespace App\Controller;

use App\EventListener\LeagueOAuth2AuthorizationListener;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/security')]
class SecurityController extends AbstractController
{
    public function __construct(
        private ClientManagerInterface $clients,
        private ClientRegistry $providers,
    ) {}

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'oauth_providers' => $this->providers->getEnabledClientKeys(),
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/consent', name: 'app_consent')]
    public function consent(Request $request): Response
    {
        if ($request->getMethod() === Request::METHOD_POST) {
            $request->getSession()->set(
                LeagueOAuth2AuthorizationListener::AUTHORIZATION_RESULT,
                $request->get('_consent') ?? false
            );

            return $this->redirectToRoute('oauth2_authorize', $request->query->all());
        }

        $client = $this->clients->find($request->query->get('client_id'));

        return $this->render('security/consent.html.twig', [
            'oauth_client' => $client->getName(),
        ]);
    }
}
