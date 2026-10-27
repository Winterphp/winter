<?php

declare(strict_types=1);

namespace Winter\Kernel;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;
use Winter\Config\Config;
use Winter\Container\Manifest;
use Winter\Data\DoctrineFactory;
use Winter\Data\DoctrineUserProvider;
use Winter\Data\TransactionRunner;
use Winter\Event\ApplicationEventPublisher;
use Winter\Event\EventDispatcher;
use Winter\Http\RouteGenerator;
use Winter\Logging\LoggerFactory;
use Winter\Security\AccessChecker;
use Winter\Security\CsrfService;
use Winter\Security\JwtAuthenticator;
use Winter\Security\JwtTokenService;
use Winter\Security\LazyUserProvider;
use Winter\Security\NullUserProvider;
use Winter\Security\PasswordHasher;
use Winter\Security\SecurityContext;
use Winter\Security\SessionAuthenticator;
use Winter\Security\SessionFactory;
use Winter\Security\UserProviderInterface;
use Winter\Serialization\SerializerFactory;
use Winter\Support\Paths;
use Winter\Validation\ValidatorFactory;
use Winter\View\TwigFactory;
use Winter\View\ViewRenderer;

final class FrameworkDefinitions
{
    /**
     * @return array<string, mixed>
     */
    public static function all(Config $config, Manifest $manifest, Paths $paths): array
    {
        $securityUser = array_key_first($manifest->securityUsers);
        $identifier = $securityUser !== null ? $manifest->securityUsers[$securityUser] : 'email';

        $userProvider = $securityUser !== null
            ? \DI\create(DoctrineUserProvider::class)->constructor(
                \DI\get(EntityManagerInterface::class),
                $securityUser,
                $identifier,
            )
            : \DI\create(NullUserProvider::class);

        return [
            'winter.config' => \DI\create(Config::class)->constructor($config->all()),
            Config::class => \DI\get('winter.config'),

            'winter.manifest' => \DI\create(Manifest::class)->constructor(
                $manifest->services,
                $manifest->aliases,
                $manifest->beans,
                $manifest->routes,
                $manifest->configProperties,
                $manifest->securityUsers,
                $manifest->scanPaths,
                $manifest->compiledAt,
                $manifest->profiles,
                $manifest->exceptionHandlers,
                $manifest->eventListeners,
            ),
            Manifest::class => \DI\get('winter.manifest'),

            Paths::class => \DI\create(Paths::class)->constructor($paths->root),

            LoggerInterface::class => \DI\factory([LoggerFactory::class, 'create']),
            EntityManagerInterface::class => \DI\factory([DoctrineFactory::class, 'create']),
            TransactionRunner::class => \DI\create(TransactionRunner::class)->constructor(\DI\get(EntityManagerInterface::class)),
            ValidatorInterface::class => \DI\factory([ValidatorFactory::class, 'create']),
            SerializerInterface::class => \DI\factory([SerializerFactory::class, 'create']),
            Environment::class => \DI\factory([TwigFactory::class, 'create']),

            EventDispatcher::class => \DI\create(EventDispatcher::class)->constructor(
                $manifest->eventListeners,
                \DI\get(\Psr\Container\ContainerInterface::class),
            ),
            ApplicationEventPublisher::class => \DI\create(ApplicationEventPublisher::class)->constructor(
                \DI\get(EventDispatcher::class),
            ),

            RouteGenerator::class => \DI\create(RouteGenerator::class)->constructor($manifest->routes),
            ViewRenderer::class => \DI\create(ViewRenderer::class)->constructor(\DI\get(Environment::class)),

            SecurityContext::class => \DI\create(SecurityContext::class),
            AccessChecker::class => \DI\create(AccessChecker::class),

            SessionInterface::class => \DI\factory([SessionFactory::class, 'create']),
            PasswordHasher::class => \DI\create(PasswordHasher::class),
            CsrfService::class => \DI\create(CsrfService::class)->constructor(\DI\get(SessionInterface::class)),
            JwtTokenService::class => \DI\create(JwtTokenService::class)->constructor(\DI\get(Config::class)),

            'winter.user_provider.real' => $userProvider,
            UserProviderInterface::class => \DI\create(LazyUserProvider::class)->constructor(
                \DI\get(\Psr\Container\ContainerInterface::class),
            ),

            SessionAuthenticator::class => \DI\create(SessionAuthenticator::class)->constructor(
                \DI\get(SessionInterface::class),
                \DI\get(UserProviderInterface::class),
                \DI\get(PasswordHasher::class),
            ),
            JwtAuthenticator::class => \DI\create(JwtAuthenticator::class)->constructor(
                \DI\get(JwtTokenService::class),
                \DI\get(UserProviderInterface::class),
            ),
        ];
    }
}
