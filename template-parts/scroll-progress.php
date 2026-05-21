<?php
/**
 * Barre de progression de lecture (scroll).
 * Uniquement rendue sur contenus longs et linéaires (single film, page légale).
 *
 * @package lb3
 */

defined('ABSPATH') || exit;
?>
<div
    x-data="scrollProgress"
    x-init="init()"
    class="pointer-events-none fixed inset-x-0 top-0 z-[60] h-[2px] bg-transparent"
    aria-hidden="true"
>
    <div class="h-full bg-white/70 transition-[width] duration-75 ease-linear"
         :style="`width: ${progress}%`"></div>
</div>
