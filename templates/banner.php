<?php

/**
 * The consent banner and the preferences dialog, hidden until the script finds no consent.
 *
 * Rendered by italystrap/view, $this is the Config with the data, read with the array notation.
 *
 * @see \ItalyCookieChoices\Consent\UI\Banner::data()
 *
 * @var \ItalyStrap\Config\ConfigInterface $this
 */

declare(strict_types=1);

$texts = $this['texts'];
?>
<div
    id="icc-banner"
    class="<?php echo \esc_attr($this['classes']['banner']); ?>"
    role="dialog"
    aria-modal="<?php echo $this['modal'] ? 'true' : 'false'; ?>"
    aria-labelledby="icc-banner-title"
    aria-describedby="icc-banner-text"
    tabindex="-1"
    data-reload="<?php echo $this['reload'] ? '1' : '0'; ?>"
    data-html-margin="<?php echo $this['htmlMargin'] ? '1' : '0'; ?>"
    hidden
>
    <div class="<?php echo \esc_attr($this['classes']['content']); ?>">
        <h2 id="icc-banner-title" class="icc-banner__title"><?php echo \esc_html($texts['title'] ?? ''); ?></h2>
        <div id="icc-banner-text" class="<?php echo \esc_attr($this['classes']['text']); ?>">
            <?php echo $this['text']; // Sanitized with wp_kses_post(). ?>
            <?php if ($this['link'] !== null) : ?>
                <a
                    href="<?php echo \esc_url($this['link']['url']); ?>"
                    class="icc-banner__link <?php echo \esc_attr($this['classes']['link']); ?>"
                    <?php echo $this['link']['newTab'] ? 'target="_blank"' : ''; ?>
                    <?php echo $this['link']['rel'] !== '' ? 'rel="' . \esc_attr($this['link']['rel']) . '"' : ''; ?>
                ><?php echo \esc_html($this['link']['text']); ?></a>
            <?php endif; ?>
        </div>
        <div class="icc-banner__actions">
            <button
                type="button"
                class="<?php echo \esc_attr($this['classes']['button']); ?>"
                data-icc-action="accept-all"
            >
                <?php echo \esc_html($texts['acceptAll'] ?? ''); ?>
            </button>
            <button
                type="button"
                class="<?php echo \esc_attr($this['classes']['button']); ?>"
                data-icc-action="reject-all"
            >
                <?php echo \esc_html($texts['rejectAll'] ?? ''); ?>
            </button>
            <button
                type="button"
                class="<?php echo \esc_attr($this['classes']['button']); ?>"
                data-icc-action="preferences"
                aria-controls="icc-preferences"
                aria-expanded="false"
            ><?php echo \esc_html($texts['preferences'] ?? ''); ?></button>
        </div>
        <button
            type="button"
            class="icc-banner__close"
            data-icc-action="close"
            aria-label="<?php echo \esc_attr($texts['close'] ?? ''); ?>"
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
            <?php echo \esc_html($texts['preferencesTitle'] ?? ''); ?>
        </h2>
        <form class="icc-preferences__form">
            <fieldset class="icc-preferences__categories">
                <legend class="icc-visually-hidden"><?php echo \esc_html($texts['preferencesTitle'] ?? ''); ?></legend>
                <?php foreach ($this['categories'] as $category) : ?>
                    <?php $id = 'icc-category-' . $category['id']; ?>
                    <div class="icc-preferences__category">
                        <input
                            type="checkbox"
                            id="<?php echo \esc_attr($id); ?>"
                            name="icc-category"
                            value="<?php echo \esc_attr($category['id']); ?>"
                            <?php if ($category['description'] !== '') : ?>
                                aria-describedby="<?php echo \esc_attr($id . '-description'); ?>"
                            <?php endif; ?>
                            <?php echo $category['required'] ? 'checked disabled' : ''; ?>
                        >
                        <label for="<?php echo \esc_attr($id); ?>"><?php echo \esc_html($category['label']); ?></label>
                        <?php if ($category['description'] !== '') : ?>
                            <p id="<?php echo \esc_attr($id . '-description'); ?>">
                                <?php echo \esc_html($category['description']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </fieldset>
            <div class="icc-preferences__actions">
                <button type="submit" class="<?php echo \esc_attr($this['classes']['button']); ?>">
                    <?php echo \esc_html($texts['save'] ?? ''); ?>
                </button>
                <button
                type="button"
                class="<?php echo \esc_attr($this['classes']['button']); ?>"
                data-icc-action="accept-all"
            >
                    <?php echo \esc_html($texts['acceptAll'] ?? ''); ?>
                </button>
                <button
                type="button"
                class="<?php echo \esc_attr($this['classes']['button']); ?>"
                data-icc-action="reject-all"
            >
                    <?php echo \esc_html($texts['rejectAll'] ?? ''); ?>
                </button>
            </div>
        </form>
        <button
            type="button"
            class="icc-banner__close"
            data-icc-action="close-preferences"
            aria-label="<?php echo \esc_attr($texts['closePreferences'] ?? ''); ?>"
        ><span aria-hidden="true">&times;</span></button>
    </div>
</div>
