# Publier une version

Winter suit un modèle **monorepo + dépôts générés** :

| Dépôt | Rôle | Source |
|---|---|---|
| `winterphp/winter` | source de vérité (non publiée sur Packagist) | — |
| `winterphp/framework` | paquet Packagist `winterphp/framework` | `packages/framework/` |
| `winterphp/skeleton` | paquet Packagist `winterphp/skeleton` | `packages/skeleton/` |
| `winterphp/docs` | documentation | `docs/` |

> **Règle absolue : on ne committe jamais dans un dépôt généré.**
> `framework`, `skeleton` et `docs` sont réécrits (`--force`) à chaque publication depuis le monorepo. Une modification faite directement dans l'un de ces dépôts sera perdue.

## 1. Préparer

```bash
composer install
composer test                      # Framework + Skeleton, documents inclus
```

Mettre à jour les notes de version si nécessaire, puis committer sur `main`.

## 2. Vérifier ce qui sera publié

```bash
git ls-files | grep -E '(^|/)\.env$|/vendor/|/var/(cache|log)/|composer\.lock' && echo "ÉCHEC : fichier interdit" || echo "OK"
```

## 3. Générer les trois dépôts

```bash
git subtree split --prefix=packages/framework -b split/framework
git subtree split --prefix=packages/skeleton  -b split/skeleton
git subtree split --prefix=docs               -b split/docs

git push --force framework split/framework:main
git push --force skeleton  split/skeleton:main
git push --force docs      split/docs:main
```

(`framework`, `skeleton`, `docs` sont les remotes vers les dépôts `winterphp/*`.)

`docs` n'est pas publié sur Packagist : ce push suffit.

## 4. Tagger les paquets

Un tag git ne peut exister qu'une fois par dépôt : on tague localement sous un nom préfixé, puis on pousse vers le nom définitif.

```bash
git tag -a framework/v0.1.0 split/framework -m "winterphp/framework 0.1.0"
git tag -a skeleton/v0.1.0  split/skeleton  -m "winterphp/skeleton 0.1.0"

git push framework refs/tags/framework/v0.1.0:refs/tags/v0.1.0
git push skeleton  refs/tags/skeleton/v0.1.0:refs/tags/v0.1.0

git tag -d framework/v0.1.0 skeleton/v0.1.0
```

Avec le **GitHub Service Hook** activé sur Packagist, la version est disponible en quelques secondes. Sans hook, Packagist n'interroge les dépôts qu'une fois par semaine.

**Un tag publié est immuable** : pour corriger une version, publier `v0.1.1`, jamais re-tagger.

## 5. Ordre et portée des versions

1. `framework` d'abord — la CI du squelette l'installe depuis Packagist.
2. `skeleton` ensuite.
3. `docs` à tout moment.

Le squelette exige `winterphp/framework: ^0.1`. En publiant `v0.2.0`, mettre à jour cette contrainte dans `packages/skeleton/composer.json` **avant** de générer le split.

## 6. Vérifier

```bash
composer create-project winterphp/skeleton /tmp/winter-demo --no-interaction
composer require winterphp/framework -d /tmp/winter-demo
```
