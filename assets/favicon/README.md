# Favicon assets

Placeholder monogramme SVG "LB" fourni par défaut (`favicon.svg`, `safari-pinned-tab.svg`).
Pour remplacer par le jeu complet haute définition :

1. Fournir un logo source SVG ou PNG ≥ 512×512 (fond transparent ou plein).
2. Passer par [realfavicongenerator.net](https://realfavicongenerator.net) avec ces réglages :
   - iOS : fond `#1C1A23`, pas de margin.
   - Android : theme color `#1C1A23`, background `#1C1A23`, display `standalone`.
   - Windows : tile color `#1C1A23`.
   - Safari : color `#1C1A23`.
3. Télécharger le zip et copier dans ce dossier les fichiers suivants :
   - `favicon.ico` (multi-tailles 16/32/48)
   - `favicon.svg`
   - `apple-touch-icon.png` (180×180)
   - `favicon-192.png`, `favicon-512.png`, `favicon-maskable-512.png`
   - `safari-pinned-tab.svg`
4. Écraser `site.webmanifest` si realfavicongenerator en produit un — garder `theme_color` et `background_color` à `#1C1A23`.

`inc/favicon.php` inclut uniquement les fichiers présents sur disque : l'ajout des PNG remplace automatiquement le fallback SVG dans `<head>`.
