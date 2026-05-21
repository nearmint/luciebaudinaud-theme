<?php
/**
 * Fallback : portfolio one-page → redirige les archives vers la home.
 *
 * @package lb3
 */

defined('ABSPATH') || exit;

if (!is_front_page()) {
    wp_safe_redirect(home_url('/'), 302);
    exit;
}

get_template_part('front-page');
