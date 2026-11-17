<?php

/**
 * The news of the scheduled scan of the site.
 *
 * @see \ItalyCookieChoices\Scan\UI\ScanNotice::show()
 *
 * @var ConfigInterface $this
 */

declare(strict_types=1);

use ItalyStrap\Config\ConfigInterface;

$icc_vendors = (array) $this->get('vendors');
$icc_hosts = (array) $this->get('hosts');
?>
<div class="notice notice-warning">
    <p><?php \esc_html_e('The scheduled scan of the site found new vendors to check.', 'italy-cookie-choices'); ?></p>
    <?php if ($icc_vendors !== []) : ?>
        <p>
            <?php \esc_html_e('Vendors not blocked:', 'italy-cookie-choices'); ?>
            <?php echo \esc_html(\implode(', ', \array_map('strval', $icc_vendors))); ?>
        </p>
    <?php endif; ?>
    <?php if ($icc_hosts !== []) : ?>
        <p>
            <?php \esc_html_e('Unknown third parties:', 'italy-cookie-choices'); ?>
            <?php echo \esc_html(\implode(', ', \array_map('strval', $icc_hosts))); ?>
        </p>
    <?php endif; ?>
    <p>
        <a class="button button-primary" href="<?php echo \esc_url((string) $this->get('checkUrl')); ?>">
            <?php \esc_html_e('Check them', 'italy-cookie-choices'); ?>
        </a>
        <a class="button" href="<?php echo \esc_url((string) $this->get('ignoreUrl')); ?>">
            <?php \esc_html_e('Ignore', 'italy-cookie-choices'); ?>
        </a>
    </p>
</div>
