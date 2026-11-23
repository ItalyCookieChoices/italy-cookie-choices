<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Cookies\Application;

use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\Settings\NetworkSettings;
use ItalyCookieChoices\Settings\Options;

/**
 * The cookies of the site itself, for the cookie table, like the login of WordPress or the cart of the shop.
 */
final readonly class SiteCookies
{
    public function __construct(
        private Options $options,
        private ConsentPolicyFactory $policy,
        private NetworkSettings $network,
    ) {
    }

    /**
     * @return array<array-key, mixed> The cookie as it is saved.
     * @throws \InvalidArgumentException When the cookie is not valid or the site uses the main site.
     */
    public function add(string $name, string $category, string $duration, string $purpose): array
    {
        $this->assertTheSiteHasItsOwnCookies();
        $name = \trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException(\__('The cookie needs a name.', 'italy-cookie-choices'));
        }

        $this->assertIsNew($name);
        $this->assertIsACategory($category);

        $cookies = $this->options->records(CookieSetting::SITE_COOKIES);
        $cookies[] = ['name' => $name, 'category' => $category, 'duration' => $duration, 'purpose' => $purpose];
        $this->options->save([CookieSetting::SITE_COOKIES => $cookies]);

        return $this->options->records(CookieSetting::SITE_COOKIES)[\count($cookies) - 1] ?? [];
    }

    private function assertTheSiteHasItsOwnCookies(): void
    {
        if (!$this->network->usesTheMainSite()) {
            return;
        }

        throw new \InvalidArgumentException(
            \__('The cookies of this site come from the main site of the network.', 'italy-cookie-choices')
        );
    }

    private function assertIsNew(string $name): void
    {
        $names = \array_column($this->options->records(CookieSetting::SITE_COOKIES), 'name');
        if (!\in_array($name, $names, true)) {
            return;
        }

        throw new \InvalidArgumentException(\sprintf(
            /* translators: %s: the name of the cookie. */
            \__('The site cookie "%s" already exists.', 'italy-cookie-choices'),
            $name
        ));
    }

    private function assertIsACategory(string $category): void
    {
        $categories = $this->policy->create()->categories()->ids();
        if (\in_array($category, $categories, true)) {
            return;
        }

        throw new \InvalidArgumentException(\sprintf(
            /* translators: 1: the id of the category, 2: the ids of the categories, separated by commas. */
            \__('There is no category "%1$s": %2$s.', 'italy-cookie-choices'),
            $category,
            \implode(', ', $categories)
        ));
    }
}
