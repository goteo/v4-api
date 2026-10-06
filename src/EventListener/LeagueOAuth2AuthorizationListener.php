<?php

namespace App\EventListener;

use League\Bundle\OAuth2ServerBundle\Event\AuthorizationRequestResolveEvent;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class LeagueOAuth2AuthorizationListener
{
    public const string AUTHORIZATION_RESULT = 'oauth2.authorization_result';

    public function __construct(
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    #[AsEventListener(event: OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE)]
    public function onAuthorizationRequestResolve(AuthorizationRequestResolveEvent $event): void
    {
        /** @var \App\Entity\OAuth2Client */
        $client = $event->getClient();

        if ($client->isConsented()) {
            $event->resolveAuthorization(true);

            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if ($request->getSession()->has(self::AUTHORIZATION_RESULT)) {
            $event->resolveAuthorization($request->getSession()->get(self::AUTHORIZATION_RESULT));
            $request->getSession()->remove(self::AUTHORIZATION_RESULT);

            return;
        }

        $event->setResponse(new RedirectResponse(
            $this->urlGenerator->generate('app_consent', $request->query->all())
        ));
    }
}
