<?php

/**
 * The custom scripts of a position, the ones with a category wait for its consent.
 *
 * @see \ItalyCookieChoices\Blocking\UI\CustomScriptsPrinter
 *
 * @var ConfigInterface $this
 */

declare(strict_types=1);

use ItalyStrap\Config\ConfigInterface;

/** @var list<array{category: string, code: string}> $icc_scripts */
$icc_scripts = (array) $this->get('scripts');
foreach ($icc_scripts as $icc_script) :
    if ($icc_script['category'] === '') :
        // The code of the site owner, saved with unfiltered_html.
        echo $icc_script['code'] . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
        continue;
    endif;
    ?>
<template data-icc-code data-category="<?php echo \esc_attr($icc_script['category']); ?>"><?php
    echo $icc_script['code']; // phpcs:ignore WordPress.Security.EscapeOutput
?></template>
    <?php
endforeach;
