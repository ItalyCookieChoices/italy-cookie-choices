<?php

/**
 * The notice of the settings of another consent plugin that can be imported.
 *
 * @see \ItalyCookieChoices\Migration\UI\MigrationNotice::show()
 *
 * @var ConfigInterface $this
 */

declare(strict_types=1);

use ItalyStrap\Config\ConfigInterface;

?>
<div class="notice notice-info">
    <p>
        <?php echo \esc_html(\sprintf(
            /* translators: %s: the names of the plugins, separated by commas. */
            \__('Cookie Choices can import the settings of %s.', 'italy-cookie-choices'),
            (string) $this->get('plugins')
        )); ?>
    </p>
    <p>
        <a class="button button-primary" href="<?php echo \esc_url((string) $this->get('importUrl')); ?>">
            <?php \esc_html_e('Import the settings', 'italy-cookie-choices'); ?>
        </a>
        <a class="button" href="<?php echo \esc_url((string) $this->get('dismissUrl')); ?>">
            <?php \esc_html_e('Dismiss', 'italy-cookie-choices'); ?>
        </a>
    </p>
</div>
