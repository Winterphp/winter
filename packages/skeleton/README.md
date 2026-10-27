# Application Winter

Squelette d'application pour le framework [Winter](https://github.com/winterphp/framework) : un seul dossier `src/`, organisé librement.

## Créer une application

```bash
composer create-project winterphp/skeleton mon-app
cd mon-app
cp .env.example .env      # renseigner DATABASE_URL et JWT_SECRET
php bin/console cache:compile
php bin/console db:migrate
php -S 127.0.0.1:8000 -t public
```

Ouvrez <http://127.0.0.1:8000>.

## Organisation livrée

```
src/
  Configuration/AppBeans.php         #[Configuration] + #[Bean]
  Controller/HomeController.php      page Twig
  Controller/SecurityController.php  login/logout session + CSRF
  Controller/Api/UserController.php  API JSON, #[RequestBody], #[IsGranted]
  Dto/CreateUserRequest.php          validation Symfony
  Entity/User.php                    #[Entity] + #[SecurityUser]
  Repository/                        interface + implémentation #[Repository]
  Service/UserService.php            #[Service]
templates/                           Twig
tests/                               Pest
```

Renommez, déplacez, fusionnez ces dossiers : rien n'est imposé. Le scanner ne lit que les attributs.

## Routes

| Méthode | Path | Accès |
|---|---|---|
| GET | `/` | public |
| GET/POST | `/login` | public (CSRF) |
| POST | `/logout` | session (CSRF) |
| GET | `/api/users` | `ROLE_ADMIN` |
| POST | `/api/users` | public |

```bash
curl -X POST http://127.0.0.1:8000/api/users \
  -H 'Content-Type: application/json' \
  -d '{"email":"jane@example.com","password":"motdepasse-solide"}'
```

## Profils et événements

Les profils supplémentaires se déclarent dans `.env` :

```dotenv
APP_ENV=dev
APP_PROFILES=staging,debug
```

Une classe ou un bean peut alors être conditionné par `#[Profile('staging')]`, et un fichier `config/app.staging.php` surcharge `config/app.php`. Les écouteurs `#[EventListener]` reçoivent les événements publiés via `ApplicationEventPublisher` ; les événements `kernel.boot`, `kernel.request` et `kernel.terminate` sont fournis.

## Tests

```bash
composer install
composer test
```

Deux suites (`Feature/DocsTest.php` et `Unit/CodeHighlighterTest.php`) vérifient la documentation du monorepo Winter : elles sont exclues ici et tournent dans la CI de [winterphp/winter](https://github.com/winterphp/winter).

## Documentation

- Référence des attributs, architecture, migration depuis Laravel : [winterphp/docs](https://github.com/winterphp/docs).

## Licence

MIT — voir [LICENSE](LICENSE).
