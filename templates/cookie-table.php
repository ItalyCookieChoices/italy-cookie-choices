<?php

/**
 * The cookie table of the cookie policy, a table for each category with cookies.
 *
 * Rendered by italystrap/view, $this is the Config with the data.
 *
 * @see \ItalyCookieChoices\Cookies\UI\CookieTableRenderer::data()
 *
 * @var ConfigInterface $this
 */

declare(strict_types=1);

use ItalyStrap\Config\Config;
use ItalyStrap\Config\ConfigInterface;

$columns = new Config((array) $this->get('columns'));
?>
<div class="icc-cookie-table">
    <?php foreach ((array) $this->get('categories') as $categoryData) : ?>
        <?php $category = new Config((array) $categoryData); ?>
        <table class="icc-cookie-table__table">
            <caption><?php echo \esc_html((string) $category->get('label')); ?></caption>
            <thead>
                <tr>
                    <th scope="col"><?php echo \esc_html((string) $columns->get('cookie')); ?></th>
                    <th scope="col"><?php echo \esc_html((string) $columns->get('provider')); ?></th>
                    <th scope="col"><?php echo \esc_html((string) $columns->get('purpose')); ?></th>
                    <th scope="col"><?php echo \esc_html((string) $columns->get('duration')); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ((array) $category->get('cookies') as $cookieData) : ?>
                    <?php $cookie = new Config((array) $cookieData); ?>
                    <tr>
                        <td><?php echo \esc_html((string) $cookie->get('name')); ?></td>
                        <td><?php echo \esc_html((string) $cookie->get('provider')); ?></td>
                        <td><?php echo \esc_html((string) $cookie->get('purpose')); ?></td>
                        <td><?php echo \esc_html((string) $cookie->get('duration')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
</div>
