<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Cookies\UI;

use ItalyCookieChoices\Consent\UI\ConsentTexts;
use ItalyCookieChoices\Cookies\Application\CookieTableFactory;
use ItalyCookieChoices\Cookies\Domain\Cookie;
use ItalyStrap\Config\Config;
use ItalyStrap\View\ViewInterface;

/**
 * The cookie table of the cookie policy: a table for each category with cookies, in the order of the categories.
 */
final readonly class CookieTableRenderer
{
    private const TEMPLATE = 'cookie-table';

    public function __construct(
        private CookieTableFactory $table,
        private ConsentTexts $texts,
        private ViewInterface $view,
    ) {
    }

    public function html(): string
    {
        return $this->view->render(self::TEMPLATE, new Config($this->data()));
    }

    /**
     * The data the template prints, not escaped yet.
     *
     * @return array{
     *     columns: array{cookie: string, provider: string, purpose: string, duration: string},
     *     categories: list<array{
     *         label: string,
     *         cookies: list<array{name: string, provider: string, purpose: string, duration: string}>
     *     }>
     * }
     */
    public function data(): array
    {
        $table = $this->table->create();

        $categories = [];
        foreach ($this->texts->all()['categories'] as $category) {
            $cookies = $table->inCategory($category['id']);
            if ($cookies === []) {
                continue;
            }

            $categories[] = [
                'label' => $category['label'],
                'cookies' => \array_map($this->row(...), $cookies),
            ];
        }

        return [
            'columns' => [
                'cookie' => \__('Cookie', 'italy-cookie-choices'),
                'provider' => \__('Provider', 'italy-cookie-choices'),
                'purpose' => \__('Purpose', 'italy-cookie-choices'),
                'duration' => \__('Duration', 'italy-cookie-choices'),
            ],
            'categories' => $categories,
        ];
    }

    /**
     * @return array{name: string, provider: string, purpose: string, duration: string}
     */
    private function row(Cookie $cookie): array
    {
        return [
            'name' => $cookie->name(),
            'provider' => $cookie->provider(),
            'purpose' => $cookie->purpose(),
            'duration' => $cookie->duration(),
        ];
    }
}
