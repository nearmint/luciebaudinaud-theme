<?php
/**
 * Bouton TinyMCE « lang » — wrappe la sélection dans <span lang="xx">…</span>.
 *
 * Usage côté Lucie (admin) :
 *   1. Sélectionner un titre de film, une citation ou un nom propre dans une autre
 *      langue (ex : « The Tree of Life »).
 *   2. Cliquer sur le bouton « lang » de la barre d'outils TinyMCE (fin de rangée 1).
 *   3. Saisir le code langue (en, it, de, en-US, pt-BR…).
 *
 * Résultat : le lecteur d'écran change de prononciation sur le fragment, évite
 * le « franglais » auditif.
 *
 * @package lb3
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Enregistre le plugin TinyMCE.
 */
add_filter('mce_external_plugins', static function (array $plugins): array {
    $plugins['lb3_lang_attribute'] = LB3_URI . '/assets/admin/tinymce-lang-attribute.js?v=' . LB3_VERSION;
    return $plugins;
});

/**
 * Ajoute le bouton en fin de rangée 1 de la toolbar WYSIWYG.
 */
add_filter('mce_buttons', static function (array $buttons): array {
    $buttons[] = 'lb3_lang_attribute';
    return $buttons;
});

/**
 * Autorise `lang` (et son alias historique `xml:lang`) sur les <span> dans le
 * contenu filtré par wp_kses côté front. `lang` est déjà accepté par défaut,
 * on explicite pour ne pas être cassé par un futur override.
 */
add_filter('wp_kses_allowed_html', static function (array $tags, string $context): array {
    if ($context !== 'post') {
        return $tags;
    }
    if (!isset($tags['span']) || !is_array($tags['span'])) {
        $tags['span'] = [];
    }
    $tags['span']['lang']     = true;
    $tags['span']['xml:lang'] = true;
    $tags['span']['dir']      = true;
    return $tags;
}, 10, 2);
