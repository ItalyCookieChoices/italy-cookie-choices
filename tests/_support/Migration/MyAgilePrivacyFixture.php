<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Migration;

/**
 * The settings and the services of My Agile Privacy, written for the tests from its structure.
 * Its post type is not registered, like when it is deactivated.
 */
final class MyAgilePrivacyFixture
{
    /**
     * @param array<string, mixed> $settings
     */
    public static function settings(array $settings): void
    {
        \update_option('MyAgilePrivacy-1.0.0', $settings);
    }

    /**
     * The texts of the banner the site owner changed, by language.
     *
     * @param array<string, array<string, string>> $texts
     */
    public static function texts(array $texts): string
    {
        return (string) \wp_json_encode($texts);
    }

    /**
     * @param array<string, string> $meta
     */
    public static function service(
        string $name,
        string $key,
        bool $isNecessary = false,
        array $meta = [],
        string $status = 'publish'
    ): int {
        $post = \wp_insert_post([
            'post_type' => 'my-agile-privacy-c',
            'post_title' => $name,
            'post_content' => 'The description of ' . $name,
            'post_status' => $status,
        ]);
        \update_post_meta($post, '_map_api_key', $key);
        \update_post_meta($post, '_map_is_necessary', $isNecessary ? 'necessary' : 'not-necessary');
        foreach ($meta as $metaKey => $value) {
            \update_post_meta($post, $metaKey, $value);
        }

        return $post;
    }
}
