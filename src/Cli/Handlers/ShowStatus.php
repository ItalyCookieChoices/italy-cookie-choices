<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Cli\Handlers;

use ItalyCookieChoices\Blocking\Application\VendorSettings;
use ItalyCookieChoices\Console\Pipeline\Message;
use ItalyCookieChoices\Console\Pipeline\StatusCode;
use ItalyCookieChoices\Consent\Application\ConsentCookieDomain;
use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\ConsentLog\Application\LogSettings;
use ItalyCookieChoices\Integrations\Application\IntegrationSettings;
use ItalyCookieChoices\Settings\NetworkSettings;
use ItalyCookieChoices\Settings\Options;
use ItalyStrap\Pipeline\HandlerInterface;

/**
 * The status of the site, read from the settings of each context.
 */
final readonly class ShowStatus implements HandlerInterface
{
    private const FIELDS = ['setting', 'value'];

    public function __construct(
        private Options $options,
        private VendorSettings $vendors,
        private IntegrationSettings $integrations,
        private LogSettings $log,
        private ConsentPolicyFactory $policy,
        private ConsentCookieDomain $cookieDomain,
        private NetworkSettings $network,
    ) {
    }

    public function handle(object $message): StatusCode
    {
        \assert($message instanceof Message);
        $message->io()->items($this->items(), self::FIELDS);

        return StatusCode::Success;
    }

    /**
     * @return list<array<string, string>>
     */
    private function items(): array
    {
        $values = [
            'banner' => $this->onOff($this->options->isActive()),
            'blocked_vendors' => $this->blockedVendors(),
            'consent_mode' => $this->consentMode(),
            'microsoft_uet' => $this->onOff($this->integrations->sendsUetConsent()),
            'microsoft_clarity' => $this->onOff($this->integrations->sendsClarityConsent()),
            'global_privacy_control' => $this->onOff($this->options->isChecked('global_privacy_control')),
            'consent_duration_days' => (string) $this->policy->create()->duration()->days(),
            'cookie_domain' => $this->cookieDomain->forThisSite(),
            'consent_log' => $this->onOff($this->log->isEnabled()),
            'consent_log_retention_months' => (string) $this->log->retention()->months(),
            'uses_main_site_settings' => $this->onOff($this->network->usesTheMainSite()),
        ];

        $items = [];
        foreach ($values as $setting => $value) {
            $items[] = ['setting' => $setting, 'value' => $value];
        }

        return $items;
    }

    /**
     * The ids of the vendors whose scripts and frames are blocked.
     */
    private function blockedVendors(): string
    {
        $ids = [];
        foreach ($this->vendors->all() as $vendor) {
            if (!$vendor['enabled']) {
                continue;
            }

            $ids[] = $vendor['id'];
        }

        return \implode(', ', $ids);
    }

    private function consentMode(): string
    {
        if (!$this->integrations->sendsConsentMode()) {
            return 'off';
        }

        return $this->integrations->isConsentModeAdvanced() ? 'advanced' : 'basic';
    }

    private function onOff(bool $value): string
    {
        return $value ? 'on' : 'off';
    }
}
