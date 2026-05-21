<?php
/**
 * ACF field groups déclarés en PHP.
 *
 * Pourquoi PHP plutôt que acf-json uniquement ?
 * → acf-json = source de vérité (sync auto admin ↔ disque).
 * → Le code PHP ici sert de filet de sécurité si les JSON sont perdus
 *   et de référence lisible dans le repo.
 *
 * Si acf-json contient déjà les field groups, ce code peut être désactivé
 * (il est idempotent : ACF détecte les doublons par `key`).
 *
 * @package lb3
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

add_action('acf/init', static function (): void {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    // ─────────────────────────────────────────────────────────
    // GROUPE 1 : fiche film (single post)
    // ─────────────────────────────────────────────────────────
    acf_add_local_field_group([
        'key'      => 'group_lb3_film',
        'title'    => __('Fiche film', 'lb3'),
        'fields'   => [
            [
                'key'           => 'field_lb3_film_featured',
                'label'         => __('Film mis en avant', 'lb3'),
                'name'          => 'featured',
                'type'          => 'true_false',
                'ui'            => 1,
                'default_value' => 0,
                'instructions'  => __('Cocher pour remonter ce film en tête de la grille de la home. Les films « mis en avant » apparaissent avant les autres.', 'lb3'),
            ],
            [
                'key'           => 'field_lb3_film_cover',
                'label'         => __('Image de couverture (fullscreen)', 'lb3'),
                'name'          => 'cover',
                'type'          => 'image',
                'return_format' => 'url',
                'preview_size'  => 'medium',
                'instructions'  => __('Image d\'au moins 2400 px de large, orientée paysage. Apparaît en fond fullscreen en haut de la fiche film.', 'lb3'),
            ],
            [
                'key'          => 'field_lb3_film_video_url',
                'label'        => __('URL de la bande-annonce (Vimeo ou YouTube)', 'lb3'),
                'name'         => 'video_url',
                'type'         => 'url',
                'required'     => 0,
                'instructions' => __('Collez uniquement l\'URL de la vidéo. Exemples : https://vimeo.com/157932076 ou https://www.youtube.com/watch?v=dQw4w9WgXcQ. Laissez vide si pas de bande-annonce.', 'lb3'),
            ],
            // Champ legacy conservé en DB : masqué par `acf/prepare_field/name=iframe`
            // si la valeur est vide (cf. inc/setup.php). Visible uniquement sur les
            // fiches qui ont encore du HTML iframe stocké → permet à Lucie de migrer
            // vers video_url puis de vider ce champ.
            [
                'key'          => 'field_lb3_film_iframe',
                'label'        => __('Embed vidéo (obsolète)', 'lb3'),
                'name'         => 'iframe',
                'type'         => 'wysiwyg',
                'tabs'         => 'visual,text',
                'toolbar'      => 'basic',
                'media_upload' => 0,
                'delay'        => 1,
                'instructions' => __('Champ obsolète. Recopiez l\'URL Vimeo ou YouTube dans le champ URL ci-dessus, puis videz complètement ce champ.', 'lb3'),
            ],
            [
                'key'          => 'field_lb3_film_realisateur',
                'label'        => __('Réalisation', 'lb3'),
                'name'         => 'realisateur',
                'type'         => 'text',
                'instructions' => __('Nom complet du réalisateur ou de la réalisatrice. Ex : « Agnès Varda ».', 'lb3'),
            ],
            [
                'key'          => 'field_lb3_film_duree',
                'label'        => __('Durée', 'lb3'),
                'name'         => 'duree',
                'type'         => 'text',
                'instructions' => __('Format libre. Ex : « 90 min », « 52 min », « 1h30 ».', 'lb3'),
            ],
            [
                'key'          => 'field_lb3_film_annee',
                'label'        => __('Année', 'lb3'),
                'name'         => 'annee',
                'type'         => 'text',
                'instructions' => __('Année de sortie. Ex : « 2024 ».', 'lb3'),
            ],
            [
                'key'           => 'field_lb3_film_gallery',
                'label'         => __('Galerie de photogrammes', 'lb3'),
                'name'          => 'gallery',
                'type'          => 'gallery',
                'return_format' => 'id',
                'preview_size'  => 'medium',
                'insert'        => 'append',
                'library'       => 'all',
                'instructions'  => __('Photogrammes ou photos de plateau. Recommandé : 6 à 15 images horizontales en haute définition (≥ 2000 px de large).', 'lb3'),
            ],
            [
                'key'          => 'field_lb3_film_transcription',
                'label'        => __('Transcription de la bande-annonce', 'lb3'),
                'name'         => 'transcription',
                'type'         => 'wysiwyg',
                'toolbar'      => 'basic',
                'tabs'         => 'visual,text',
                'media_upload' => 0,
                'instructions' => __('Transcription texte de la bande-annonce (a11y, SEO). Laisser vide si la vidéo n\'a pas de dialogue ou si aucune transcription n\'est disponible.', 'lb3'),
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'post',
                ],
            ],
        ],
        'style'           => 'default',
        'menu_order'      => 0,
        'label_placement' => 'top',
    ]);

    // ─────────────────────────────────────────────────────────
    // GROUPE 1b : sélections & prix (repeater sur la fiche film)
    // ─────────────────────────────────────────────────────────
    acf_add_local_field_group([
        'key'      => 'group_lb3_film_selections',
        'title'    => __('Sélections & prix', 'lb3'),
        'fields'   => [
            [
                'key'          => 'field_lb3_film_selections',
                'label'        => __('Sélections et prix', 'lb3'),
                'name'         => 'selections',
                'type'         => 'repeater',
                'button_label' => __('Ajouter une sélection / un prix', 'lb3'),
                'layout'       => 'block',
                'instructions' => __('Ajoute une entrée par festival (sélection officielle, prix ou mention). Laisser vide si le film n\'a pas de palmarès.', 'lb3'),
                'sub_fields'   => [
                    [
                        'key'          => 'field_lb3_film_sel_festival',
                        'label'        => __('Festival', 'lb3'),
                        'name'         => 'festival',
                        'type'         => 'text',
                        'required'     => 1,
                        'instructions' => __('Nom du festival. Ex : « Festival de Cannes ».', 'lb3'),
                        'wrapper'      => ['width' => '50'],
                    ],
                    [
                        'key'          => 'field_lb3_film_sel_section',
                        'label'        => __('Section', 'lb3'),
                        'name'         => 'section',
                        'type'         => 'text',
                        'instructions' => __('Section du festival (facultatif). Ex : « Semaine de la Critique ».', 'lb3'),
                        'wrapper'      => ['width' => '50'],
                    ],
                    [
                        'key'          => 'field_lb3_film_sel_annee',
                        'label'        => __('Année', 'lb3'),
                        'name'         => 'annee',
                        'type'         => 'number',
                        'min'          => 1900,
                        'max'          => 2100,
                        'instructions' => __('Année de la sélection ou du prix. Ex : 2026.', 'lb3'),
                        'wrapper'      => ['width' => '25'],
                    ],
                    [
                        'key'           => 'field_lb3_film_sel_type',
                        'label'         => __('Type', 'lb3'),
                        'name'          => 'type',
                        'type'          => 'select',
                        'choices'       => [
                            'selection' => __('Sélection', 'lb3'),
                            'prix'      => __('Prix', 'lb3'),
                            'mention'   => __('Mention spéciale', 'lb3'),
                        ],
                        'default_value' => 'selection',
                        'return_format' => 'value',
                        'ui'            => 1,
                        'allow_null'    => 0,
                        'instructions'  => __('Nature de la distinction.', 'lb3'),
                        'wrapper'       => ['width' => '35'],
                    ],
                    [
                        'key'          => 'field_lb3_film_sel_prix_nom',
                        'label'        => __('Nom du prix', 'lb3'),
                        'name'         => 'prix_nom',
                        'type'         => 'text',
                        'instructions' => __('Ex : « Caméra d\'Or », « Prix de la mise en scène ». Inutile pour une simple sélection.', 'lb3'),
                        'wrapper'      => ['width' => '40'],
                        'conditional_logic' => [
                            [
                                [
                                    'field'    => 'field_lb3_film_sel_type',
                                    'operator' => '!=',
                                    'value'    => 'selection',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'post',
                ],
            ],
        ],
        'style'           => 'default',
        'menu_order'      => 1,
        'label_placement' => 'top',
    ]);

    // ─────────────────────────────────────────────────────────
    // GROUPE 1c : fiche technique DP (métadonnées optionnelles)
    // ─────────────────────────────────────────────────────────
    acf_add_local_field_group([
        'key'      => 'group_lb3_film_technical',
        'title'    => __('Fiche technique', 'lb3'),
        'fields'   => [
            [
                'key'          => 'field_lb3_film_tech_camera',
                'label'        => __('Caméra', 'lb3'),
                'name'         => 'camera',
                'type'         => 'text',
                'instructions' => __('Modèle principal. Ex : « Arri Alexa 35 », « Sony Venice ».', 'lb3'),
                'wrapper'      => ['width' => '50'],
            ],
            [
                'key'          => 'field_lb3_film_tech_lenses',
                'label'        => __('Optiques', 'lb3'),
                'name'         => 'lenses',
                'type'         => 'text',
                'instructions' => __('Série d\'objectifs utilisée. Ex : « Zeiss Supreme Primes ».', 'lb3'),
                'wrapper'      => ['width' => '50'],
            ],
            [
                'key'          => 'field_lb3_film_tech_format',
                'label'        => __('Format de capture', 'lb3'),
                'name'         => 'format',
                'type'         => 'text',
                'instructions' => __('Codec et container. Ex : « ProRes 4444 », « ARRIRAW ».', 'lb3'),
                'wrapper'      => ['width' => '50'],
            ],
            [
                'key'           => 'field_lb3_film_tech_support',
                'label'         => __('Support', 'lb3'),
                'name'          => 'pellicule_vs_numerique',
                'type'          => 'radio',
                'choices'       => [
                    'numerique' => __('Numérique', 'lb3'),
                    'pellicule' => __('Pellicule', 'lb3'),
                    'mixte'     => __('Mixte', 'lb3'),
                ],
                'allow_null'    => 1,
                'layout'        => 'horizontal',
                'return_format' => 'value',
                'instructions'  => __('Origine image du tournage.', 'lb3'),
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'          => 'field_lb3_film_tech_aspect_ratio',
                'label'        => __('Ratio', 'lb3'),
                'name'         => 'aspect_ratio',
                'type'         => 'text',
                'instructions' => __('Ratio d\'image. Ex : « 1.85:1 », « 2.39:1 ».', 'lb3'),
                'wrapper'      => ['width' => '33'],
            ],
            [
                'key'          => 'field_lb3_film_tech_resolution',
                'label'        => __('Résolution', 'lb3'),
                'name'         => 'resolution',
                'type'         => 'text',
                'instructions' => __('Résolution de livraison. Ex : « 4K », « 2K », « 6K OG ».', 'lb3'),
                'wrapper'      => ['width' => '33'],
            ],
            [
                'key'          => 'field_lb3_film_tech_etalonnage',
                'label'        => __('Étalonnage', 'lb3'),
                'name'         => 'etalonnage',
                'type'         => 'text',
                'instructions' => __('Coloriste / étalonneur (facultatif). Ex : « Pierre Dupont, Éclair ».', 'lb3'),
                'wrapper'      => ['width' => '34'],
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'post',
                ],
            ],
        ],
        'style'           => 'default',
        'menu_order'      => 2,
        'label_placement' => 'top',
    ]);

    // ─────────────────────────────────────────────────────────
    // GROUPE 2 : pages légales (mentions légales, politique de confidentialité, FR + EN)
    // ─────────────────────────────────────────────────────────
    acf_add_local_field_group([
        'key'      => 'group_lb3_legal',
        'title'    => __('Page légale', 'lb3'),
        'fields'   => [
            [
                'key'     => 'field_lb3_legal_content',
                'label'   => __('Contenu de la page', 'lb3'),
                'name'    => 'legal_content',
                'type'    => 'wysiwyg',
                'toolbar' => 'full',
                'tabs'    => 'visual,text',
                'instructions' => __('Texte de la page (mentions légales, politique de confidentialité…).', 'lb3'),
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'page_template',
                    'operator' => '==',
                    'value'    => 'page-legal.php',
                ],
            ],
        ],
        'style'           => 'default',
        'label_placement' => 'top',
    ]);

    // ─────────────────────────────────────────────────────────
    // GROUPE 3 : page d'accueil (affecté à la page front)
    // ─────────────────────────────────────────────────────────
    acf_add_local_field_group([
        'key'      => 'group_lb3_home',
        'title'    => __('Page d\'accueil', 'lb3'),
        'fields'   => [
            [
                'key'           => 'field_lb3_home_cover',
                'label'         => __('Hero : image de fond', 'lb3'),
                'name'          => 'cover',
                'type'          => 'image',
                'return_format' => 'url',
                'preview_size'  => 'medium',
                'instructions'  => __('Image fullscreen affichée au chargement de la home. Paysage, ≥ 2400 px de large.', 'lb3'),
            ],
            [
                'key'          => 'field_lb3_home_seo_description',
                'label'        => __('SEO : description (meta)', 'lb3'),
                'name'         => 'seo_description',
                'type'         => 'textarea',
                'rows'         => 3,
                'instructions' => __('Phrase unique visible dans Google et en aperçu de partage. 150 à 160 caractères, pas de guillemets superflus.', 'lb3'),
            ],
            [
                'key'           => 'field_lb3_home_og_image_default',
                'label'         => __('SEO : image Open Graph par défaut', 'lb3'),
                'name'          => 'og_image_default',
                'type'          => 'image',
                'return_format' => 'url',
                'preview_size'  => 'medium',
                'instructions'  => __('Image utilisée lors des partages sociaux quand aucune couverture n\'est disponible. Dimensions recommandées : 1200 × 630 px, poids ≤ 200 KB.', 'lb3'),
            ],
            [
                'key'           => 'field_lb3_home_cv_pdf',
                'label'         => __('CV au format PDF', 'lb3'),
                'name'          => 'cv_pdf',
                'type'          => 'file',
                'return_format' => 'url',
                'mime_types'    => 'pdf',
                'instructions'  => __('PDF téléchargeable depuis la section CV. Nom de fichier lisible (ex : « lucie-baudinaud-cv.pdf »), poids ≤ 2 MB.', 'lb3'),
            ],
            [
                'key'           => 'field_lb3_home_galerie',
                'label'         => __('Galerie photos', 'lb3'),
                'name'          => 'galerie',
                'type'          => 'gallery',
                'return_format' => 'id',
                'insert'        => 'append',
                'instructions'  => __('Photos personnelles affichées en grille dans la section Photos de la home. Haute définition, format libre.', 'lb3'),
            ],
            [
                'key'           => 'field_lb3_home_photo_cover_photogrammes',
                'label'         => __('Bande séparatrice : avant Photos', 'lb3'),
                'name'          => 'photo_cover_photogrammes',
                'type'          => 'image',
                'return_format' => 'url',
                'instructions'  => __('Image pleine largeur affichée avant la section Photos. Format paysage large, ≥ 2000 px.', 'lb3'),
            ],
            [
                'key'           => 'field_lb3_home_photo_cover_cv',
                'label'         => __('Bande séparatrice : avant CV', 'lb3'),
                'name'          => 'photo_cover_cv',
                'type'          => 'image',
                'return_format' => 'url',
                'instructions'  => __('Image pleine largeur affichée avant la section CV. Format paysage large, ≥ 2000 px.', 'lb3'),
            ],
            [
                'key'           => 'field_lb3_home_photo_cover_contact',
                'label'         => __('Bande séparatrice : avant Contact', 'lb3'),
                'name'          => 'photo_cover_contact',
                'type'          => 'image',
                'return_format' => 'url',
                'instructions'  => __('Image pleine largeur affichée avant la section Contact. Format paysage large, ≥ 2000 px.', 'lb3'),
            ],
            [
                'key'          => 'field_lb3_home_texte_cv',
                'label'        => __('Texte CV', 'lb3'),
                'name'         => 'texte_cv',
                'type'         => 'wysiwyg',
                'toolbar'      => 'full',
                'instructions' => __('CV affiché dans la section CV. Utiliser des titres (H2/H3) pour structurer par rubrique (longs métrages, courts métrages, clips…).', 'lb3'),
            ],
            [
                'key'          => 'field_lb3_home_texte_bio',
                'label'        => __('Texte Bio / Contact', 'lb3'),
                'name'         => 'texte_bio',
                'type'         => 'wysiwyg',
                'toolbar'      => 'full',
                'instructions' => __('Bio courte et coordonnées affichées dans la section Contact. Inclure le mailto: pour tracking Umami.', 'lb3'),
            ],
            [
                'key'          => 'field_lb3_home_social_instagram',
                'label'        => __('Instagram URL', 'lb3'),
                'name'         => 'social_instagram',
                'type'         => 'url',
                'instructions' => __('URL complète du profil Instagram. Utilisée dans le footer et le JSON-LD (sameAs).', 'lb3'),
            ],
            [
                'key'          => 'field_lb3_home_social_imdb',
                'label'        => __('IMDb URL', 'lb3'),
                'name'         => 'social_imdb',
                'type'         => 'url',
                'instructions' => __('URL complète de la fiche IMDb. Utilisée dans le footer et le JSON-LD.', 'lb3'),
            ],
            [
                'key'          => 'field_lb3_home_social_vimeo',
                'label'        => __('Vimeo URL', 'lb3'),
                'name'         => 'social_vimeo',
                'type'         => 'url',
                'instructions' => __('URL complète de la chaîne Vimeo. Utilisée dans le footer et le JSON-LD.', 'lb3'),
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'page_type',
                    'operator' => '==',
                    'value'    => 'front_page',
                ],
            ],
        ],
        'style'           => 'default',
        'label_placement' => 'top',
    ]);
});
