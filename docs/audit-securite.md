# Audit de sécurité — Winter

**Date :** 26 octobre 2026
**Périmètre :** `packages/framework/src`, `packages/skeleton/src`, dépendances Composer.
**Méthode :** revue manuelle ciblée des surfaces d'authentification, session, CSRF, sérialisation, gestion d'erreurs, secrets et configuration ; recherches de motifs dangereux ; exécution de la suite de tests.

## Limites de l'audit

- **Semgrep n'est pas installé** dans l'environnement : aucun scan SAST automatisé n'a été exécuté.
- **Le réseau DNS est indisponible** : `composer audit` n'a pas pu interroger les avis de sécurité Packagist. La commande est à rejouer en CI ou sur un poste connecté.
- L'audit est donc une revue manuelle : il ne prétend pas couvrir l'intégralité des chemins d'exécution.

## Synthèse

| # | Constat | Gravité | Statut |
|---|---|---|---|
| 1 | Secret JWT accepté dès 16 caractères alors que la documentation exige 32 | basse | corrigé |
| 2 | Algorithme JWT non restreint (valeur de configuration arbitraire) | basse | corrigé |
| 3 | Message d'erreur de désérialisation exposant des détails internes au client | basse | corrigé |
| 4 | Absence d'en-têtes de sécurité par défaut | moyenne | corrigé |
| 5 | Échec de régénération de session silencieusement ignoré | basse | risque accepté, documenté |
| 6 | Exemption CSRF pour tout corps `application/json` | basse | risque accepté, à réévaluer si CORS est ajouté |
| 7 | Permissions `0775` sur les dossiers de cache | basse | risque accepté, consignes de déploiement |
| 8 | Pas de limitation de débit sur le formulaire de connexion | basse | hors périmètre framework |
| 9 | Mode debug activé par défaut hors `APP_ENV=prod` | info | consigne de déploiement |
| 10 | `.env.example` forçait `SESSION_SECURE=false`, y compris si copié en production | basse | corrigé |

## Détail des findings

### 1. Longueur minimale du secret JWT — corrigé

`Security/JwtTokenService.php` acceptait 16 caractères alors que `docs/architecture.md` et `.env.example` annoncent 32. Le minimum est désormais de **32 caractères**, avec une `ConfigurationException` explicite. Rupture volontaire pour les secrets de 16 à 31 caractères.

### 2. Algorithmes JWT — corrigé

`jwt.algo` était transmis tel quel à Firebase JWT. Il est désormais limité à `HS256`, `HS384` et `HS512` ; toute autre valeur échoue au démarrage.

### 3. Fuite de détail de désérialisation — corrigé

`Http/Argument/RequestBodyResolver.php` renvoyait `Unable to map JSON body: {message du serializer}`. Le message est maintenant générique (`Unable to map JSON body.`), l'exception d'origine restant disponible comme `previous` pour les logs.

### 4. En-têtes de sécurité — corrigé

Nouveau `Kernel/Middleware/SecurityHeadersMiddleware`, placé en tête de chaîne pour couvrir aussi les réponses d'erreur :

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Strict-Transport-Security` uniquement en HTTPS, si activé
- `Content-Security-Policy` optionnelle, désactivée par défaut pour ne pas casser les pages existantes

Configuration : `security.headers.enabled`, `security.headers.frame_options`, `security.headers.hsts`, `security.headers.csp`.

### 5. Régénération de session — risque accepté

`Security/SessionAuthenticator.php` tente `session->migrate(true)` mais ignore l'échec, car certains stockages ne supportent pas la régénération. Une fixation de session reste donc possible si le stockage échoue **et** que l'attaquant peut fixer l'identifiant avant connexion.

Recommandations : activer `session.use_strict_mode=1` côté PHP et utiliser un stockage de session qui implémente la régénération. Le comportement n'a pas été modifié pour ne pas casser les stockages existants.

### 6. CSRF et JSON — risque accepté

`Kernel/Middleware/CsrfMiddleware.php` exempte les requêtes `Content-Type: application/json`. Un formulaire HTML cross-origin ne peut pas envoyer ce type sans préflight CORS ; le risque est donc faible **tant qu'aucune politique CORS permissive n'est ajoutée**. `@CrossOrigin` n'est pas inclus dans cet incrément ; toute future ouverture CORS devra réévaluer cette exemption.

### 7. Permissions du cache — risque accepté

`Support/Paths::ensure()` crée les dossiers en `0775`. Le cache peut contenir des valeurs de configuration. Consigne : garder `var/` hors du document root, avec un utilisateur de service dédié, et restreindre les permissions au déploiement (`var/cache` en `0750` ou `0700` selon l'utilisateur PHP-FPM).

### 8. Limitation de débit — hors périmètre

Aucun mécanisme de limitation des tentatives de connexion n'est fourni. À traiter au niveau du reverse proxy (nginx `limit_req`) ou d'un middleware applicatif.

### 9. Mode debug — consigne

`APP_DEBUG` suit `APP_ENV` et vaut `true` par défaut hors `prod`. Une production mal configurée exposerait les traces. Consigne : `APP_ENV=prod` et `APP_DEBUG=false`, vérifiés au déploiement.

### 10. Cookie de session dans `.env.example` — corrigé

Le modèle contenait `SESSION_SECURE=false`, ce qui désactivait le cookie sécurisé même si le fichier était copié tel quel en production. La ligne est désormais commentée : sans valeur explicite, le défaut suit `APP_ENV` (`true` en `prod`, `false` sinon).

## Points vérifiés sans anomalie

- Requêtes Doctrine via `findOneBy` / DQL paramétré : pas d'injection SQL identifiée.
- Twig configuré avec `autoescape: 'html'`.
- Jetons CSRF de 32 octets et comparaison `hash_equals`.
- Mots de passe hachés en Argon2id (ou `PASSWORD_DEFAULT`), vérification via `password_verify`.
- Cookies de session `httpOnly`, `SameSite=Lax`, `secure` en production par défaut.
- Cookies de session non signés côté framework : s'appuyer sur le stockage PHP et `session.use_strict_mode`.
- Aucun `eval`, `unserialize` de données utilisateur, `exec` ou inclusion de chemin fourni par la requête dans le framework.

## Vérifications à rejouer

```bash
composer audit
semgrep --config p/owasp-top-ten packages/framework/src packages/skeleton/src
vendor/bin/pest --testsuite=Framework
vendor/bin/pest --testsuite=Skeleton
```
