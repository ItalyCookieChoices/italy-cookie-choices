<?php

/**
 * The settings of the plugin for the network of a multisite, the React app replaces the content of #icc-network.
 *
 * @see \ItalyCookieChoices\Settings\UI\NetworkSettingsPage::render()
 *
 * @var ConfigInterface $this
 */

declare(strict_types=1);

use ItalyStrap\Config\ConfigInterface;

?>
<div class="wrap">
    <h1><?php echo \esc_html((string) $this->get('title')); ?></h1>
    <div id="icc-network">
        <p><?php \esc_html_e('Loading…', 'italy-cookie-choices'); ?></p>
    </div>
</div>
