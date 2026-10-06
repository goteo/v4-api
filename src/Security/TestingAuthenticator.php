<?php

namespace App\Security;

use App\Repository\User\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TestingAuthenticator extends AbstractAuthenticator
{
    public const string AUTH_HEADER = 'X-Test-Auth';

    public function __construct(
        private string $appEnv,
        private UserRepository $userRepository,
    ) {}

    public function supports(Request $request): ?bool
    {
        if (!\in_array($this->appEnv, ['test'])) {
            return false;
        }

        return $request->headers->has(self::AUTH_HEADER);
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $userId = $request->headers->get(self::AUTH_HEADER);

        $passport = new SelfValidatingPassport(new UserBadge($userId, function ($id) {
            return $this->userRepository->find($id);
        }), []);

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ], Response::HTTP_UNAUTHORIZED);
    }
}
