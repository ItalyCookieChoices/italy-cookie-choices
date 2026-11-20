<?php

/**
 * The widget of the dashboard: the status of the settings and the consents of the month.
 *
 * @see \ItalyCookieChoices\Dashboard\UI\DashboardWidget::data()
 *
 * @var ConfigInterface $this
 */

declare(strict_types=1);

use ItalyStrap\Config\ConfigInterface;

$icc_items = (array) $this->get('items');
$icc_consents = $this->get('consents');
$icc_icons = ['ok' => 'yes-alt', 'warning' => 'warning', 'info' => 'info-outline'];
?>
<ul class="icc-dashboard__status">
    <?php foreach ($icc_items as $icc_item) : ?>
        <?php
        $icc_item = (array) $icc_item;
        $icc_state = (string) ($icc_item['state'] ?? 'info');
        ?>
        <li class="icc-dashboard__item icc-dashboard__item--<?php echo \esc_attr($icc_state); ?>">
            <span class="dashicons dashicons-<?php echo \esc_attr($icc_icons[$icc_state] ?? 'info-outline'); ?>"
                aria-hidden="true"></span>
            <span>
                <a href="<?php echo \esc_url((string) ($icc_item['url'] ?? '')); ?>"><?php
                    echo \esc_html((string) ($icc_item['label'] ?? ''));
                ?></a>: <?php echo \esc_html((string) ($icc_item['value'] ?? '')); ?>
            </span>
        </li>
    <?php endforeach; ?>
</ul>
<?php if (\is_array($icc_consents)) : ?>
    <h3 class="icc-dashboard__title"><?php \esc_html_e('Consents this month', 'italy-cookie-choices'); ?></h3>
    <ul class="icc-dashboard__consents">
        <li>
            <strong><?php echo \esc_html(\number_format_i18n((int) ($icc_consents['choices'] ?? 0))); ?></strong>
            <?php \esc_html_e('Choices', 'italy-cookie-choices'); ?>
        </li>
        <li>
            <strong><?php echo \esc_html(\number_format_i18n((int) ($icc_consents['acceptAll'] ?? 0))); ?></strong>
            <?php \esc_html_e('Accepted', 'italy-cookie-choices'); ?>
        </li>
        <li>
            <strong><?php echo \esc_html(\number_format_i18n((int) ($icc_consents['rejectAll'] ?? 0))); ?></strong>
            <?php \esc_html_e('Refused', 'italy-cookie-choices'); ?>
        </li>
        <li>
            <strong><?php echo \esc_html(\number_format_i18n((int) ($icc_consents['partial'] ?? 0))); ?></strong>
            <?php \esc_html_e('Partial', 'italy-cookie-choices'); ?>
        </li>
    </ul>
    <p>
        <a href="<?php echo \esc_url((string) $this->get('logUrl')); ?>">
            <?php \esc_html_e('See the consent log', 'italy-cookie-choices'); ?>
        </a>
    </p>
<?php endif; ?>
