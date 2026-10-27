# Winter — framework

Framework PHP 8 inspiré de **Spring Boot** : stéréotypes par attributs, injection par constructeur, un seul dossier `src/` organisé librement.

```php
#[RestController]
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

## Installation

```bash
composer require winterphp/framework
```

Pour démarrer une application complète :

```bash
composer create-project winterphp/skeleton mon-app
```

## Contenu

- **Stéréotypes** : `#[Component]`, `#[Service]`, `#[Repository]`, `#[Controller]`, `#[RestController]`, `#[Configuration]`, `#[Bean]`.
- **Injection par type** : `#[Autowired]`, `#[Primary]`, `#[Qualifier]`, `#[Value]`, `#[ConfigurationProperties]`.
- **Container compilé** en PHP (`var/cache/php-di`) + manifeste scanné une fois : pas de réflexion à chaque requête.
- **Routing par attributs** (`#[Route]`, `#[GetMapping]`…) avec cache FastRoute.
- **HTTP** : Request/Response Symfony, middleware, `#[RequestBody]` → DTO validé, JSON automatique.
- **Avis d'exception** : `#[ControllerAdvice]`, `#[ExceptionHandler]`, `#[Order]`, `#[ResponseStatus]`.
- **Profils** : `#[Profile]` + surcharges `config/{clé}.{profil}.php`.
- **Événements** : `ApplicationEventPublisher`, `#[EventListener]`, `kernel.boot|request|terminate`.
- **Doctrine ORM 3** : entités/repositories par attributs, `TransactionRunner`, migrations.
- **Sécurité** : sessions httpOnly, CSRF, JWT, `#[CurrentUser]`, `#[IsGranted]`, en-têtes de sécurité.
- **Twig**, **Monolog**, Symfony Validator/Serializer, console (`cache:compile`, `route:list`, `debug:container`, `db:migrate`…).

## Tests

```bash
composer install
composer test
```

## Documentation

La référence complète (attributs, architecture, migration depuis Laravel, audit de sécurité) est publiée dans le dépôt [winterphp/docs](https://github.com/winterphp/docs).

## Limites assumées (v1)

- Pas d'AOP ni de proxies : pas de `#[Transactional]` par attribut, utiliser `TransactionRunner`.
- Services **singleton par requête** (modèle PHP-FPM), pas de beans partagés entre requêtes.
- Pas de mode worker FrankenPHP/RoadRunner, ni mailer, queue ou i18n.
- Pas de `@CrossOrigin` : ajouter une politique CORS explicite.
- `JWT_SECRET` doit faire **au moins 32 caractères**.
- PHP **8.3+**, testé sur PHP 8.4.

## Licence

MIT — voir [LICENSE](LICENSE).
