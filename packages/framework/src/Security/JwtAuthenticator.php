<?php

declare(strict_types=1);

namespace Winter\Security;

use Symfony\Component\HttpFoundation\Request;
use Winter\Http\HttpException\AuthenticationException;

final class JwtAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private readonly JwtTokenService $tokens,
        private readonly UserProviderInterface $provider,
    ) {
    }

    public function authenticate(Request $request): ?UserInterface
    {
        $header = (string) $request->headers->get('Authorization', '');

        if ($header === '') {
            return null;
        }

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches) !== 1) {
            throw new AuthenticationException('Authorization header must use the Bearer scheme.');
        }

        $payload = $this->tokens->decode(trim($matches[1]));
        $subject = $payload['sub'] ?? null;

        if (!\is_string($subject) || $subject === '') {
            throw new AuthenticationException('Token has no subject.');
        }

        $user = $this->provider->loadByIdentifier($subject);

        if ($user === null) {
            throw new AuthenticationException('Token subject does not exist anymore.');
        }

        return $user;
    }
}
