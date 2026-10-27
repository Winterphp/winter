<?php

declare(strict_types=1);

namespace Winter\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Winter\Config\Config;
use Winter\Config\ConfigurationException;
use Winter\Http\HttpException\AuthenticationException;

final class JwtTokenService
{
    public function __construct(private readonly Config $config)
    {
    }

    public function create(UserInterface $user): string
    {
        $now = time();

        return JWT::encode([
            'sub' => $user->getIdentifier(),
            'roles' => $user->getRoles(),
            'iat' => $now,
            'exp' => $now + $this->ttl(),
        ], $this->secret(), $this->algorithm());
    }

    /**
     * @return array<string, mixed>
     */
    public function decode(string $token): array
    {
        try {
            /** @var array<string, mixed> $payload */
            $payload = (array) JWT::decode($token, new Key($this->secret(), $this->algorithm()));
        } catch (AuthenticationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new AuthenticationException('Invalid or expired token.', previous: $exception);
        }

        return $payload;
    }

    private function secret(): string
    {
        $secret = $this->config->string('jwt.secret');

        if ($secret === null || strlen($secret) < 32) {
            throw new ConfigurationException('JWT_SECRET must be set to at least 32 characters to use JWT authentication.');
        }

        return $secret;
    }

    private function algorithm(): string
    {
        $algorithm = strtoupper($this->config->string('jwt.algo', 'HS256') ?? 'HS256');

        if (!\in_array($algorithm, ['HS256', 'HS384', 'HS512'], true)) {
            throw new ConfigurationException(sprintf(
                'JWT algorithm "%s" is not supported. Use HS256, HS384 or HS512.',
                $algorithm,
            ));
        }

        return $algorithm;
    }

    private function ttl(): int
    {
        return max(60, (int) $this->config->get('jwt.ttl', 3600));
    }
}
