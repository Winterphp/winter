<?php

declare(strict_types=1);

use Winter\Config\Config;
use Winter\Config\ConfigurationException;
use Winter\Security\JwtTokenService;
use Winter\Security\UserInterface;

function jwtUser(): UserInterface
{
    return new class implements UserInterface {
        public function getIdentifier(): string
        {
            return 'jane@example.com';
        }

        public function getRoles(): array
        {
            return ['ROLE_USER'];
        }

        public function getPasswordHash(): ?string
        {
            return null;
        }
    };
}

test('un secret trop court est refusé', function (): void {
    $service = new JwtTokenService(new Config(['jwt' => ['secret' => str_repeat('a', 31)]]));

    expect(fn (): string => $service->create(jwtUser()))
        ->toThrow(ConfigurationException::class);
});

test('un algorithme non supporté est refusé', function (): void {
    $service = new JwtTokenService(new Config([
        'jwt' => ['secret' => str_repeat('a', 32), 'algo' => 'none'],
    ]));

    expect(fn (): string => $service->create(jwtUser()))
        ->toThrow(ConfigurationException::class);
});

test('un jeton signé avec un secret valide est décodable', function (): void {
    $service = new JwtTokenService(new Config([
        'jwt' => ['secret' => str_repeat('a', 32), 'algo' => 'HS256', 'ttl' => 600],
    ]));

    $token = $service->create(jwtUser());
    $payload = $service->decode($token);

    expect($payload['sub'])->toBe('jane@example.com')
        ->and($payload['roles'])->toBe(['ROLE_USER']);
});
