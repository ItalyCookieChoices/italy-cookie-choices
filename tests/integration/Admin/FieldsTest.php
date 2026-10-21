<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Admin;

use Italy_Cookie_Choices\Admin\Settings;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class FieldsTest extends WPTestCase
{
    private const OPTION_NAME = 'italy_cookie_choices';

    private const REQUIRED_OPTIONS = ['text' => '', 'url' => '', 'anchor_text' => '', 'button_text' => ''];

    /**
     * @return iterable<string, array{string}>
     */
    public function checkboxes(): iterable
    {
        foreach (['active', 'scroll', 'secondView', 'reload', 'html_margin', 'target'] as $field) {
            yield $field => [$field];
        }
    }

    /**
     * @dataProvider checkboxes
     */
    public function testCheckboxesAreUncheckedByDefault(string $field): void
    {
        $this->assertStringContainsString(
            "<input type='checkbox' name='italy_cookie_choices[$field]'  value='1' "
            . "id=\"italy_cookie_choices[$field]\">",
            $this->renderField($field, [])
        );
    }

    /**
     * @dataProvider checkboxes
     */
    public function testCheckboxesAreCheckedFromTheOptions(string $field): void
    {
        $this->assertStringContainsString(
            "<input type='checkbox' name='italy_cookie_choices[$field]'  checked='checked' value='1'",
            $this->renderField($field, [$field => '1'])
        );
    }

    public function testTheTopBarIsTheDefaultBanner(): void
    {
        $output = $this->renderField('banner', []);

        $this->assertStringContainsString('value="1" id="radio_1"  checked=\'checked\' />', $output);
        $this->assertStringContainsString('value="2" id="radio_2"  />', $output);
        $this->assertStringContainsString('value="3" id="radio_3"  />', $output);
    }

    public function testTheBannerIsCheckedFromTheOptions(): void
    {
        $output = $this->renderField('banner', ['banner' => '3']);

        $this->assertStringContainsString('value="1" id="radio_1"  />', $output);
        $this->assertStringContainsString('value="3" id="radio_3"  checked=\'checked\' />', $output);
    }

    public function testTheDefaultTemplateIsSelectedByDefault(): void
    {
        $output = $this->renderField('js_template', []);

        $this->assertStringContainsString('<option value="default" selected>', $output);
        $this->assertSame(1, \substr_count($output, 'selected'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function templates(): iterable
    {
        foreach (['default', 'bigbutton', 'smallbutton', 'custom'] as $template) {
            yield $template => [$template];
        }
    }

    /**
     * @dataProvider templates
     */
    public function testTheTemplateIsSelectedFromTheOptions(string $template): void
    {
        $output = $this->renderField('js_template', ['js_template' => $template]);

        $this->assertStringContainsString('<option value="' . $template . '" selected>', $output);
        $this->assertSame(1, \substr_count($output, 'selected'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public function textInputs(): iterable
    {
        yield 'slug' => ['slug', 'slug'];
        yield 'anchor text' => ['anchor_text', 'anchor_text'];
        yield 'button text' => ['button_text', 'button_text'];
        yield 'banner background' => ['banner_bg', 'banner_bg'];
        yield 'banner text color' => ['banner_text_color', 'banner_text_color'];
        yield 'cookie name' => ['cookie_name', 'cookie_name'];
        yield 'cookie value' => ['cookie_value', 'cookie_value'];
        yield 'content message button' => ['content_message_button_text', 'content_message_button_text'];
        yield 'banner style' => ['customClass', 'bannerStyle'];
        yield 'content style' => ['customClass', 'contentStyle'];
        yield 'consent text' => ['customClass', 'consentText'];
        yield 'info class' => ['customClass', 'infoClass'];
        yield 'close class' => ['customClass', 'closeClass'];
    }

    /**
     * @dataProvider textInputs
     */
    public function testTextInputsShowTheEscapedOption(string $callback, string $field): void
    {
        $this->assertStringContainsString(
            'name="italy_cookie_choices[' . $field . ']" value="say &quot;hi&quot;"',
            $this->renderField($callback, [$field => 'say "hi"'])
        );
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public function textInputDefaults(): iterable
    {
        yield 'slug' => ['slug', 'slug', ''];
        yield 'banner background' => ['banner_bg', 'banner_bg', '#fff'];
        yield 'banner text color' => ['banner_text_color', 'banner_text_color', '#000'];
        yield 'cookie name' => ['cookie_name', 'cookie_name', 'displayCookieConsent'];
        yield 'cookie value' => ['cookie_value', 'cookie_value', 'y'];
        yield 'content message button' => ['content_message_button_text', 'content_message_button_text', ''];
        yield 'banner style' => ['customClass', 'bannerStyle', 'bannerStyle'];
        yield 'content style' => ['customClass', 'contentStyle', 'contentStyle'];
        yield 'consent text' => ['customClass', 'consentText', 'consentText'];
        yield 'info class' => ['customClass', 'infoClass', 'italybtn'];
        yield 'close class' => ['customClass', 'closeClass', 'italybtn'];
    }

    /**
     * @dataProvider textInputDefaults
     */
    public function testTextInputsHaveDefaults(string $callback, string $field, string $default): void
    {
        $this->assertStringContainsString(
            'name="italy_cookie_choices[' . $field . ']" value="' . $default . '"',
            $this->renderField($callback, [])
        );
    }

    public function testCookiePlaceholdersShowTheDefaults(): void
    {
        $this->assertStringContainsString(
            'value="custom" placeholder="displayCookieConsent"',
            $this->renderField('cookie_name', ['cookie_name' => 'custom'])
        );
        $this->assertStringContainsString(
            'value="custom" placeholder="y"',
            $this->renderField('cookie_value', ['cookie_value' => 'custom'])
        );
    }

    public function testTheUrlIsEscaped(): void
    {
        $this->assertStringContainsString(
            'name="italy_cookie_choices[url]" value=""',
            $this->renderField('url', ['url' => 'javascript:alert(1)'])
        );
        $this->assertStringContainsString(
            'name="italy_cookie_choices[url]" value="https://example.com/policy/"',
            $this->renderField('url', ['url' => 'https://example.com/policy/'])
        );
    }

    public function testTheUrlAndSlugFieldsListThePages(): void
    {
        $page = self::factory()->post->create(['post_type' => 'page', 'post_name' => 'cookie-policy']);
        $this->assertIsInt($page);

        $this->assertStringContainsString(
            "var urls = '" . \wp_json_encode([\get_permalink($page)]) . "';",
            $this->renderField('url', [])
        );
        $this->assertStringContainsString(
            "var slugs = '[\"cookie-policy\"]';",
            $this->renderField('slug', [])
        );
    }

    public function testTheTextUsesTheEditor(): void
    {
        $output = $this->renderField('text', ['text' => 'Hello <strong>world</strong><script>x</script>']);

        $this->assertStringContainsString('name="italy_cookie_choices[text]"', $output);
        $this->assertStringContainsString('id="italy_cookie_choices_text"', $output);
        $this->assertStringContainsString('>Hello <strong>world</strong>x</textarea>', $output);
    }

    public function testTheContentMessageUsesTheEditor(): void
    {
        $output = $this->renderField('content_message_text', ['content_message_text' => 'Blocked <em>content</em>']);

        $this->assertStringContainsString('name="italy_cookie_choices[content_message_text]"', $output);
        $this->assertStringContainsString('id="italy_cookie_choices_content_message_text"', $output);
        $this->assertStringContainsString('>Blocked <em>content</em></textarea>', $output);
    }

    public function testTheCustomCssIsEscaped(): void
    {
        $this->assertStringContainsString(
            '>a &gt; b {}</textarea>',
            $this->renderField('customCSS', ['customCSS' => 'a > b {}'])
        );
    }

    public function testBlockCheckboxesAreCheckedFromTheOptions(): void
    {
        $output = $this->renderField('block', ['block' => '1', 'widget_block' => '1']);

        $this->assertStringContainsString("name='italy_cookie_choices[block]'  checked='checked'", $output);
        $this->assertStringContainsString("name='italy_cookie_choices[widget_block]'  checked='checked'", $output);
        $this->assertStringContainsString("name='italy_cookie_choices[all_block]'  value='1'", $output);
    }

    public function testAllBlockUnchecksTheOtherBlocks(): void
    {
        $output = $this->renderField('block', ['block' => '1', 'widget_block' => '1', 'all_block' => '1']);

        $this->assertStringContainsString("name='italy_cookie_choices[block]'  value='1'", $output);
        $this->assertStringContainsString("name='italy_cookie_choices[widget_block]'  value='1'", $output);
        $this->assertStringContainsString("name='italy_cookie_choices[all_block]'  checked='checked'", $output);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public function scriptTextareas(): iterable
    {
        yield 'allowed in body' => ['custom_script_block_body_exclude', 'allow'];
        yield 'blocked' => ['custom_script_block', 'block'];
    }

    /**
     * @dataProvider scriptTextareas
     */
    public function testScriptTextareasShowTheEscapedOption(string $field, string $prefix): void
    {
        $output = $this->renderField($field, [$field => '<script src="a.js"></script>']);

        $this->assertStringContainsString(
            '>&lt;script src=&quot;a.js&quot;&gt;&lt;/script&gt;</textarea>',
            $output
        );
        $this->assertStringContainsString('data-value="<---------SEP--------->"', $output);
        $this->assertStringContainsString('data-value="<---------SOMETHING--------->"', $output);
        $this->assertStringContainsString('name="italy_cookie_choices[' . $prefix . '_iframe][]"', $output);
        $this->assertStringContainsString('name="italy_cookie_choices[' . $prefix . '_script][]"', $output);
        $this->assertStringContainsString('name="italy_cookie_choices[' . $prefix . '_embed][]"', $output);
    }

    /**
     * @dataProvider scriptTextareas
     */
    public function testScriptListsShowTheSavedVendors(string $field, string $prefix): void
    {
        $output = $this->renderField($field, [$prefix . '_script' => ['analytics', 'facebook']]);

        $this->assertStringContainsString('name="italy_cookie_choices[' . $prefix . '_script][analytics]"', $output);
        $this->assertStringContainsString('name="italy_cookie_choices[' . $prefix . '_script][facebook]"', $output);
    }

    public function testForeachScriptPrintsOneRowPerValue(): void
    {
        \ob_start();
        (new Settings())->foreach_script(['first', 'sec"ond'], 'allow_script', ['' => '', 'Google' => 'google']);
        $output = (string) \ob_get_clean();

        $select = '<select><option value=""></option><option value="google">Google</option></select>';
        $this->assertSame(
            '<div class="custom-script">'
            . '<div class="italy-cookie-choices-clone-div">'
            . '<input type="text" class="regular-text" data-type="allow_script" '
            . 'value="first" name="italy_cookie_choices[allow_script][first]" '
            . 'id="italy_cookie_choices[allow_script][first]"/>'
            . $select . '<span><a class="button add" style="font-size:22px"> + </a>'
            . '<a class="button cancel" style="font-size:22px"> × </a></span></div>'
            . '<div class="italy-cookie-choices-clone-div">'
            . '<input type="text" class="regular-text" data-type="allow_script" '
            . 'value="sec&quot;ond" name="italy_cookie_choices[allow_script][sec&quot;ond]" '
            . 'id="italy_cookie_choices[allow_script][sec&quot;ond]"/>'
            . $select . '<span><a class="button add" style="font-size:22px"> + </a>'
            . '<a class=" button remove" style="font-size:22px"> × </a></span></div>'
            . '</div>',
            $output
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    private function renderField(string $field, array $options): string
    {
        \update_option(self::OPTION_NAME, \array_merge(self::REQUIRED_OPTIONS, $options));
        $sut = new Settings();
        $sut->italy_cl_settings_init();

        \ob_start();
        $sut->{'italy_cl_option_' . $field}([]);

        return (string) \ob_get_clean();
    }
}
