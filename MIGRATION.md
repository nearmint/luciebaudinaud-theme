# Migration de l'ancien thème vers `lb3`

Ce document décrit la procédure pour remplacer l'ancien thème par `lb3` sans perdre de contenu.

## Principes

- Les **contenus** (posts = films, pages, médias, catégories) ne changent pas. Ils sont indépendants du thème.
- Les **champs ACF** portent les mêmes noms que dans l'ancien thème (`cover`, `iframe`, `realisateur`, `duree`, `annee`, `gallery`, `galerie`, `texte_bio`, `texte_cv`, etc.) → récupération transparente.
- Les **strings de l'ancien textdomain** (`luciebaudinaud2`) ne sont pas portées ; le nouveau thème utilise `lb3`. Les traductions doivent être régénérées via Poedit (voir plus bas).

## Avant-déploiement : checklist

- [ ] **Backup complet** : base de données + dossier `wp-content/` (OVH manager ou plugin).
- [ ] Tester le nouveau thème sur un **environnement de staging**.
- [ ] Vérifier que tous les plugins suivants sont **activés** :
  - ACF Pro
  - ACF: Gallery Field
  - Polylang
  - ACF Options for Polylang (BeAPI)
- [ ] Vérifier la version PHP OVH : **8.1 minimum**, idéalement 8.3.

## Étape 1 — Déposer le thème sur le serveur

```
wp-content/themes/lb3/
```

Le dossier doit contenir `dist/` (buildé au préalable avec `npm run build`).

## Étape 2 — Activer le nouveau thème

Admin WP → **Apparence → Thèmes → lb3 → Activer**.

À cet instant, le site utilise `lb3`. L'ancien thème reste disponible (rollback immédiat si besoin).

## Étape 3 — Vérifier ACF

Les field groups sont déclarés de deux manières :

1. **acf-json** (automatique) : dès qu'ACF détecte le dossier `/acf-json`, il propose d'importer les field groups absents.
   - Aller dans **Custom Fields → Field Groups**.
   - Si un bandeau « Sync available » apparaît en haut : cliquer **Sync**.

2. **Déclarations PHP** (`inc/acf-fields.php`) : filet de sécurité. Les field groups sont disponibles même sans import manuel.

Les champs existants dans la base (`cover`, `iframe`, etc.) sont automatiquement reconnus car les noms correspondent.

## Étape 3.5 — Configurer les permaliens + Polylang URL

Pour que chaque film garde un slug propre dans sa langue (FR et EN) et que le SEO reste clean :

### Permaliens WordPress

**Réglages → Permaliens** → structure `/%postname%/`.

### Polylang → Langues → Réglages

Onglet **URL modifications** :

| Option | État |
|---|---|
| Le type d'URL utilisé est | « La langue est définie par un répertoire (ex: /en/) » |
| Cacher les URL pour la langue par défaut | **ON** — FR reste à la racine `/`, EN passe en `/en/` |
| Supprimer `/language/` de l'URL | **OFF** |
| Rediriger la langue détectée vers la langue par défaut | au choix (OFF conseillé pour respecter le navigateur) |
| Détecter la langue du navigateur | au choix |

### Slug par film

Chaque film doit avoir un **slug manuel par langue** (pas de traduction auto du slug) :
- FR : `/nom-du-film/` (ex : `/les-olympiades/`)
- EN : `/en/movie-title/` (ex : `/en/paris-13th-district/`)

Lucie saisit le slug depuis l'admin WP, champ « Permalien » sous le titre, dans chaque traduction du post. Voir `docs/GUIDE-LUCIE.md` section 5.

## Étape 4 — Configurer les traductions Polylang

### Strings du thème (templates)

Les strings wrappées en `__('texte', 'lb3')` sont traduites via fichiers `.po/.mo` dans `languages/`.

```bash
# Depuis le dossier du thème
wp i18n make-pot . languages/lb3.pot --domain=lb3
```

Ouvrir `languages/lb3.pot` dans **Poedit**, créer la traduction `en_US` :
- Poedit → File → New from POT/PO file → sélectionner `lb3.pot`
- Choisir langue : English (United States)
- Traduire les strings
- Sauvegarder sous `languages/lb3-en_US.po` (Poedit génère le `.mo` en parallèle)

### Strings dynamiques (labels admin)

Seule string actuelle enregistrée : `"Tout"` (filtre films, groupe `lb3`).

Admin WP → **Languages → Strings translations** → filtrer sur `lb3` → traduire.

Si d'autres strings dynamiques sont ajoutées, les enregistrer dans `inc/setup.php` :
```php
pll_register_string('identifiant', 'Texte FR', 'lb3');
```

## Étape 5 — Remplir les nouveaux champs ACF

Le nouveau thème introduit **un seul nouveau champ** sur la page d'accueil :

- **SEO : description (meta)** → remplir avec une description de 150-160 caractères pour la meta description.

Les autres champs optionnels (ajoutés dans `inc/acf-fields.php` mais non bloquants) :
- `social_instagram`, `social_imdb`, `social_vimeo` : URLs pour enrichir le JSON-LD Person.

Tous les **anciens champs** sont réutilisés tels quels.

## Étape 6 — Vider les caches

Après activation :
- Vider le cache OVH (si plugin actif)
- Vider le cache navigateur pour tester
- Polylang : **Languages → Strings translations → Save changes** (force reload)

## Étape 7 — Vérifications post-déploiement

### Vérification visuelle
- [ ] Home FR : hero, grille films, filtres, photos, CV, contact
- [ ] Home EN (`/en/`) : même rendu, contenu traduit
- [ ] Toggle FR/EN fonctionnel
- [ ] Single film : cover, play modal, titre/réa/durée/année, description, galerie photogrammes
- [ ] Filtres films par catégorie
- [ ] Ouverture modale vidéo + lecture + fermeture ESC
- [ ] Galerie photos : ouverture PhotoSwipe, navigation flèches, fermeture
- [ ] 404 : URL inexistante redirige bien

### Vérification technique
- [ ] HTML valide : `view-source:https://luciebaudinaud.com`, chercher `<!DOCTYPE html>` + ne pas avoir d'erreurs console
- [ ] `<title>`, meta description, Open Graph présents
- [ ] `hreflang` alternates FR/EN/x-default
- [ ] JSON-LD Person sur home + CreativeWork sur single (tester avec [Rich Results Test](https://search.google.com/test/rich-results))
- [ ] Umami : ouvrir une session, vérifier dans le dashboard Umami que la visite apparaît
- [ ] Umami events : cliquer lang_switch, filtre, play → les events apparaissent
- [ ] Lighthouse : Performance ≥ 90, Accessibility ≥ 95, SEO = 100

### Vérification SEO
- [ ] Google Search Console : soumettre le sitemap `https://luciebaudinaud.com/wp-sitemap.xml`
- [ ] Vérifier qu'aucune URL n'est cassée (Screaming Frog ou `wget --spider`)
- [ ] Inspecter 2-3 URL dans Search Console après 24h → pas d'erreur d'indexation

## Rollback

Si un problème critique apparaît :

1. Admin WP → **Apparence → Thèmes → (ancien thème) → Activer**.
2. Le site revient immédiatement à l'ancien thème (les contenus et ACF sont préservés).
3. Signaler le problème pour correction sur `lb3`.

Aucune modification de base de données n'est faite par `lb3`, donc le rollback est sans risque.

## Points d'attention connus

| Sujet | Détail |
|---|---|
| Classes de filtre | L'ancien thème utilisait `cat-{ID}` hardcodé. `lb3` utilise `cat-{slug}`. Les filtres fonctionnent donc via les slugs — si Lucie renomme le slug d'une catégorie, le filtre change (mais ça n'arrive jamais en pratique). |
| Galerie photos | Polylang : la galerie ACF est récupérée depuis la home FR quelle que soit la langue (`lb3_get_fr_front_page_id()`). Même logique que l'ancien thème. |
| Iframe embeds | Vimeo/YouTube : le code embed est stocké dans le WYSIWYG ACF `iframe`. Les `width`/`height` hardcodés sont automatiquement retirés (le wrapper `aspect-ratio` s'en charge). |
| Google Analytics UA | Retiré. Remplacé par Umami. Si des scripts GA tiers sont encore en place (Tag Manager…), les retirer aussi. |
| Bandeau RGPD | Umami ne pose pas de cookies → plus besoin de bandeau. Si un plugin CMP est actif, le désactiver. |

## Contacts

Questions sur la migration : Nicolas Rapp.
