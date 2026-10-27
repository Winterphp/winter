# Architecture

## Cycle d'une requête (PHP-FPM)

```tree
public/index.php
└── Application::run()
    ├── Kernel::boot()
    │   ├── Config::fromRoot()       .env + config/*.php + défauts
    │   ├── manifest.php             cache du scan (ou compilation)
    │   ├── ContainerBuilder         PHP-DI, compilé en prod
    │   ├── Router                   routes FastRoute (cache en prod)
    │   └── HttpKernel               middlewares + résolveurs d'arguments
    └── HttpKernel::handle(Request)
        ├── SecurityHeadersMiddleware   en-têtes nosniff / frame / referrer / HSTS / CSP
        ├── ErrorHandlerMiddleware      rendu HTML ou JSON de dernier recours
        ├── ExceptionAdviceMiddleware   #[ControllerAdvice] + #[ExceptionHandler]
        ├── SessionMiddleware           (sauf AUTH_MODE=jwt)
        ├── AuthenticationMiddleware    session et/ou JWT → SecurityContext
        ├── CsrfMiddleware              (formulaires)
        └── dispatch
            ├── KernelRequestEvent      #[EventListener]
            ├── Router::match()
            ├── AccessChecker           #[IsGranted]
            ├── ControllerInvoker       container + ArgumentResolver
            └── ResponseConverter       Response | View | JSON | 204
```

## Compilation : pourquoi c'est obligatoire

En Java, Spring lit les annotations au démarrage d'une JVM qui vit longtemps. En PHP-FPM, **chaque requête repart de zéro** : scanner `src/` par réflexion coûte 20–80 ms.

Winter applique donc la stratégie Symfony/PHP-DI :

1. `bin/console cache:compile` scanne `src/` et écrit `var/cache/manifest.php` (services, alias, beans, routes, `ConfigurationProperties`, entité `#[SecurityUser]`).
2. PHP-DI compile les définitions en code PHP (`var/cache/php-di/CompiledContainer_*.php`), protégé par opcache.
3. FastRoute écrit sa table de dispatch dans `var/cache/routes.php`.
4. Twig met ses templates en cache dans `var/cache/twig`.

En `APP_ENV=prod`, le manifeste est lu, jamais rescanné. En `dev`, il est recompilé dès qu'un fichier de `src/` (ou du compilateur) est plus récent que le cache.

Le manifeste enregistre aussi les **profils actifs**. Si les profils changent, le manifeste est recompilé et les caches `php-di` / `routes.php` sont purgés, car services et routes peuvent différer.

Après un déploiement : **`cache:compile`** (ou vider `var/cache`). Ne jamais servir `var/` publiquement.

## Résolution des dépendances

- Constructeur uniquement, par type ; autowiring PHP-DI pour les classes concrètes.
- Interface → alias unique ou `#[Primary]` ; sinon erreur **au build**, pas à la requête.
- Scalaire → `#[Value]` ou valeur par défaut, sinon erreur au build.
- Bean → `#[Bean]` sur une méthode de `#[Configuration]`.
- Portée : le container est reconstruit à chaque requête ; un service est un singleton **de requête**.

## Résolution des arguments de contrôleur

Ordre : `#[RequestBody]` → `#[PathVariable]` / variable de route → `#[RequestParam]` → `#[RequestHeader]` → `#[CookieValue]` → `#[CurrentUser]` → exception courante (avis) → service typé (`Request`, `Container` ou n'importe quel service) → valeur par défaut → 400 explicite.

## Avis d'exception

Les classes `#[ControllerAdvice]` sont scannées au build. Le manifeste conserve, pour chaque méthode `#[ExceptionHandler]`, la classe d'exception, l'ordre et le statut éventuel. À la requête, `ExceptionHandlerResolver` parcourt la classe exacte, puis ses parents, puis ses interfaces, et retient le premier avis trouvé ; `#[Order]` départage les candidats de même spécificité. Si aucun avis ne correspond, l'exception remonte au `ErrorHandler` générique.

Un avis est un service : ses dépendances de constructeur sont injectées, et ses paramètres de méthode suivent le même `ArgumentResolver` que les contrôleurs (exception, `Request`, services).

## Profils

`Config::profiles()` retourne `APP_ENV` complété par `APP_PROFILES`. Le compilateur filtre classes, beans et routes avant d'écrire le manifeste ; les fichiers `config/{clé}.{profil}.php` sont fusionnés après les fichiers de base. Le profil n'est pas modifiable par un fichier de configuration : il vient de l'environnement ou des surcharges explicites.

## Événements

`EventDispatcher` reçoit la liste compilée des `#[EventListener]` et résout chaque écouteur via le container, uniquement lorsqu'il est demandé. Les événements sont synchrones : `kernel.boot` après construction du noyau, `kernel.request` au début du dispatch, `kernel.terminate` après sauvegarde de session. Aucun événement asynchrone ni worker.

## En-têtes de sécurité

`SecurityHeadersMiddleware` est le middleware le plus externe : il ajoute `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, HSTS (HTTPS uniquement) et une CSP optionnelle. Il ne remplace jamais un en-tête déjà positionné par un contrôleur.

## Différences assumées avec Spring Boot

| Spring Boot | Winter v1 |
|---|---|
| Beans singleton partagés | singleton par requête |
| `@Transactional` par AOP | `TransactionRunner::run()` explicite |
| `@Scheduled`, `@Async` | absents (incompatibles avec PHP-FPM) |
| Classpath scan | scan filesystem + manifeste compilé |
| Profils Spring | `#[Profile]` + `APP_ENV` / `APP_PROFILES` + `config/*.{profil}.php` |
| `@EventListener` | `#[EventListener]`, synchrone |
| `@ControllerAdvice` | `#[ControllerAdvice]` / `#[RestControllerAdvice]` |
| Actuator | `debug:container`, `debug:config`, `route:list` |

## Décisions d'implémentation

- **PHP-DI** pour les définitions et la compilation ; Winter ajoute la couche de stéréotypes et valide les dépendances avant de compiler.
- **FastRoute** plutôt que Symfony Routing : empreinte minimale, cache intégré.
- **Doctrine 3** avec driver attributs sur tout `src/` : les entités peuvent vivre n'importe où.
- **Provider utilisateur paresseux** : la connexion base n'est ouverte qu'à la première authentification, pas au boot.
- **CSRF** limité aux formulaires (corps non-JSON) : les API JSON sans cookie restent utilisables.

## Limites de sécurité

- `APP_DEBUG=false` et `APP_ENV=prod` en production — les traces ne doivent jamais fuiter.
- `APP_SECRET` / `JWT_SECRET` (≥ 32 caractères) obligatoires ; JWT sans secret lève une erreur explicite.
- Cookies de session `httpOnly`, `SameSite=Lax`, `secure` en prod par défaut.
- `var/cache` hors du document root (`public/` uniquement).
