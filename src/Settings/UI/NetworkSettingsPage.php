<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI;

use ItalyCookieChoices\Settings\NetworkSettings;
use ItalyStrap\Asset\AssetLoader;
use ItalyStrap\Asset\BuildMetadata;
use ItalyStrap\Asset\BuildMetadataFile;
use ItalyStrap\Asset\Dependencies;
use ItalyStrap\Asset\EventName;
use ItalyStrap\Asset\Handle;
use ItalyStrap\Asset\InlineScript;
use ItalyStrap\Asset\Position;
use ItalyStrap\Asset\Script;
use ItalyStrap\Asset\ScriptLoading;
use ItalyStrap\Asset\Style;
use ItalyStrap\Asset\Translations;
use ItalyStrap\Config\Config;
use ItalyStrap\Event\SubscriberInterface;
use ItalyStrap\View\ViewInterface;
use SplFileInfo;

/**
 * Network Admin > Settings > Cookie Choices, on a multisite with the plugin active on the network:
 * a React app with the switch that gives the settings of the main site to every site,
 * and the copy of the settings of a site to other sites.
 */
final readonly class NetworkSettingsPage implements SubscriberInterface
{
    public const SLUG = 'italy-cookie-choices';
    public const HANDLE = 'italy-cookie-choices-network';
    public const CAPABILITY = 'manage_network_options';

    private const PARENT = 'settings.php';
    private const EVENT_NAME_SUFFIX = 'settings_page_' . self::SLUG;
    private const SCRIPT = 'network-settings.js';
    private const STYLE = 'network-settings.css';
    private const TEMPLATE = 'network-settings';

    /**
     * @param string $buildPath The assets/build folder, with the trailing slash.
     * @param string $buildUrl The assets/build URL, with the trailing slash.
     * @param string $languagesPath The folder of the translations bundled with the plugin.
     */
    public function __construct(
        private NetworkSettings $network,
        private ViewInterface $view,
        private AssetLoader $assets,
        private string $buildPath,
        private string $buildUrl,
        private string $languagesPath,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'network_admin_menu' => 'addPage';
        yield EventName::ADMIN => 'enqueue';
    }

    public function addPage(): void
    {
        \add_submenu_page(
            self::PARENT,
            \__('Cookie Choices', 'italy-cookie-choices'),
            \__('Cookie Choices', 'italy-cookie-choices'),
            self::CAPABILITY,
            self::SLUG,
            $this->render(...)
        );
    }

    public function render(): void
    {
        echo $this->view->render( // phpcs:ignore -- Escaped in the template.
            self::TEMPLATE,
            new Config(['title' => \__('Cookie Choices', 'italy-cookie-choices')])
        );
    }

    public function enqueue(mixed $eventNameSuffix = ''): void
    {
        $metadata = $this->metadata();
        if ($metadata === null || $eventNameSuffix !== self::EVENT_NAME_SUFFIX || !\is_network_admin()) {
            return;
        }

        $this->assets->load(
            new Script(
                handle: new Handle(self::HANDLE),
                source: $this->buildUrl . self::SCRIPT,
                dependencies: $metadata->dependencies(),
                version: $metadata->version(),
                loading: new ScriptLoading(inFooter: true),
                inlineScripts: [new InlineScript(
                    \sprintf('window.italyCookieChoicesNetwork = %s;', (string) \wp_json_encode($this->data())),
                    Position::Before
                )],
                translations: new Translations('italy-cookie-choices', new SplFileInfo($this->languagesPath)),
            ),
            new Style(
                handle: new Handle(self::HANDLE),
                source: $this->buildUrl . self::STYLE,
                dependencies: new Dependencies('wp-components'),
                version: $metadata->version(),
                data: ['rtl' => 'replace'],
            ),
        );
    }

    /**
     * The switch of the network and the sites to copy the settings between, the main site first.
     *
     * @return array{shared: bool, mainSiteId: int, sites: list<array{id: int, name: string, url: string}>}
     */
    public function data(): array
    {
        $mainSiteId = \get_main_site_id();
        $sites = $this->sites();
        $isMain = static fn(array $site): bool => $site['id'] === $mainSiteId;

        return [
            'shared' => $this->network->sharesTheMainSite(),
            'mainSiteId' => $mainSiteId,
            'sites' => [
                ...\array_filter($sites, $isMain),
                ...\array_filter($sites, static fn(array $site): bool => !$isMain($site)),
            ],
        ];
    }

    /**
     * The public sites of this network.
     *
     * @return list<array{id: int, name: string, url: string}>
     */
    private function sites(): array
    {
        $query = [
            'network_id' => \get_current_network_id(),
            'number' => 0,
            'archived' => 0,
            'deleted' => 0,
            'spam' => 0,
            'fields' => 'ids',
        ];

        return \array_values(\array_map(
            fn(int $siteId): array => [
                'id' => $siteId,
                'name' => $this->name($siteId),
                'url' => \get_home_url($siteId, '/'),
            ],
            \array_map(\intval(...), \get_sites($query))
        ));
    }

    private function name(int $siteId): string
    {
        $name = \get_blog_option($siteId, 'blogname');

        return \html_entity_decode(\is_string($name) ? $name : '', \ENT_QUOTES);
    }

    /**
     * The dependencies and the version generated by the build, null when the assets are not built.
     */
    private function metadata(): ?BuildMetadata
    {
        $file = new BuildMetadataFile(new SplFileInfo($this->buildPath . self::SCRIPT));

        return $file->exists() ? $file->read() : null;
    }
}
