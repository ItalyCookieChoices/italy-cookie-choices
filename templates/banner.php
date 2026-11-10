<?php

/**
 * The consent banner and the preferences dialog, hidden until the script finds no consent.
 *
 * Rendered by italystrap/view, $this is the Config with the data, nested keys use an array of segments.
 *
 * @see \ItalyCookieChoices\Consent\UI\Banner::data()
 *
 * @var ConfigInterface $this
 */

declare(strict_types=1);

use ItalyStrap\Config\Config;
use ItalyStrap\Config\ConfigInterface;

$buttonClass = (string) $this->get(['classes', 'button']);
$linkUrl = (string) $this->get(['link', 'url']);
$linkRel = (string) $this->get(['link', 'rel']);
$globalPrivacyControl = (bool) $this->get('globalPrivacyControl');
$globalPrivacyControlNote = (string) $this->get(['texts', 'globalPrivacyControl']);
?>
<div
    id="icc-banner"
    class="<?php echo \esc_attr((string) $this->get(['classes', 'banner'])); ?>"
    role="dialog"
    aria-modal="<?php echo (bool) $this->get('modal') ? 'true' : 'false'; ?>"
    aria-labelledby="icc-banner-title"
    aria-describedby="icc-banner-text"
    tabindex="-1"
    data-reload="<?php echo (bool) $this->get('reload') ? '1' : '0'; ?>"
    data-html-margin="<?php echo (bool) $this->get('htmlMargin') ? '1' : '0'; ?>"
    data-opens-by-itself="<?php echo (bool) $this->get('opensByItself') ? '1' : '0'; ?>"
    data-global-privacy-control="<?php echo $globalPrivacyControl ? '1' : '0'; ?>"
    hidden
>
    <div class="<?php echo \esc_attr((string) $this->get(['classes', 'content'])); ?>">
        <?php echo (string) $this->get('logo'); // Escaped by wp_get_attachment_image(). ?>
        <h2 id="icc-banner-title" class="icc-banner__title">
            <?php echo \esc_html((string) $this->get(['texts', 'title'])); ?>
        </h2>
        <div id="icc-banner-text" class="<?php echo \esc_attr((string) $this->get(['classes', 'text'])); ?>">
            <?php echo (string) $this->get('text'); // Sanitized with wp_kses_post(). ?>
            <?php if ($linkUrl !== '') : ?>
                <a
                    href="<?php echo \esc_url($linkUrl); ?>"
                    class="icc-banner__link <?php echo \esc_attr((string) $this->get(['classes', 'link'])); ?>"
                    <?php echo (bool) $this->get(['link', 'newTab']) ? 'target="_blank"' : ''; ?>
                    <?php echo $linkRel !== '' ? 'rel="' . \esc_attr($linkRel) . '"' : ''; ?>
                ><?php echo \esc_html((string) $this->get(['link', 'text'])); ?></a>
            <?php endif; ?>
        </div>
        <?php if ($globalPrivacyControl) : ?>
            <p class="icc-banner__gpc" data-icc-gpc-note hidden><?php echo \esc_html($globalPrivacyControlNote); ?></p>
        <?php endif; ?>
        <div class="icc-banner__actions">
            <button type="button" class="<?php echo \esc_attr($buttonClass); ?>" data-icc-action="accept-all">
                <?php echo \esc_html((string) $this->get(['texts', 'acceptAll'])); ?>
            </button>
            <button type="button" class="<?php echo \esc_attr($buttonClass); ?>" data-icc-action="reject-all">
                <?php echo \esc_html((string) $this->get(['texts', 'rejectAll'])); ?>
            </button>
            <button
                type="button"
                class="<?php echo \esc_attr($buttonClass); ?>"
                data-icc-action="preferences"
                aria-controls="icc-preferences"
                aria-expanded="false"
            ><?php echo \esc_html((string) $this->get(['texts', 'preferences'])); ?></button>
        </div>
        <button
            type="button"
            class="icc-banner__close"
            data-icc-action="close"
            aria-label="<?php echo \esc_attr((string) $this->get(['texts', 'close'])); ?>"
        ><span aria-hidden="true">&times;</span></button>
    </div>
</div>
<div
    id="icc-preferences"
    class="icc-preferences"
    role="dialog"
    aria-modal="true"
    aria-labelledby="icc-preferences-title"
    tabindex="-1"
    hidden
>
    <div class="icc-preferences__panel">
        <h2 id="icc-preferences-title" class="icc-preferences__title">
            <?php echo \esc_html((string) $this->get(['texts', 'preferencesTitle'])); ?>
        </h2>
        <?php if ($globalPrivacyControl) : ?>
            <p class="icc-banner__gpc" data-icc-gpc-note hidden><?php echo \esc_html($globalPrivacyControlNote); ?></p>
        <?php endif; ?>
        <form class="icc-preferences__form">
            <fieldset class="icc-preferences__categories">
                <legend class="icc-visually-hidden">
                    <?php echo \esc_html((string) $this->get(['texts', 'preferencesTitle'])); ?>
                </legend>
                <?php foreach ((array) $this->get('categories') as $categoryData) : ?>
                    <?php
                    $category = new Config((array) $categoryData);
                    $id = 'icc-category-' . (string) $category->get('id');
                    $description = (string) $category->get('description');
                    ?>
                    <div class="icc-preferences__category">
                        <input
                            type="checkbox"
                            id="<?php echo \esc_attr($id); ?>"
                            name="icc-category"
                            value="<?php echo \esc_attr((string) $category->get('id')); ?>"
                            <?php if ($description !== '') : ?>
                                aria-describedby="<?php echo \esc_attr($id . '-description'); ?>"
                            <?php endif; ?>
                            <?php echo (bool) $category->get('required') ? 'checked disabled' : ''; ?>
                        >
                        <label for="<?php echo \esc_attr($id); ?>">
                            <?php echo \esc_html((string) $category->get('label')); ?>
                        </label>
                        <?php if ($description !== '') : ?>
                            <p id="<?php echo \esc_attr($id . '-description'); ?>">
                                <?php echo \esc_html($description); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </fieldset>
            <div class="icc-preferences__actions">
                <button type="submit" class="<?php echo \esc_attr($buttonClass); ?>">
                    <?php echo \esc_html((string) $this->get(['texts', 'save'])); ?>
                </button>
                <button type="button" class="<?php echo \esc_attr($buttonClass); ?>" data-icc-action="accept-all">
                    <?php echo \esc_html((string) $this->get(['texts', 'acceptAll'])); ?>
                </button>
                <button type="button" class="<?php echo \esc_attr($buttonClass); ?>" data-icc-action="reject-all">
                    <?php echo \esc_html((string) $this->get(['texts', 'rejectAll'])); ?>
                </button>
            </div>
        </form>
        <button
            type="button"
            class="icc-banner__close"
            data-icc-action="close-preferences"
            aria-label="<?php echo \esc_attr((string) $this->get(['texts', 'closePreferences'])); ?>"
        ><span aria-hidden="true">&times;</span></button>
    </div>
</div>
<?php $floatingButton = $this->get('floatingButton'); ?>
<?php if (\is_array($floatingButton)) : ?>
    <?php
    $floatingButton = new Config($floatingButton);
    $floatingPosition = (string) $floatingButton->get('position');
    ?>
    <button
        type="button"
        id="icc-floating-button"
        class="icc-floating-button icc-floating-button--<?php echo \esc_attr($floatingPosition); ?>"
        aria-label="<?php echo \esc_attr((string) $floatingButton->get('label')); ?>"
        title="<?php echo \esc_attr((string) $floatingButton->get('label')); ?>"
        data-icc-open-preferences
        hidden
    >
        <svg aria-hidden="true" focusable="false" width="24" height="24" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/>
            <circle cx="9" cy="9.5" r="1.5" fill="currentColor"/>
            <circle cx="14.5" cy="8.5" r="1.25" fill="currentColor"/>
            <circle cx="14.5" cy="14.5" r="1.5" fill="currentColor"/>
            <circle cx="9.5" cy="15.5" r="1" fill="currentColor"/>
        </svg>
    </button>
<?php endif; ?>
