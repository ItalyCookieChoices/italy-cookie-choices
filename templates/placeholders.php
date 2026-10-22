<?php

/**
 * A placeholder for each optional category, the blocking script copies it before each blocked frame.
 *
 * Rendered by italystrap/view, $this is the Config with the data.
 *
 * @see \ItalyCookieChoices\Blocking\UI\Placeholders::data()
 *
 * @var ConfigInterface $this
 */

declare(strict_types=1);

use ItalyStrap\Config\Config;
use ItalyStrap\Config\ConfigInterface;

$preferences = (string) $this->get('preferences');
?>
<?php foreach ((array) $this->get('categories') as $categoryData) : ?>
    <?php
    $category = new Config((array) $categoryData);
    $id = (string) $category->get('id');
    ?>
    <template data-icc-placeholder="<?php echo \esc_attr($id); ?>">
        <div class="icc-placeholder">
            <div class="icc-placeholder__text">
                <?php echo (string) $category->get('message'); // Sanitized with wp_kses_post() or esc_html(). ?>
            </div>
            <div class="icc-placeholder__actions">
                <button type="button" class="icc-placeholder__button" data-icc-accept="<?php echo \esc_attr($id); ?>">
                    <?php echo \esc_html((string) $category->get('button')); ?>
                </button>
                <?php if ($preferences !== '') : ?>
                    <a href="#icc-preferences" class="icc-placeholder__preferences">
                        <?php echo \esc_html($preferences); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </template>
<?php endforeach; ?>
