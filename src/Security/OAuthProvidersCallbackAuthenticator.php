<?php

namespace App\Security;

use App\Entity\User\Person;
use App\Entity\User\User;
use App\OAuth\TokenOwner;
use App\Repository\User\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class OAuthProvidersCallbackAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private ClientRegistry $oauthProviders,
        private UserRepository $userRepository,
        private UserService $userService,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
        private UserPasswordHasherInterface $userPasswordHasher,
    ) {}

    public function supports(Request $request): ?bool
    {
        return \str_starts_with($request->getPathInfo(), '/oauth_providers/callback');
    }

    public function authenticate(Request $request): Passport
    {
        $providerKey = \basename($request->getPathInfo());
        $provider = $this->oauthProviders->getClient($providerKey);

        $accessToken = $provider->getAccessToken();
        /** @var TokenOwner */
        $tokenOwner = $provider->fetchUserFromToken($accessToken);
        $email = $tokenOwner->getEmail();

        $passport = new SelfValidatingPassport(new UserBadge($email, function () use ($email, $tokenOwner) {
            $user = $this->userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $user->setHandle($this->userService->sequentializeHandle($email));
                $user->setPassword($this->userPasswordHasher->hashPassword($user, \random_bytes(32)));

                $person = Person::for($user);
                $person->setFirstName($tokenOwner->getFirstName());
                $person->setLastName($tokenOwner->getLastName());
                $user->setPerson($person);

                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }

            return $user;
        }), []);

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $session = $request->getSession();

        if ($session->has('auth_request_uri')) {
            $uri = $session->get('auth_request_uri');
            $session->remove('auth_request_uri');

            return new RedirectResponse($uri);
        }

        return new RedirectResponse('/');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    //    public function start(Request $request, ?AuthenticationException $authException = null): Response
    //    {
    //        /*
    //         * If you would like this class to control what happens when an anonymous user accesses a
    //         * protected page (e.g. redirect to /login), uncomment this method and make this class
    //         * implement Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface.
    //         *
    //         * For more details, see https://symfony.com/doc/current/security/experimental_authenticators.html#configuring-the-authentication-entry-point
    //         */
    //    }
}
