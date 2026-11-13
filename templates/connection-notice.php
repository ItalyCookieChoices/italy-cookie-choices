<?php

/**
 * The notice of a site connected to other languages by MultilingualPress, without its own banner texts.
 *
 * @see \ItalyCookieChoices\Multilingual\UI\ConnectionNotice::show()
 *
 * @var ConfigInterface $this
 */

declare(strict_types=1);

use ItalyStrap\Config\ConfigInterface;

?>
<div class="notice notice-info">
    <p>
        <?php \esc_html_e(
            'This site is connected to other languages: fill its banner texts from a connected site.',
            'italy-cookie-choices'
        ); ?>
    </p>
    <p>
        <a class="button button-primary" href="<?php echo \esc_url((string) $this->get('fillUrl')); ?>">
            <?php \esc_html_e('Fill the texts', 'italy-cookie-choices'); ?>
        </a>
        <a class="button" href="<?php echo \esc_url((string) $this->get('ignoreUrl')); ?>">
            <?php \esc_html_e('Ignore', 'italy-cookie-choices'); ?>
        </a>
    </p>
</div>
