<?php

/**
 * The settings of the plugin for the network of a multisite.
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
    <?php if ((bool) $this->get('updated')) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php \esc_html_e('Settings saved.', 'italy-cookie-choices'); ?></p>
        </div>
    <?php endif; ?>
    <form method="post" action="<?php echo \esc_url((string) $this->get('action')); ?>">
        <?php \wp_nonce_field((string) $this->get('nonceAction')); ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php \esc_html_e('Settings of the sites', 'italy-cookie-choices'); ?></th>
                <td>
                    <label>
                        <input
                            type="checkbox"
                            class="icc-network-shared"
                            name="shared"
                            value="1"
                            aria-describedby="icc-network-shared-description"
                            <?php \checked((bool) $this->get('shared')); ?>
                        >
                        <?php \esc_html_e('Use the settings of the main site on every site', 'italy-cookie-choices'); ?>
                    </label>
                    <p id="icc-network-shared-description" class="description">
                        <?php \esc_html_e(
                            'Each site keeps its own texts, for its language, and its own consent log.',
                            'italy-cookie-choices'
                        ); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php \submit_button(); ?>
    </form>
</div>
