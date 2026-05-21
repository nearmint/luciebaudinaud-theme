# Guide éditorial — luciebaudinaud.com

Ce guide explique comment gérer le site au quotidien depuis l'admin WordPress.
Tout se passe sur : `https://luciebaudinaud.com/wp-admin/`.

Identifiant / mot de passe : fournis séparément.

---

## 1. Ajouter un film

Un film = un **article** (WordPress appelle ça « Articles » dans le menu de gauche).

1. Menu gauche → **Articles → Ajouter**.
2. Remplir :
   - **Titre** : nom du film (affiché sur la home et la fiche film).
   - **Contenu** : synopsis ou notes de production (facultatif).
3. Dans la colonne de droite, sélectionner une ou plusieurs **catégories**
   (Longs métrages, Courts métrages, Clips, Documentaires…).
   Pour créer une nouvelle catégorie : bouton « Ajouter une catégorie ».
4. Renseigner l'**image mise en avant** (colonne de droite, en bas) : c'est la
   vignette affichée dans la grille de la home.
5. Descendre sous le contenu — le bloc « **Fiche film** » (ACF) apparaît :
   - **Image de couverture** : image fullscreen du haut de la fiche film (paysage ≥ 2400 px).
   - **URL de la bande-annonce** : coller **uniquement l'URL** de la vidéo. Exemples :
     - Vimeo : `https://vimeo.com/157932076` (sur la page Vimeo → bouton « Partager » → copier le lien)
     - YouTube : `https://www.youtube.com/watch?v=dQw4w9WgXcQ` (depuis la barre d'adresse)
     - YouTube court : `https://youtu.be/dQw4w9WgXcQ` accepté aussi

     Laisser vide si pas de bande-annonce. **Ne pas coller de code `<iframe>`** :
     le site génère l'iframe automatiquement, en mode respect de la vie privée
     (Vimeo DNT, YouTube-nocookie).
   - **Réalisation** : nom du réalisateur / réalisatrice.
   - **Durée** : format libre (« 90 min », « 1h30 »…).
   - **Année** : année de sortie.
   - **Galerie de photogrammes** : 6 à 15 photos haute déf (≥ 2000 px).

   > Ancien champ **« Embed vidéo (obsolète) »** : visible seulement sur les fiches
   > qui contiennent encore du code iframe legacy. Pour migrer :
   > 1. Récupérer l'URL Vimeo/YouTube depuis le code iframe (attribut `src="…"`).
   > 2. La coller dans le nouveau champ **URL de la bande-annonce**.
   > 3. Vider complètement l'ancien champ.
   > 4. Mettre à jour la fiche.
6. Bouton **Publier** en haut à droite.

→ Le film apparaît immédiatement sur la home et via son URL propre
(`/nom-du-film/`).

### Check-list avant publication

- [ ] Titre correct (sans fautes, accents inclus).
- [ ] Image mise en avant renseignée (grille home).
- [ ] Cover fullscreen renseignée (haut de la fiche).
- [ ] Réalisation / Durée / Année remplis.
- [ ] Au moins une catégorie cochée.
- [ ] URL de la bande-annonce testée (ou laissée vide si pas de bande-annonce).
- [ ] 6 photos minimum dans la galerie (ou aucune si pas dispo).
- [ ] Traduction EN créée (cf. section 5).

---

## 2. Ordonner mes films

L'ordre d'affichage des films sur le site suit la **date de publication**
(du plus récent au plus ancien).

Pour remonter un film en haut de la grille :

1. Menu gauche → **Articles**.
2. Survoler la ligne du film concerné → cliquer sur **« Modification rapide »**.
3. Ajuster le champ **Date** : mettre une date plus récente que celle des
   films à placer en-dessous.
4. Cliquer **« Mettre à jour »**.
5. Rafraîchir le site pour voir le nouvel ordre.

> Astuce : pour classer précisément deux films dans l'ordre souhaité, leur
> donner des dates à quelques minutes d'écart — ça suffit, personne ne voit
> la date, seul l'ordre compte.

---

## 3. Mettre à jour la galerie photos de la home

1. Menu gauche → **Pages → Toutes les pages** → cliquer sur la page
   d'accueil (celle marquée « Page d'accueil »).
2. Descendre dans le bloc « **Page d'accueil** » (ACF).
3. Section **Galerie photos** :
   - Ajouter : bouton « Ajouter à la galerie » → uploader ou sélectionner
     dans la médiathèque.
   - Supprimer : survoler une miniature, cliquer la corbeille.
   - Réordonner : glisser-déposer les miniatures.
4. **Mettre à jour**.

Les autres champs de la même page :

- **Bande séparatrice avant Photos / CV / Contact** : image pleine largeur
  affichée entre les sections.
- **Texte CV** : contenu affiché dans la section CV (structuré en rubriques).
- **Texte Bio / Contact** : bio + coordonnées (inclure un lien `mailto:` pour
  que les clics soient tracés).
- **CV au format PDF** : fichier PDF téléchargeable depuis la section CV.
- **Instagram / IMDb / Vimeo URL** : URLs complètes des profils (apparaissent
  dans le footer et les données structurées SEO).

---

## 4. Mettre à jour le CV

Deux zones à mettre à jour en parallèle :

### Le texte affiché sur la home

1. **Pages → Page d'accueil**.
2. Bloc ACF → champ **Texte CV**.
3. Éditer comme un document : structurer avec des titres de section (H2
   pour les grandes catégories, H3 pour les sous-rubriques), listes à puces
   pour les films, etc.
4. **Mettre à jour**.

### Le PDF téléchargeable

1. Dans la même page, champ **CV au format PDF**.
2. Cliquer « Ajouter un fichier », uploader le PDF.
3. Nommer le fichier de manière lisible (`lucie-baudinaud-cv-2026.pdf`).
4. Poids conseillé : ≤ 2 MB.
5. **Mettre à jour**.

Le bouton « Télécharger le CV (PDF) » apparaît automatiquement sous le texte
CV quand un fichier est renseigné. Les clics sont tracés dans Umami (événement
`cv_download`).

---

## 5. Ajouter une traduction EN

Le site est bilingue grâce au plugin **Polylang**. Chaque film et chaque page
existent en deux versions (FR et EN) liées entre elles.

### Traduire un film existant

1. Ouvrir le film FR (**Articles → Tous les articles**).
2. Dans le panneau latéral de droite, section « **Langue** » :
   - Langue actuelle : « Français ».
   - À côté de « English », cliquer l'icône « + » (créer une traduction).
3. Une nouvelle fiche s'ouvre, pré-remplie avec les mêmes champs ACF que
   le FR.
4. Traduire :
   - **Titre** (si différent en EN — sinon garder tel quel).
   - **Contenu** / description.
   - **Réalisation** n'a en général pas besoin d'être traduit.
   - **Durée** : adapter (« 90 min » ⟶ « 90 min », identique).
   - La **cover image**, l'**embed vidéo** et la **galerie** sont partagées :
     rien à refaire.
5. Choisir la bonne **catégorie EN** (si elle existe) ou créer la traduction
   de la catégorie.
6. **Publier**.

### Traduire une page

Même principe :

1. **Pages → Toutes les pages** → ouvrir la page FR.
2. Panneau latéral « Langue » → créer la version EN.
3. Traduire le contenu (et le champ `legal_content` pour les pages légales).
4. **Publier**.

### Switch FR / EN sur le site

Le bouton FR/EN en haut à droite du site bascule automatiquement — rien à
configurer côté contenu.

---

## 6. Autres éléments rapides

### Changer l'image de fond du hero (home)

**Pages → Page d'accueil** → champ **Hero : image de fond** → uploader.

### Changer la description SEO (Google)

**Pages → Page d'accueil** → champ **SEO : description (meta)** → 150-160
caractères.

### Bouton « lang » — marquer un fragment dans une autre langue

Quand un titre de film ou une citation est en anglais / italien / autre,
les lecteurs d'écran doivent le savoir pour prononcer correctement.

1. Dans un champ WYSIWYG (CV, Bio, contenu de film), **sélectionner** le texte
   concerné (ex : « The Tree of Life »).
2. Cliquer sur le bouton **lang** à droite de la barre d'outils (rangée 1).
3. Saisir le code langue :
   - `en` pour anglais
   - `it` pour italien
   - `de` pour allemand
   - `es` pour espagnol
   - etc. (codes BCP 47 — accepte aussi `en-US`, `pt-BR`…)
4. Valider → le texte est entouré d'un `<span lang="xx">`, invisible visuellement
   mais pris en compte par VoiceOver / NVDA.

À faire chaque fois qu'un morceau de texte n'est **pas dans la langue de la page**
(FR sur la version FR, EN sur la version EN).

### Ajouter les pages mentions légales / politique de confidentialité

1. **Pages → Ajouter**.
2. Titre : « Mentions légales » (FR) → le slug devient automatiquement
   `mentions-legales`. Pour l'EN, titre « Legal notice » → slug
   `legal-notice`.
3. Dans « Attributs de page » (panneau latéral) → **Modèle** → sélectionner
   **« Page légale »**.
4. Un champ **Contenu de la page** (ACF WYSIWYG) apparaît → y coller le texte.
5. **Publier**.
6. Répéter pour « Politique de confidentialité » / « Privacy policy ».

Les liens apparaissent automatiquement dans le footer du site.

### Ajouter une catégorie de films

**Articles → Catégories** → formulaire à gauche.

Le filtre correspondant apparaît automatiquement sur la home — aucun dev
nécessaire.

### Renommer une catégorie existante

Pour changer le libellé affiché dans les filtres (ex : « Courts métrages »
→ « COURT MÉTRAGE ») :

1. **Articles → Catégories**.
2. Cliquer sur la catégorie à renommer.
3. Modifier le champ **Nom** — c'est ce qui s'affiche sur le site.
4. **Ne pas modifier le slug** (colonne « identifiant » / URL) : il est
   utilisé par le filtrage côté navigateur. Le modifier casse les liens
   et les filtres.
5. **Mettre à jour**.

Puis traduire côté anglais :

1. Basculer la langue de l'admin sur **English** (switch Polylang en haut).
2. **Posts → Categories** → la liste bascule en EN.
3. Si une traduction EN existe déjà, l'ouvrir et modifier son **Name**.
   Sinon, créer la traduction via l'icône « + » dans la colonne langue.
4. **Update**.

> Libellés attendus actuels :
> **TOUT · LONG MÉTRAGE · DOCUMENTAIRE · SÉRIE · COURT MÉTRAGE · CLIP**

### Régénérer les miniatures après un changement de taille

Quand l'équipe tech modifie la taille d'affichage des vignettes (ratio,
largeur, etc.), les images déjà uploadées gardent l'ancien format tant
qu'elles ne sont pas régénérées.

Procédure :

1. Installer / activer le plugin **Regenerate Thumbnails** (si absent).
2. Menu gauche → **Outils → Regenerate Thumbnails**.
3. Cliquer « Regenerate Thumbnails For All Attachments ».
4. Patienter (quelques minutes selon le volume).

À faire **une fois** après chaque déploiement qui annonce un changement
de taille d'image. L'équipe tech précise dans ses messages quand c'est
nécessaire.

---

## 7. En cas de problème

- **Un film n'apparaît pas sur la home** : vérifier que son statut est
  « Publié » (pas « Brouillon »), et qu'il a au moins une catégorie.
- **La bande-annonce ne se lance pas** : ouvrir le film en édition, vérifier
  que le champ « Embed vidéo » contient un `<iframe src="…"></iframe>`
  complet, pas juste une URL.
- **La traduction EN ne s'affiche pas** : vérifier qu'elle est publiée (pas
  brouillon) et que Polylang relie bien la version FR et EN (icône chaîne
  dans la colonne « Langue » de la liste).

Pour toute autre question : contacter l'équipe technique.
