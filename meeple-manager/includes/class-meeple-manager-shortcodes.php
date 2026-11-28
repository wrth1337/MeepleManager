<?php

if (!defined('ABSPATH')) {
    exit;
}

class Meeple_Manager_Shortcodes {
    public function register() {
        add_shortcode('game-list', array($this, 'render_public_catalog'));
        add_shortcode('meeple_game_list', array($this, 'render_public_catalog'));
        add_shortcode('game-lending', array($this, 'render_lending_area'));
        add_shortcode('meeple-lending', array($this, 'render_lending_area'));
        add_shortcode('meeple_lending', array($this, 'render_lending_area'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'));
    }

    public function render_public_catalog() {
        $games = get_posts(
            array(
                'post_type' => MEEPLE_MANAGER_GAME_POST_TYPE,
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'meta_query' => array(
                    array(
                        'key' => MEEPLE_MANAGER_META_INVENTORY_NUMBER,
                        'compare' => 'EXISTS',
                    ),
                ),
            )
        );

        $this->enqueue_styles();

        if (empty($games)) {
            return '<p class="meeple-manager-empty">Derzeit sind keine Spiele eingetragen.</p>';
        }

        $favorite_games = array();
        $regular_games = array();

        foreach ($games as $game) {
            if (get_post_meta($game->ID, MEEPLE_MANAGER_META_FAVORITE, true) === '1') {
                $favorite_games[] = $game;
            } else {
                $regular_games[] = $game;
            }
        }

        $games = array_merge($favorite_games, $regular_games);
        $game_styles = Meeple_Manager_Game_Style_Settings::get_settings();
        $placeholder_image_id = (int) $game_styles['placeholder_image_id'];
        $placeholder_image = $placeholder_image_id > 0
            ? wp_get_attachment_image(
                $placeholder_image_id,
                'full',
                false,
                array(
                    'class' => 'meeple-manager-game-card__image meeple-manager-game-card__image--placeholder',
                    'alt' => '',
                )
            )
            : '';
        $output = '<div class="meeple-manager-catalog-wrap"><ul class="meeple-manager-catalog">';

        foreach ($games as $game) {
            $image_position = array();
            foreach (array(
                'x' => MEEPLE_MANAGER_META_IMAGE_POSITION_X,
                'y' => MEEPLE_MANAGER_META_IMAGE_POSITION_Y,
            ) as $axis => $meta_key) {
                $position = get_post_meta($game->ID, $meta_key, true);
                if ($position !== '') {
                    $image_position[] = '--meeple-manager-game-image-position-' . $axis . ':' . min(100, absint($position)) . '%';
                }
            }

            $image = get_the_post_thumbnail(
                $game->ID,
                'full',
                array_merge(
                    array(
                        'class' => 'meeple-manager-game-card__image',
                        'alt' => $game->post_title,
                    ),
                    !empty($image_position) ? array('style' => implode(';', $image_position) . ';') : array()
                )
            );

            $is_placeholder = false;
            if (!is_string($image) || $image === '') {
                $is_placeholder = true;
                $image = $placeholder_image !== ''
                    ? $placeholder_image
                    : '<div class="meeple-manager-game-card__placeholder">Kein Bild hinterlegt</div>';
            }

            $output .= '<li class="meeple-manager-catalog__item">';
            $output .= '<div class="meeple-manager-game-card__image-wrap' . ($is_placeholder ? ' meeple-manager-game-card__image-wrap--placeholder' : '') . '">' . $image;
            if (get_post_meta($game->ID, MEEPLE_MANAGER_META_FAVORITE, true) === '1') {
                $output .= self::render_favorite_badge();
            }
            $output .= '</div>';
            $output .= '<h2 class="meeple-manager-catalog__title">' . esc_html($game->post_title) . '</h2>';
            $output .= '</li>';
        }

        $output .= '</ul></div>';

        return $output;
    }

    public static function render_favorite_badge() {
        return '<span class="meeple-manager-favorite-badge" role="img" aria-label="Aktueller Favorit" title="Heiß" tabindex="0">'
            . '<svg class="meeple-manager-favorite-badge__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
            . '<path class="meeple-manager-favorite-badge__flame-outer" d="M12 23a8 8 0 0 1-8-8c0-3.6 2.1-6.7 5.4-8.1.2 2.1 1.4 3.4 2.5 4.2C13 7.5 12.9 4.1 11.5 1 16.7 4.7 20 9.2 20 15a8 8 0 0 1-8 8Z"/>'
            . '<path class="meeple-manager-favorite-badge__flame-inner" d="M12 20a4.5 4.5 0 0 1-4.5-4.5c0-1.7.9-3.2 2.3-4 .3 1.1 1 1.8 1.9 2.2.8-1.1 1.1-2.6.8-4.2 2.3 1.8 4 3.8 4 6A4.5 4.5 0 0 1 12 20Z"/>'
            . '</svg><span class="meeple-manager-favorite-badge__tooltip" aria-hidden="true">Heiß</span></span>';
    }

    public function render_lending_area() {
        global $post;

        if (!$post instanceof WP_Post || !is_singular() || $post->post_password === '') {
            return '<p class="meeple-manager-notice">Der Verleihbereich muss auf einer passwortgeschützten WordPress-Seite eingebunden werden.</p>';
        }

        if (post_password_required($post)) {
            return get_the_password_form($post);
        }

        $games = get_posts(
            array(
                'post_type' => MEEPLE_MANAGER_GAME_POST_TYPE,
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'meta_query' => array(
                    array(
                        'key' => MEEPLE_MANAGER_META_INVENTORY_NUMBER,
                        'compare' => 'EXISTS',
                    ),
                ),
            )
        );

        $active_by_game = Meeple_Manager_Rentals::get_active_loan_map();

        $this->enqueue_styles();
        $output = $this->render_request_notice();
        $output .= '<div class="meeple-manager-lending">';

        if (empty($games)) {
            $output .= '<p class="meeple-manager-empty">Derzeit sind keine Spiele eingetragen.</p></div>';
            return $output;
        }

        wp_enqueue_script(
            'meeple-manager-game-lending-table',
            MEEPLE_MANAGER_URL . 'assets/js/meeple-manager-game-lending-table.js',
            array(),
            MEEPLE_MANAGER_VERSION,
            true
        );

        $output .= '<div class="meeple-manager-table-wrap">';
        $output .= '<div class="meeple-manager-table-controls">';
        $output .= '<label>Spiel nach Titel suchen<input type="search" data-game-title-search placeholder="Titel eingeben" autocomplete="off"></label>';
        $output .= '<div class="meeple-manager-mobile-sort-controls" role="group" aria-label="Spiele sortieren">';
        $output .= '<span>Sortieren:</span>';
        $output .= '<button type="button" class="meeple-manager-sort-button" data-sort-button="inventory" aria-label="Nach Inventarnummer aufsteigend sortieren">Inventarnummer <span class="meeple-manager-sort-indicator" aria-hidden="true">↕</span></button>';
        $output .= '<button type="button" class="meeple-manager-sort-button" data-sort-button="title" aria-label="Nach Titel aufsteigend sortieren">Titel <span class="meeple-manager-sort-indicator" aria-hidden="true">↑</span></button>';
        $output .= '</div></div>';
        $output .= '<table class="meeple-manager-table" data-meeple-manager-game-table data-sort-column="title" data-sort-direction="asc">';
        $output .= '<thead><tr>';
        $output .= '<th scope="col" data-sort-header="inventory" aria-sort="none">Inventarnummer <button type="button" class="meeple-manager-sort-button" data-sort-button="inventory" aria-label="Nach Inventarnummer aufsteigend sortieren"><span class="meeple-manager-sort-indicator" aria-hidden="true">↕</span></button></th>';
        $output .= '<th scope="col" data-sort-header="title" aria-sort="ascending">Titel <button type="button" class="meeple-manager-sort-button" data-sort-button="title" aria-label="Nach Titel aufsteigend sortieren"><span class="meeple-manager-sort-indicator" aria-hidden="true">↑</span></button></th>';
        $output .= '<th scope="col">Kategorie</th><th scope="col">Zustand</th><th scope="col">Verfügbarkeit</th><th scope="col">Voraussichtliche Rückgabe</th></tr></thead><tbody>';
        $requestable_games = array();
        $categories = Meeple_Manager_Content_Types::get_categories();

        foreach ($games as $game) {
            $game_id = (int) $game->ID;
            $category_key = get_post_meta($game_id, MEEPLE_MANAGER_META_GAME_CATEGORY, true);
            if (!isset($categories[$category_key])) {
                $category_key = 'green';
            }
            $category = $categories[$category_key];
            $inventory_number = get_post_meta($game_id, MEEPLE_MANAGER_META_INVENTORY_NUMBER, true);
            $condition = get_post_meta($game_id, MEEPLE_MANAGER_META_CONDITION, true);
            $loan = isset($active_by_game[$game_id]) ? $active_by_game[$game_id] : null;
            $due_date = $loan ? $loan['due_date'] : '';

            if ($category_key === 'red') {
                $availability = 'Nicht ausleihbar';
            } elseif ($loan) {
                $availability = 'Ausgeliehen';
            } elseif ($category_key === 'yellow') {
                $availability = 'Verfügbar – Freigabe erforderlich';
                $requestable_games[] = $game;
            } else {
                $availability = 'Verfügbar';
                $requestable_games[] = $game;
            }

            $output .= '<tr data-game-title="' . esc_attr($game->post_title) . '" data-inventory-number="' . esc_attr($inventory_number) . '">';
            $output .= '<td data-label="Inventarnummer">' . esc_html($inventory_number) . '</td>';
            $output .= '<td data-label="Titel">' . esc_html($game->post_title) . '</td>';
            $output .= '<td data-label="Kategorie"><span class="meeple-manager-category meeple-manager-category--' . esc_attr($category_key) . '">' . esc_html($category['short_label']) . '</span></td>';
            $output .= '<td data-label="Zustand">' . ($condition !== '' ? nl2br(esc_html($condition)) : '–') . '</td>';
            $output .= '<td data-label="Verfügbarkeit">' . esc_html($availability) . '</td>';
            $output .= '<td data-label="Voraussichtliche Rückgabe">' . ($due_date !== '' ? esc_html($this->format_date($due_date)) : '–') . '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody></table><p class="meeple-manager-table-no-results" data-game-title-no-results role="status" hidden>Kein Spiel mit diesem Titel gefunden.</p></div>';
        $output .= $this->render_request_form($requestable_games);
        $output .= '</div>';

        return $output;
    }

    private function render_request_form($games) {
        if (empty($games)) {
            return '<p class="meeple-manager-empty">Aktuell können keine Ausleihanfragen gestellt werden.</p>';
        }

        ob_start();
        ?>
        <section class="meeple-manager-request">
            <h2>Ausleihanfrage stellen</h2>
            <p>Die Anfrage ist unverbindlich. Der Spielewart bestätigt die Ausleihe persönlich.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="meeple_manager_submit_request">
                <?php wp_nonce_field('meeple_manager_submit_request', 'meeple_manager_request_nonce'); ?>
                <p>
                    <label for="meeple-request-game">Spiel</label>
                    <select id="meeple-request-game" name="meeple_game_id" required>
                        <option value="">Bitte auswählen</option>
                        <?php foreach ($games as $game) : ?>
                            <option value="<?php echo esc_attr($game->ID); ?>"><?php echo esc_html($game->post_title . ' (' . get_post_meta($game->ID, MEEPLE_MANAGER_META_INVENTORY_NUMBER, true) . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p>
                    <label for="meeple-request-first-name">Vorname</label>
                    <input id="meeple-request-first-name" name="meeple_first_name" type="text" autocomplete="given-name" required>
                </p>
                <p>
                    <label for="meeple-request-last-name">Nachname</label>
                    <input id="meeple-request-last-name" name="meeple_last_name" type="text" autocomplete="family-name" required>
                </p>
                <p>
                    <label for="meeple-request-email">E-Mail-Adresse</label>
                    <input id="meeple-request-email" name="meeple_email" type="email" autocomplete="email" required>
                </p>
                <p>
                    <label for="meeple-request-message">Nachricht (optional)</label>
                    <textarea id="meeple-request-message" name="meeple_message" rows="4"></textarea>
                </p>
                <p><button type="submit" class="meeple-manager-button">Anfrage senden</button></p>
            </form>
        </section>
        <?php
        return ob_get_clean();
    }

    private function render_request_notice() {
        if (!isset($_GET['meeple_request_status'])) {
            return '';
        }

        $status = sanitize_key(wp_unslash($_GET['meeple_request_status']));
        $messages = array(
            'received' => array('success', 'Deine Anfrage wurde gespeichert. Du erhältst eine Eingangsbestätigung per E-Mail.'),
            'received_mail_warning' => array('warning', 'Deine Anfrage wurde gespeichert. Eine der E-Mails konnte möglicherweise nicht versendet werden.'),
            'invalid' => array('error', 'Bitte prüfe Vorname, Nachname und E-Mail-Adresse.'),
            'unavailable' => array('error', 'Dieses Spiel ist nicht mehr verfügbar. Bitte aktualisiere die Seite und versuche es erneut.'),
            'error' => array('error', 'Die Anfrage konnte nicht gespeichert werden. Bitte versuche es später erneut.'),
        );

        if (!isset($messages[$status])) {
            return '';
        }

        return '<div class="meeple-manager-notice meeple-manager-notice--' . esc_attr($messages[$status][0]) . '" role="status">' . esc_html($messages[$status][1]) . '</div>';
    }

    private function format_date($date) {
        if (!Meeple_Manager_Rentals::is_valid_date($date)) {
            return '';
        }

        $timestamp = DateTimeImmutable::createFromFormat('!Y-m-d', $date, wp_timezone())->getTimestamp();
        return wp_date('d.m.Y', $timestamp);
    }

    private function enqueue_styles() {
        wp_enqueue_style(
            'meeple-manager-frontend',
            MEEPLE_MANAGER_URL . 'assets/css/meeple-manager.css',
            array(),
            MEEPLE_MANAGER_VERSION
        );
    }

    public function enqueue_frontend_styles() {
        $this->enqueue_styles();
    }
}
