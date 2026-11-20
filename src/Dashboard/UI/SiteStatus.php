<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Dashboard\UI;

use ItalyCookieChoices\Blocking\Application\VendorSettings;
use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Consent\Domain\BannerLink;
use ItalyCookieChoices\ConsentLog\Application\LogSettings;
use ItalyCookieChoices\Integrations\Application\IntegrationSettings;
use ItalyCookieChoices\Scan\Application\ScanReports;
use ItalyCookieChoices\Scan\Domain\Report;
use ItalyCookieChoices\Settings\Options;
use ItalyCookieChoices\Settings\UI\SettingsTab;

/**
 * The status of the settings that make the consent valid, each with a link to its tab.
 *
 * @phpstan-type Item array{id: string, label: string, value: string, state: string, url: string}
 */
final readonly class SiteStatus
{
    public const OK = 'ok';
    public const WARNING = 'warning';
    public const INFO = 'info';

    public function __construct(
        private Options $options,
        private VendorSettings $vendors,
        private IntegrationSettings $integrations,
        private LogSettings $log,
        private ScanReports $reports,
    ) {
    }

    /**
     * @return list<Item>
     */
    public function items(): array
    {
        return \array_values(\array_filter([
            $this->banner(),
            $this->policy(),
            $this->blocking(),
            $this->consentMode(),
            $this->globalPrivacyControl(),
            $this->consentLog(),
            $this->scan(),
        ]));
    }

    /**
     * @return Item
     */
    private function banner(): array
    {
        $isActive = $this->options->isActive();

        return $this->item(
            'banner',
            \__('Banner', 'italy-cookie-choices'),
            $this->onOff($isActive),
            $isActive ? self::OK : self::WARNING,
            SettingsTab::Banner
        );
    }

    /**
     * @return Item
     */
    private function policy(): array
    {
        $url = $this->options->text(BannerLink::Policy->urlSetting());

        return $this->item(
            'policy',
            \__('Policy link', 'italy-cookie-choices'),
            $url !== '' ? $url : \__('Not set', 'italy-cookie-choices'),
            $url !== '' ? self::OK : self::WARNING,
            SettingsTab::Texts
        );
    }

    /**
     * @return Item
     */
    private function blocking(): array
    {
        $blocked = \count(\array_filter(
            $this->vendors->all(),
            static fn(array $vendor): bool => $vendor['enabled']
        ));

        return $this->item(
            'blocking',
            \__('Blocked vendors', 'italy-cookie-choices'),
            \sprintf(
                /* translators: %d: the number of vendors. */
                \_n('%d vendor', '%d vendors', $blocked, 'italy-cookie-choices'),
                $blocked
            ),
            $blocked > 0 ? self::OK : self::WARNING,
            SettingsTab::Blocking
        );
    }

    /**
     * Only when a Google tag is set.
     *
     * @return Item|null
     */
    private function consentMode(): ?array
    {
        if ($this->integrations->measurementId() === '' && $this->integrations->containerId() === '') {
            return null;
        }

        $sends = $this->integrations->sendsConsentMode();

        return $this->item(
            'consent-mode',
            \__('Google Consent Mode', 'italy-cookie-choices'),
            $this->onOff($sends),
            $sends ? self::OK : self::WARNING,
            SettingsTab::Integrations
        );
    }

    /**
     * @return Item
     */
    private function globalPrivacyControl(): array
    {
        $isOn = $this->options->isChecked(ConsentSetting::GLOBAL_PRIVACY_CONTROL);

        return $this->item(
            'gpc',
            \__('Global Privacy Control', 'italy-cookie-choices'),
            $this->onOff($isOn),
            $isOn ? self::OK : self::INFO,
            SettingsTab::Banner
        );
    }

    /**
     * @return Item
     */
    private function consentLog(): array
    {
        $isEnabled = $this->log->isEnabled();

        return $this->item(
            'consent-log',
            \__('Consent log', 'italy-cookie-choices'),
            $this->onOff($isEnabled),
            $isEnabled ? self::OK : self::WARNING,
            SettingsTab::ConsentLog
        );
    }

    /**
     * @return Item
     */
    private function scan(): array
    {
        $report = $this->reports->last();
        $findings = $report !== null ? $this->findings($report) : [];
        $format = \get_option('date_format');
        $date = $report !== null
            ? (string) \wp_date(\is_string($format) ? $format : 'Y-m-d', $report->date->getTimestamp())
            : \__('Never', 'italy-cookie-choices');

        return $this->item(
            'scan',
            \__('Last scan', 'italy-cookie-choices'),
            \implode(', ', [$date, ...$findings]),
            $report !== null && $findings === [] ? self::OK : self::WARNING,
            SettingsTab::Blocking
        );
    }

    /**
     * What the last scan found to check.
     *
     * @return list<string>
     */
    private function findings(Report $report): array
    {
        $vendors = \count($report->vendors);
        $hosts = \count($report->hosts);

        return \array_values(\array_filter([
            $vendors > 0 ? \sprintf(
                /* translators: %d: the number of vendors. */
                \_n('%d vendor not blocked', '%d vendors not blocked', $vendors, 'italy-cookie-choices'),
                $vendors
            ) : '',
            $hosts > 0 ? \sprintf(
                /* translators: %d: the number of third parties. */
                \_n('%d unknown third party', '%d unknown third parties', $hosts, 'italy-cookie-choices'),
                $hosts
            ) : '',
        ]));
    }

    /**
     * @return Item
     */
    private function item(string $id, string $label, string $value, string $state, SettingsTab $tab): array
    {
        return ['id' => $id, 'label' => $label, 'value' => $value, 'state' => $state, 'url' => $tab->url()];
    }

    private function onOff(bool $isOn): string
    {
        return $isOn ? \__('On', 'italy-cookie-choices') : \__('Off', 'italy-cookie-choices');
    }
}
