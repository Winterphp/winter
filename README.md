# Winter

Framework PHP 8 inspiré de **Spring Boot**, pensé pour un développeur qui aime les stéréotypes, l'injection par type et un seul dossier `src/` organisé librement — sans la structure imposée par Laravel.

```php
#[Controller]
#[Route('/articles')]
final class ArticleController
{
    public function __construct(private readonly ArticleService $articles) {}

    #[GetMapping('/{id}')]
    public function show(int $id): array
    {
        return ['article' => $this->articles->find($id)];
    }

    #[PostMapping('')]
    public function create(#[RequestBody] CreateArticle $request): array
    {
        return ['id' => $this->articles->create($request->title)->getId()];
    }
}
```

## Ce que fait Winter

- **Stéréotypes PHP 8** : `#[Component]`, `#[Service]`, `#[Repository]`, `#[Controller]`, `#[Configuration]`, `#[Bean]`.
- **Injection par constructeur** par type, avec `#[Primary]`, `#[Qualifier]` et `#[Value]`.
- **Container compilé** en PHP (`var/cache/php-di`) + manifeste scanné une fois : compatible PHP-FPM, pas de scan de réflexion à chaque requête.
- **Routing par attributs** (`#[Route]`, `#[GetMapping]`…) avec cache FastRoute.
- **HTTP** : Request/Response Symfony, middleware, `#[RequestBody]` → DTO validé, JSON automatique, erreurs HTML/JSON.
- **Avis d'exception** : `#[ControllerAdvice]`, `#[ExceptionHandler]`, `#[Order]`, `#[ResponseStatus]`.
- **Profils** : `#[Profile]` et surcharges `config/{clé}.{profil}.php`, activés par `APP_ENV` + `APP_PROFILES`.
- **Événements** : `ApplicationEventPublisher`, `#[EventListener]`, événements `kernel.boot`, `kernel.request`, `kernel.terminate`.
- **Doctrine ORM 3** : entités et repositories par attributs, `TransactionRunner`, migrations.
- **Sécurité** : sessions + cookies httpOnly, CSRF, JWT, `#[CurrentUser]`, `#[IsGranted]`, en-têtes de sécurité.
- **Twig**, **Monolog**, **Symfony Validator/Serializer**, **console** (`cache:compile`, `route:list`, `debug:container`, `db:migrate`…).
- **Requêtes** : `#[RequestHeader]` et `#[CookieValue]` en plus de `#[PathVariable]` et `#[RequestParam]`.

## Installation (monorepo)

```bash
composer install
composer test                     # Pest : framework + squelette
cd packages/skeleton
cp .env.example .env              # renseigner DATABASE_URL / JWT_SECRET
php bin/console cache:compile
php bin/console db:diff && php bin/console db:migrate
php -S 127.0.0.1:8000 -t public
```

## Structure d'une application

```tree
public/index.php       # 3 lignes
bin/console            # CLI
src/                   # TOUT le code, organisé comme vous voulez
templates/             # vues Twig
var/cache, var/log     # exécution
```

Aucun `app/`, `routes/`, `config/`, `database/` obligatoire. Un dossier `config/` optionnel (fichiers PHP retournant des tableaux) est chargé s'il existe.

## Commandes

| Commande | Rôle |
|---|---|
| `cache:compile` | Scanne `src/`, valide les dépendances, compile container + routes + Twig |
| `cache:clear` | Vide `var/cache` |
| `route:list` | Table des routes |
| `debug:container` | Services, beans et alias d'interfaces |
| `debug:config` | Configuration résolue (secrets masqués) |
| `make:entity Nom` | Entité Doctrine minimale dans `src/Entity` |
| `db:diff` / `db:migrate` / `db:status` | Doctrine Migrations (alias des commandes officielles) |

## Documentation

- [`docs/attributs.md`](docs/attributs.md) — référence de chaque attribut.
- [`docs/architecture.md`](docs/architecture.md) — cycle de requête, compilation, limites.
- [`docs/migration-depuis-laravel.md`](docs/migration-depuis-laravel.md) — correspondances Laravel → Winter.

## Limites assumées (v1)

- Pas d'AOP / proxies : pas de `#[Transactional]` par attribut — utiliser `TransactionRunner`.
- Portée des services : **singleton par requête** (PHP-FPM), pas de beans partagés entre requêtes.
- Pas de mode worker FrankenPHP/RoadRunner, ni mailer, queue, i18n.
- Pas de `@CrossOrigin` : à ajouter avec une politique CORS explicite.
- `JWT_SECRET` doit faire **au moins 32 caractères**.
- PHP **8.3+** ; testé sur PHP 8.4.

## Tests

```bash
composer test
vendor/bin/pest --testsuite=Framework
vendor/bin/pest --testsuite=Skeleton
```
