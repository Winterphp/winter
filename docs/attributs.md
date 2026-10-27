# Référence des attributs

Tous les attributs sont dans `Winter\Attribute`. `#[Service]`, `#[Repository]`, `#[Controller]` et `#[Configuration]` sont des spécialisations de `#[Component]` (scannées via `IS_INSTANCEOF`).

## Stéréotypes

### `#[Component(?string $name = null)]`
Classe gérée par le container. Le nom par défaut est le FQCN ; un nom explicite permet `#[Qualifier('nom')]`.

```php
#[Component('mailer.smtp')]
final class SmtpMailer implements Mailer {}
```

### `#[Service]`, `#[Repository]`, `#[Controller]`
Mêmes règles que `#[Component]`, sémantique métier. `#[Controller]` est requis pour que les routes publiques soient découvertes.

### `#[Configuration]` + `#[Bean(?string $name = null)]`
Fabrique de services. Les paramètres de la méthode sont injectés comme ceux d'un constructeur.

```php
#[Configuration]
final class MailConfiguration
{
    #[Bean]
    public function mailer(#[Value('mail.dsn')] string $dsn): Mailer { /* ... */ }

    #[Bean('mailer.logger')]
    public function loggerMailer(Mailer $mailer, LoggerInterface $logger): Mailer { /* ... */ }
}
```

Contraintes : type de retour classe obligatoire, identifiant unique. Les closures ne sont pas compilables — utilisez des méthodes.

## Injection

### `#[Value(string $key, mixed $default = null)]`
Sur un paramètre de constructeur ou une propriété publique. Résolu depuis `.env` / `config/*.php`.

```php
public function __construct(#[Value('app.name', 'Winter')] private string $name) {}
```

Sans défaut et clé absente → échec explicite au build du container.

### `#[Qualifier(string $name)]`
Choisit un service par nom quand plusieurs implémentations existent.

### `#[Primary]`
Sur une classe : implémentation par défaut de ses interfaces.

Règle d'alias : une interface déclarée dans le code scanné est liée automatiquement si elle a **une seule** implémentation. Plusieurs implémentations sans `#[Primary]` → erreur de compilation listant les candidats.

### `#[Autowired]`
Injection de propriété (échappatoire). Préférez le constructeur.

### `#[ConfigurationProperties(string $prefix)]`
Lie un sous-arbre de configuration à un objet typé (constructeur ou propriétés publiques).

```php
#[ConfigurationProperties('mail')]
final class MailSettings
{
    public function __construct(public readonly string $dsn, public readonly int $retries = 3) {}
}
```

## Web

### `#[Route(string $path, array|string $methods = ['GET'], ?string $name = null, ?string $produces = null)]`
Sur une classe (préfixe) ou une méthode. Les attributs de méthode se cumulent.

### `#[GetMapping]`, `#[PostMapping]`, `#[PutMapping]`, `#[PatchMapping]`, `#[DeleteMapping]`
Sucres de `#[Route]`. Signature : `(string $path, ?string $name = null, ?string $produces = null)`.

Nom par défaut : `home.index` pour `HomeController::index`. Un nom dupliqué échoue à la compilation.

### `#[PathVariable(?string $name = null)]`
Variable `{id}` de l'URL, castée vers `int`/`float`/`bool` selon le type du paramètre. Sans attribut, un paramètre dont le nom correspond à une variable de route est aussi résolu.

### `#[RequestParam(string $name, mixed $default = null)]`
Query string, avec cast scalaire et défaut.

### `#[RequestBody(bool $validate = true)]`
Désérialise le JSON (ou les données de formulaire) vers un DTO typé, puis applique les contraintes Symfony.

- JSON malformé → **400**
- contraintes violées → **422** avec `{error, message, details: {champ: [messages]}}`

### `#[Template(string $name, int $status = 200)]`
Rend un template Twig avec le tableau retourné comme contexte. Peut être posé sur la classe (défaut) ou la méthode (prioritaire).

### `#[CurrentUser]`
Injecte l'utilisateur authentifié (`Winter\Security\UserInterface`) ou `null`.

### `#[IsGranted(string $roles)]`
Sur classe ou méthode. `ROLE_ADMIN` ou `ROLE_ADMIN or ROLE_EDITOR`.

- non authentifié → **401** + `WWW-Authenticate`
- authentifié sans le rôle → **403**

## Données

`#[Entity]`, `#[Column]`, `#[Id]`… viennent de Doctrine ORM et ne sont pas réimplémentés.

### `#[Repository]`
Sur l'implémentation d'une interface, étendant `Winter\Data\Repository` :

```php
#[Repository]
final class DoctrineUserRepository extends Repository implements UserRepositoryInterface
{
    protected function entityClass(): string { return User::class; }
}
```

Le container injecte l'interface partout où elle est demandée.

### `#[SecurityUser(string $identifier = 'email')]`
Sur l'entité utilisateur. Elle doit implémenter `Winter\Security\UserInterface`. Une seule entité marquée par application.

## Middleware et cycle de requête

Ordre par défaut : `ErrorHandler` → `Session` (sauf mode JWT) → `Authentication` → `Csrf` → dispatch.

Le CSRF s'applique aux `POST/PUT/PATCH/DELETE` de formulaire ; les corps `application/json` en sont dispensés (API sans cookie). Jeton disponible dans Twig via `csrf_token()`.

## Gestion des erreurs

### `#[ControllerAdvice]` / `#[RestControllerAdvice]`
Sur une classe. Elle est automatiquement enregistrée comme service : son constructeur est injecté normalement. `#[RestControllerAdvice]` est un alias sémantique, le format de réponse restant déterminé par le résultat et la requête.

### `#[ExceptionHandler(MonException::class)]`
Sur une méthode publique non statique d'une classe `#[ControllerAdvice]`. La résolution choisit la classe d'exception la plus spécifique (classe exacte, puis parents, puis interfaces), et `#[Order]` départage les avis de même spécificité.

```php
#[RestControllerAdvice]
#[Order(10)]
final class ApiExceptionAdvice
{
    #[ExceptionHandler(NotFoundHttpException::class)]
    #[ResponseStatus(404)]
    public function notFound(NotFoundHttpException $exception, Request $request): array
    {
        return ['error' => 'not_found', 'path' => $request->getPathInfo()];
    }
}
```

La méthode peut recevoir l'exception, la `Request` et des services. Le résultat suit les mêmes règles que les contrôleurs : `Response`, `View`, tableau → JSON, `null` → 204.

### `#[ResponseStatus(int $status)]`
Sur une méthode d'avis : statut HTTP appliqué aux résultats qui ne sont pas déjà une `Response`.

### `#[Order(int $value)]`
Sur une classe ou une méthode d'avis. Plus la valeur est petite, plus l'avis est prioritaire. Pour les écouteurs, utiliser `#[EventListener(priority: ...)]`.

## Profils

### `#[Profile('dev')]`, `#[Profile(['dev', 'test'])]`, `#[Profile('!prod')]`
Sur une classe ou une méthode `#[Bean]`. Un élément de tableau (ou une liste séparée par des virgules) est actif si **au moins un** jeton correspond ; `!` inverse le test. Si la classe et la méthode portent un profil, les deux doivent être satisfaits.

Les profils actifs sont `APP_ENV` complété par `APP_PROFILES` (liste séparée par des virgules) :

```dotenv
APP_ENV=staging
APP_PROFILES=debug,metrics
```

Les fichiers `config/{clé}.{profil}.php` sont chargés après `config/{clé}.php` lorsque le profil est actif :

```tree
config/
  app.php          # toujours chargé
  app.prod.php     # chargé seulement si le profil prod est actif
  security.test.php
```

Un changement de profils invalide le manifeste et les caches `php-di` / `routes.php` ; après un déploiement, lancer `cache:compile`.

## Événements

### `#[EventListener(int $priority = 0)]`
Sur une méthode publique d'un service (la classe est automatiquement enregistrée si elle ne porte que des écouteurs). La méthode doit déclarer **exactement un paramètre** typé avec la classe de l'événement. Les écouteurs sont synchrones, triés par priorité décroissante.

```php
#[Component]
final class MailListener
{
    #[EventListener]
    public function onUserCreated(UserCreated $event): void { /* ... */ }
}
```

Publication :

```php
final class UserService
{
    public function __construct(private readonly ApplicationEventPublisher $events) {}

    public function create(string $email): void
    {
        // ...
        $this->events->publish(new UserCreated($email));
    }
}
```

Événements de cycle de vie disponibles : `KernelBootEvent`, `KernelRequestEvent`, `KernelTerminateEvent`. Un écouteur de `kernel.terminate` ne doit pas lever d'exception (elle serait journalisée sans modifier la réponse déjà envoyée).

## En-têtes et cookies de requête

### `#[RequestHeader(string $name, mixed $default = null)]`
### `#[CookieValue(string $name, mixed $default = null)]`
Sur un paramètre de contrôleur. Cast scalaire (`int`, `float`, `bool`, `array`) selon le type du paramètre. Sans défaut ni valeur par défaut PHP, l'absence renvoie **400**.

```php
#[GetMapping('/dashboard')]
public function dashboard(
    #[RequestHeader('X-Tenant', 'public')] string $tenant,
    #[CookieValue('theme', 'light')] string $theme,
): array {
    return ['tenant' => $tenant, 'theme' => $theme];
}
```
