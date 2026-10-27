# Venir de Laravel

| Laravel | Winter |
|---|---|
| `app/Http/Controllers` | n'importe où dans `src/`, classe `#[Controller]` |
| `routes/web.php` | `#[GetMapping('/')]` sur la méthode |
| `Route::get('/users/{id}', ...)` | `#[GetMapping('/users/{id}')]` + `int $id` |
| `app/Models/User.php` | `src/Entity/User.php` (`#[Entity]`, Doctrine) |
| Eloquent `User::find()` | `UserRepositoryInterface` injecté, `find()` |
| `app/Providers/AppServiceProvider` | `#[Configuration]` + `#[Bean]` |
| `config/app.php` | `.env` (+ `config/app.php` optionnel) |
| `app/Services` + container | `#[Service]` + autowiring constructeur |
| FormRequest + `$request->validate()` | DTO + `#[RequestBody]` + contraintes Symfony |
| `middleware('auth')` | `#[IsGranted('ROLE_USER')]` |
| `Auth::attempt()` | `SessionAuthenticator::attempt()` |
| `@csrf` | `{{ csrf_token() }}` |
| Blade | Twig |
| `php artisan migrate` | `php bin/console db:migrate` |
| `php artisan route:list` | `php bin/console route:list` |
| `php artisan config:cache` | `php bin/console cache:compile` |
| `php artisan make:model` | `php bin/console make:entity` |
| Facades (`Cache::get`) | injection de service (pas de façade) |
| `Storage`, `Queue`, `Mail` | non fournis en v1 |

## Philosophie

Laravel impose des dossiers et des façades. Winter n'impose qu'un namespace : `App\` mappé sur `src/`. Vous décidez si vous créez `Entity/`, `Repository/`, `Service/`, ou si vous regroupez tout par domaine (`src/User/...`).

Exemple de `src/` par domaine, parfaitement valide :

```tree
src/
  User/
    User.php
    UserRepository.php
    UserService.php
    UserController.php
  Billing/
    Invoice.php
    InvoiceController.php
```

## Ce qu'il faut accepter

- Une erreur d'injection est détectée à `cache:compile`, pas par un compilateur : lancez la commande avant chaque déploiement.
- Pas de `@Transactional` : encapsulez la logique dans `TransactionRunner`.
- Un service ne survit pas à la requête : pas de cache mémoire entre requêtes.
- Pas de façades : les dépendances arrivent par constructeur, ce qui rend les tests triviaux.
