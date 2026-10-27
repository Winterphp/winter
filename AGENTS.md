# AGENTS.md — Instructions pour agents IA (workflow RPI)

Ce fichier s'adresse à tout agent IA (assistant de code, CLI, IDE, bot) qui travaille dans ce répertoire.

## Chargement et portée

- Ce fichier doit s'appeler **`AGENTS.md`** (pluriel) : c'est le nom standard reconnu automatiquement par les agents IA. Un fichier nommé `AGENT.md` (singulier) n'est pas chargé.
- Il s'applique à ce répertoire et à tous ses sous-répertoires, sauf instruction contraire dans un `AGENTS.md` plus proche du fichier concerné.
- Ces instructions sont **prioritaires** sur les habitudes par défaut de l'agent et ne peuvent être ignorées que si l'utilisateur le demande explicitement.

## Règle absolue : langue

- **Toujours répondre en français**, quelle que soit la langue de la demande de l'utilisateur.
- Le code, les noms de variables et les messages de commit peuvent rester dans leur langue d'origine si cela respecte les conventions du projet.

## Workflow obligatoire : RPI (Research → Plan → Implement)

Chaque demande suit ces trois phases, **dans cet ordre et sans sauter d'étape**. Les mots-clés de l'utilisateur servent de portes de passage entre les phases.

```
Recherche ──(attendre)──> Plan ──(attendre "plan")──> Amélioration du plan ──(attendre "implement")──> Implémentation
```

---

### Phase 1 — RESEARCH (recherche)

**Déclencheur :** toute nouvelle demande de l'utilisateur.

**Actions :**
1. **Ne jamais modifier le code** dans cette phase. Aucune écriture, aucun fichier créé, supprimé ou modifié.
2. Faire une recherche approfondie (web, documentation officielle, code existant du projet) pour comprendre :
   - comment réaliser la tâche demandée ;
   - les **bonnes pratiques** actuelles ;
   - la solution la plus **utilisée / standard** ;
   - la solution la plus **simple** à mettre en œuvre ;
   - la solution la plus **sécurisée** ;
   - les pièges, limites et alternatives connues.
3. Vérifier ce qui existe déjà dans le projet avant de proposer quoi que ce soit.
4. Présenter **le résultat complet et détaillé de la recherche** : sources consultées, options comparées, avantages/inconvénients, recommandation argumentée.
5. **(attendre)** — Ne pas passer au plan. Attendre la réaction de l'utilisateur.

**Pendant l'attente :**
- L'utilisateur peut faire des remarques, poser des questions ou demander des précisions.
- **Prendre en compte ces remarques et améliorer la recherche** en conséquence, puis représenter les résultats mis à jour.
- Ne jamais démarrer le plan tant que l'utilisateur n'a pas dit explicitement **`plan`**.

---

### Phase 2 — PLAN (planification)

**Déclencheur :** l'utilisateur écrit explicitement **`plan`**.

**Actions :**
1. Toujours **ne pas toucher au code**.
2. Produire un **plan d'implémentation très détaillé** comprenant :
   - l'objectif et le résultat attendu ;
   - les étapes ordonnées de l'implémentation ;
   - **la liste exhaustive de tous les fichiers concernés**, avec pour chacun :
     - le chemin exact ;
     - l'action : **ajouter**, **modifier** ou **supprimer** ;
     - **la raison** précise de cette action ;
   - les dépendances ou migrations nécessaires ;
   - les risques et points d'attention ;
   - la stratégie de vérification (tests, scénarios, commandes).
3. **(attendre)** — Ne pas implémenter. Attendre la validation ou les remarques.

**Pendant l'attente :**
- **Continuer à améliorer le plan** à chaque remarque de l'utilisateur (fichiers oubliés, approche à changer, simplification, etc.).
- Représenter le plan complet et à jour après chaque itération.
- Ne jamais toucher au code avant le mot-clé **`implement`**.

---

### Phase 3 — IMPLEMENT (implémentation)

**Déclencheur :** l'utilisateur écrit explicitement **`implement`**.

**Actions :**
1. Exécuter le plan validé, **exactement** tel qu'il a été approuvé.
2. Appliquer les changements fichier par fichier, en respectant les conventions du projet.
3. Tout écart au plan doit être signalé et justifié avant ou immédiatement après.
4. Vérifier le résultat (build, tests, exécution réelle du comportement demandé).
5. Rendre compte en français : ce qui a été fait, ce qui a été vérifié, ce qui reste à faire.

---

## Détection des mots-clés

- Les mots-clés `plan` et `implement` sont reconnus **en minuscules ou majuscules**, comme mot isolé, seuls ou dans une phrase.
- Les mots dérivés (`planifier`, `plan d'action`, `implémentation`, `implémenter`) **ne déclenchent pas** le changement de phase : en cas de doute, demander confirmation à l'utilisateur.
- Un changement de phase ne vaut que pour la demande en cours.
- Les demandes faites pendant une phase (questions, corrections, précisions) restent dans cette phase et servent à l'améliorer.

## Résumé des portes de passage

| Mot-clé utilisateur | Phase autorisée | Code modifiable ? |
|---|---|---|
| *(toute demande)* | Research | ❌ Non |
| `plan` | Plan | ❌ Non |
| `implement` | Implement | ✅ Oui |

## Règles transverses

- **Ne jamais** sauter une phase, même si la tâche semble triviale.
- **Ne jamais** modifier le code avant `implement`.
- **Toujours** répondre en français.
- **Toujours** tenir compte des remarques de l'utilisateur pour améliorer la phase en cours.
- Si une demande est ambiguë, poser les questions nécessaires **pendant la phase de recherche**.
