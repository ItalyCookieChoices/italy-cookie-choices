<?php

/**
 * The settings page, the React app replaces the content of #icc-admin.
 *
 * @see \ItalyCookieChoices\Settings\UI\SettingsPage::render()
 *
 * @var ConfigInterface $this
 */

declare(strict_types=1);

use ItalyStrap\Config\ConfigInterface;

?>
<div class="wrap">
    <h1><?php echo \esc_html((string) $this->get('title')); ?></h1>
    <div id="icc-admin">
        <p class="hide-if-js"><?php \esc_html_e('The settings page needs JavaScript.', 'italy-cookie-choices'); ?></p>
    </div>
</div>
