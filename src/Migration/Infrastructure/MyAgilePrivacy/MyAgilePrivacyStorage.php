<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\MyAgilePrivacy;

use ItalyCookieChoices\Migration\Infrastructure\UnregisteredContent;

/**
 * Reads the settings My Agile Privacy keeps in the database, also when it is deactivated: its settings option
 * and its services, posts of its type. A published service is in use, the others are only detected.
 *
 * @phpstan-type Service array{name: string, key: string, isNecessary: bool, code: string}
 */
final readonly class MyAgilePrivacyStorage
{
    private const SETTINGS = 'MyAgilePrivacy-1.0.0';
    private const SERVICES = 'my-agile-privacy-c';
    private const NECESSARY = 'necessary';

    /** The code of a service is JavaScript, with the HTML for no JavaScript, or the raw HTML. */
    private const SCRIPT_CODE = 'js_noscript';
    private const RAW_CODE = 'raw_code';

    public function __construct(
        private UnregisteredContent $content,
    ) {
    }

    public function hasSettings(): bool
    {
        return \is_array(\get_option(self::SETTINGS));
    }

    /**
     * @return array<array-key, mixed>
     */
    public function settings(): array
    {
        $settings = \get_option(self::SETTINGS);

        return \is_array($settings) ? $settings : [];
    }

    /**
     * The services in use, with their code as HTML.
     *
     * @return list<Service>
     */
    public function services(): array
    {
        if (!$this->hasSettings()) {
            return [];
        }

        $services = [];
        foreach ($this->content->posts(self::SERVICES) as $post) {
            $services[] = [
                'name' => $post['title'],
                'key' => $this->meta($post['id'], '_map_api_key'),
                'isNecessary' => $this->meta($post['id'], '_map_is_necessary') === self::NECESSARY,
                'code' => $this->code($post['id']),
            ];
        }

        return $services;
    }

    /**
     * The code My Agile Privacy runs after the consent, as it prints it: the JavaScript without tags
     * in a script tag, the HTML for no JavaScript decoded.
     */
    private function code(int $post): string
    {
        $type = $this->meta($post, '_map_installation_type');
        if ($type === self::RAW_CODE) {
            return $this->meta($post, '_map_raw_code');
        }

        $script = \trim(\strip_tags($this->meta($post, '_map_code')));
        if ($type !== self::SCRIPT_CODE || $script === '') {
            return '';
        }

        $noscript = \htmlspecialchars_decode($this->meta($post, '_map_noscript'), \ENT_QUOTES);

        return \sprintf("<script>\n%s\n</script>", $script)
            . ($noscript === '' ? '' : \sprintf("\n<noscript>%s</noscript>", $noscript));
    }

    private function meta(int $post, string $key): string
    {
        $value = \get_post_meta($post, $key, true);

        return \is_scalar($value) ? \trim((string) $value) : '';
    }
}
