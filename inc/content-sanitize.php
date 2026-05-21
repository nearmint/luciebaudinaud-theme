<?php
/**
 * Sanitizers appliqués au contenu rendu (WYSIWYG ACF + the_content).
 *
 * Motivation : les éditeurs peuvent laisser des balises vides (heading sans
 * texte, paragraphe d'espaces) qui cassent les audits a11y (Lighthouse signale
 * « Heading elements should contain content ») sans impact visible. On les
 * strip au rendu plutôt que d'exiger une hygiène parfaite côté admin.
 *
 * Appliqué :
 *  - acf/format_value/type=wysiwyg  → champs ACF WYSIWYG (ex : texte_cv)
 *  - the_content                    → corps des posts/pages
 *
 * @package lb3
 */
declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Supprime les <h1>..<h6> dont le contenu (une fois les balises retirées) est
 * vide ou composé uniquement d'espaces/&nbsp;.
 */
function lb3_strip_empty_headings(string $html): string
{
    if ($html === '' || stripos($html, '<h') === false) {
        return $html;
    }

    return (string) preg_replace_callback(
        '#<h([1-6])\b[^>]*>(.*?)</h\1>#is',
        static function (array $m): string {
            $inner = trim(wp_strip_all_tags($m[2]));
            // &nbsp; et autres espaces insécables → considérés comme vides.
            $inner = preg_replace('/[\s\x{00A0}]+/u', '', $inner);
            return $inner === '' ? '' : $m[0];
        },
        $html
    );
}

add_filter('acf/format_value/type=wysiwyg', 'lb3_strip_empty_headings', 20);
add_filter('the_content', 'lb3_strip_empty_headings', 20);
