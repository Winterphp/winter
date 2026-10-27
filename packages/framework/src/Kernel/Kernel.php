<?php

declare(strict_types=1);

namespace Winter\Kernel;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;
use Twig\TwigFunction;
use Winter\Config\Config;
use Winter\Container\Compiler;
use Winter\Container\Container;
use Winter\Container\ContainerBuilder;
use Winter\Container\Manifest;
use Winter\Event\ApplicationEventPublisher;
use Winter\Event\KernelBootEvent;
use Winter\Event\KernelTerminateEvent;
use Winter\Http\Argument\CookieValueResolver;
use Winter\Http\Argument\CurrentUserResolver;
use Winter\Http\Argument\DefaultValueResolver;
use Winter\Http\Argument\ExceptionValueResolver;
use Winter\Http\Argument\PathVariableResolver;
use Winter\Http\Argument\RequestBodyResolver;
use Winter\Http\Argument\RequestHeaderResolver;
use Winter\Http\Argument\RequestParamResolver;
use Winter\Http\Argument\ServiceResolver;
use Winter\Http\ArgumentResolver;
use Winter\Http\ControllerInvoker;
use Winter\Http\Error\ErrorHandler;
use Winter\Http\Error\HtmlErrorRenderer;
use Winter\Http\Error\JsonErrorRenderer;
use Winter\Http\ExceptionHandlerResolver;
use Winter\Http\ResponseConverter;
use Winter\Http\Router;
use Winter\Kernel\Middleware\AuthenticationMiddleware;
use Winter\Kernel\Middleware\CsrfMiddleware;
use Winter\Kernel\Middleware\ErrorHandlerMiddleware;
use Winter\Kernel\Middleware\ExceptionAdviceMiddleware;
use Winter\Kernel\Middleware\SecurityHeadersMiddleware;
use Winter\Kernel\Middleware\SessionMiddleware;
use Winter\Security\AccessChecker;
use Winter\Security\CsrfService;
use Winter\Security\JwtAuthenticator;
use Winter\Security\SecurityContext;
use Winter\Security\SessionAuthenticator;
use Winter\Support\Paths;
use Winter\View\ViewRenderer;

final class Kernel
{
    /**
     * @param array<string, mixed> $options
     */
    private function __construct(
        private readonly Config $config,
        private readonly Paths $paths,
        private readonly Manifest $manifest,
        private readonly Container $container,
        private readonly Router $router,
        private readonly HttpKernel $http,
        private readonly ApplicationEventPublisher $events,
    ) {
    }

    /**
     * @param array{compile?: bool, cache_dir?: string} $options
     */
    public static function boot(string $rootDir, ?Config $config = null, array $options = []): self
    {
        $paths = new Paths($rootDir);
        $config ??= Config::fromRoot($rootDir);

        $cacheDir = isset($options['cache_dir']) && \is_string($options['cache_dir'])
            ? $options['cache_dir']
            : $paths->cache();

        $config = $config->with(['cache' => ['dir' => $cacheDir]]);

        $debug = $config->bool('app.debug', false);
        $forceCompile = (bool) ($options['compile'] ?? false);
        $profiles = $config->profiles();

        $manifest = self::loadManifest($paths, $config, $cacheDir, $forceCompile, $profiles);

        if ($forceCompile) {
            self::clearDirectory($cacheDir . '/php-di');
        }

        $definitions = FrameworkDefinitions::all($config, $manifest, $paths);
        $container = (new ContainerBuilder($config))->build(
            $manifest,
            $definitions,
            !$debug || $forceCompile,
            $cacheDir . '/php-di',
        );

        $router = new Router($manifest, $debug, $cacheDir . '/routes.php');
        $logger = $container->instance(LoggerInterface::class, LoggerInterface::class);
        $twig = $container->instance(Environment::class, Environment::class);
        $serializer = $container->instance(SerializerInterface::class, SerializerInterface::class);
        $validator = $container->instance(ValidatorInterface::class, ValidatorInterface::class);
        $security = $container->instance(SecurityContext::class, SecurityContext::class);
        $access = $container->instance(AccessChecker::class, AccessChecker::class);
        $events = $container->instance(ApplicationEventPublisher::class, ApplicationEventPublisher::class);

        $errors = new ErrorHandler(new HtmlErrorRenderer($twig), new JsonErrorRenderer(), $logger);
        $converter = new ResponseConverter($container->instance(ViewRenderer::class, ViewRenderer::class), $serializer);

        $arguments = new ArgumentResolver([
            new RequestBodyResolver($serializer, $validator),
            new PathVariableResolver(),
            new RequestParamResolver(),
            new RequestHeaderResolver(),
            new CookieValueResolver(),
            new CurrentUserResolver($security),
            new ExceptionValueResolver(),
            new ServiceResolver($container),
            new DefaultValueResolver(),
        ]);

        $advice = new ExceptionHandlerResolver($manifest->exceptionHandlers, $container, $arguments, $converter);

        $middleware = [
            new SecurityHeadersMiddleware(
                enabled: $config->bool('security.headers.enabled', true),
                frameOptions: $config->string('security.headers.frame_options', 'SAMEORIGIN') ?? 'SAMEORIGIN',
                hsts: $config->bool('security.headers.hsts', $config->string('app.env', 'dev') === 'prod'),
                csp: $config->string('security.headers.csp'),
            ),
            new ErrorHandlerMiddleware($errors, $debug),
            new ExceptionAdviceMiddleware($advice),
        ];
        $mode = $config->string('security.mode', 'session') ?? 'session';
        $csrf = null;

        if ($mode !== 'jwt') {
            $middleware[] = new SessionMiddleware($container->instance(SessionInterface::class, SessionInterface::class));
            $csrf = $container->instance(CsrfService::class, CsrfService::class);
        }

        $middleware[] = new AuthenticationMiddleware(
            $security,
            $container->instance(SessionAuthenticator::class, SessionAuthenticator::class),
            $container->instance(JwtAuthenticator::class, JwtAuthenticator::class),
            $mode,
        );

        if ($csrf !== null && $config->bool('security.csrf', true)) {
            $middleware[] = new CsrfMiddleware($csrf);
        }

        if ($csrf !== null) {
            $twig->addFunction(new TwigFunction(
                'csrf_token',
                static fn (string $id = 'default'): string => $csrf->token($id),
            ));
        }

        $twig->addFunction(new TwigFunction('current_user', static fn (): ?object => $security->user()));

        $http = new HttpKernel(
            $router,
            new ControllerInvoker($container, $arguments),
            $converter,
            $access,
            $security,
            $middleware,
            $events,
        );

        $events->publish(new KernelBootEvent());

        return new self($config, $paths, $manifest, $container, $router, $http, $events);
    }

    public function handle(Request $request): Response
    {
        return $this->http->handle($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($request->hasSession() && $request->getSession()->isStarted()) {
            $request->getSession()->save();
        }

        try {
            $this->events->publish(new KernelTerminateEvent($request, $response));
        } catch (\Throwable $exception) {
            $logger = $this->container->get(LoggerInterface::class);

            if ($logger instanceof LoggerInterface) {
                $logger->error('Kernel terminate listener failed.', ['exception' => $exception]);
            }
        }
    }

    /**
     * Compile le manifeste, les routes et le conteneur (à lancer au déploiement).
     *
     * @return array{services: int, routes: int, cache: string}
     */
    public function compile(): array
    {
        $compiler = new Compiler();
        $manifest = $compiler->compile($this->scanPaths(), $this->config->profiles());
        $compiler->write($manifest, $this->paths->manifestFile());

        self::clearDirectory($this->paths->cache() . '/php-di');
        self::clearDirectory($this->paths->cache() . '/routes.php');

        (new ContainerBuilder($this->config))->build(
            $manifest,
            FrameworkDefinitions::all($this->config, $manifest, $this->paths),
            true,
            $this->paths->cache() . '/php-di',
        );

        return [
            'services' => \count($manifest->services) + \count($manifest->beans),
            'routes' => \count($manifest->routes),
            'cache' => $this->paths->cache(),
        ];
    }

    public function clearCache(): void
    {
        self::clearDirectory($this->paths->cache());
        $this->paths->ensure($this->paths->cache());
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function manifest(): Manifest
    {
        return $this->manifest;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function paths(): Paths
    {
        return $this->paths;
    }

    /**
     * @return list<string>
     */
    public function scanPaths(): array
    {
        /** @var list<string> $paths */
        $paths = $this->config->get('app.scan_paths', [$this->paths->src()]);

        return array_values(array_filter($paths, static fn (mixed $path): bool => \is_string($path)));
    }

    /**
     * @param list<string> $profiles
     */
    private static function loadManifest(Paths $paths, Config $config, string $cacheDir, bool $force, array $profiles): Manifest
    {
        $cache = $cacheDir . '/manifest.php';
        $debug = $config->bool('app.debug', false);

        if (!$force && is_file($cache) && (!$debug || self::manifestIsFresh($cache, $config, $paths))) {
            $data = require $cache;

            if (\is_array($data)) {
                $manifest = Manifest::fromArray($data);

                if ($manifest->profiles === $profiles) {
                    return $manifest;
                }

                // Le profil actif a changé : les routes et le container compilés ne sont plus valides.
                self::clearDirectory($cacheDir . '/php-di');
                self::clearDirectory($cacheDir . '/routes.php');
            }
        }

        $compiler = new Compiler();

        /** @var list<string> $scanPaths */
        $scanPaths = $config->get('app.scan_paths', [$paths->src()]);

        $manifest = $compiler->compile($scanPaths, $profiles);
        $compiler->write($manifest, $cache);

        return $manifest;
    }

    private static function manifestIsFresh(string $cacheFile, Config $config, Paths $paths): bool
    {
        $cacheTime = filemtime($cacheFile);

        if ($cacheTime === false) {
            return false;
        }

        $frameworkFiles = [
            __DIR__ . '/../Container/Compiler.php',
            __DIR__ . '/../Container/ClassScanner.php',
            __DIR__ . '/../Container/Manifest.php',
        ];

        foreach ($frameworkFiles as $frameworkFile) {
            $mtime = filemtime($frameworkFile);

            if ($mtime !== false && $mtime > $cacheTime) {
                return false;
            }
        }

        /** @var list<string> $scanPaths */
        $scanPaths = $config->get('app.scan_paths', [$paths->src()]);

        foreach ($scanPaths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            );

            /** @var \SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php' && $file->getMTime() > $cacheTime) {
                    return false;
                }
            }
        }

        return true;
    }

    private static function clearDirectory(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }

        @rmdir($path);
    }
}
