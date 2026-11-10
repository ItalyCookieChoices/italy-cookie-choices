<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI;

use ItalyCookieChoices\Blocking\Application\VendorPresets;
use ItalyCookieChoices\Consent\UI\ConsentTexts;
use ItalyCookieChoices\ConsentLog\UI\ConsentLogExport;
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
 * The settings page, a React app that saves the option through the REST API.
 *
 * The page gets only the data it needs, the policy page is searched when the site owner types (#154).
 */
final readonly class SettingsPage implements SubscriberInterface
{
    public const SLUG = 'italy-cookie-choices';
    public const HANDLE = 'italy-cookie-choices-admin';
    public const CAPABILITY = 'manage_options';
    public const DOCUMENTATION = 'https://github.com/ItalyCookieChoices/italy-cookie-choices/wiki';

    private const EVENT_NAME_SUFFIX = 'toplevel_page_' . self::SLUG;
    private const ICON = 'dashicons-privacy';
    /**
     * Below the Settings menu.
     */
    private const POSITION = 81;
    private const SCRIPT = 'settings.js';
    private const STYLE = 'settings.css';
    private const TEMPLATE = 'settings-page';

    /**
     * @param string $buildPath The assets/build folder, with the trailing slash.
     * @param string $buildUrl The assets/build URL, with the trailing slash.
     * @param string $languagesPath The folder of the translations bundled with the plugin.
     */
    public function __construct(
        private ConsentTexts $texts,
        private VendorPresets $presets,
        private ConsentLogExport $logExport,
        private ViewInterface $view,
        private AssetLoader $assets,
        private string $buildPath,
        private string $buildUrl,
        private string $languagesPath,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'admin_menu' => 'addPage';
        yield 'admin_init' => 'redirectTheOldUrl';
        yield EventName::ADMIN => 'enqueue';
    }

    public function addPage(): void
    {
        \add_menu_page(
            \__('Cookie Choices', 'italy-cookie-choices'),
            \__('Cookie Choices', 'italy-cookie-choices'),
            self::CAPABILITY,
            self::SLUG,
            $this->render(...),
            self::ICON,
            self::POSITION
        );
    }

    public function url(): string
    {
        return \admin_url('admin.php?page=' . self::SLUG);
    }

    /**
     * The 2.x versions had the page under Settings, the old bookmarks and links open the new one.
     */
    public function redirectTheOldUrl(): void
    {
        if (!$this->isTheOldUrl()) {
            return;
        }

        \wp_safe_redirect($this->url());
        exit;
    }

    public function isTheOldUrl(): bool
    {
        global $pagenow, $plugin_page;

        return $pagenow === 'options-general.php' && $plugin_page === self::SLUG;
    }

    public function render(): void
    {
        echo $this->view->render( // phpcs:ignore -- Escaped in the template.
            self::TEMPLATE,
            new Config(['title' => \get_admin_page_title()])
        );
    }

    public function enqueue(mixed $eventNameSuffix = ''): void
    {
        $metadata = $this->metadata();
        if ($metadata === null || $eventNameSuffix !== self::EVENT_NAME_SUFFIX) {
            return;
        }

        // The media library of the logo of the banner.
        \wp_enqueue_media();

        $this->assets->load(
            new Script(
                handle: new Handle(self::HANDLE),
                source: $this->buildUrl . self::SCRIPT,
                dependencies: $metadata->dependencies(),
                version: $metadata->version(),
                loading: new ScriptLoading(inFooter: true),
                inlineScripts: [new InlineScript(
                    \sprintf('window.italyCookieChoicesAdmin = %s;', (string) \wp_json_encode($this->data())),
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
     * The data the page cannot read from the REST API.
     *
     * @return array{
     *     categories: list<array{id: string, required: bool, label: string, description: string}>,
     *     vendors: list<array{id: string, name: string, category: string}>,
     *     consentLogExportUrl: string,
     *     documentationUrl: string
     * }
     */
    public function data(): array
    {
        $vendors = [];
        foreach ($this->presets->all() as $vendor) {
            $vendors[] = [
                'id' => $vendor->id(),
                'name' => $this->presets->name($vendor->id()),
                'category' => $vendor->category(),
            ];
        }

        return [
            'categories' => $this->texts->defaultCategories(),
            'vendors' => $vendors,
            'consentLogExportUrl' => $this->logExport->url(),
            'documentationUrl' => self::DOCUMENTATION,
        ];
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
