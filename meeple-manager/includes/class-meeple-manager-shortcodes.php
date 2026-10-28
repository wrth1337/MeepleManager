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
        add_shortcode('meeple-lending-button', array($this, 'render_lending_button'));
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

        $output = '<div class="meeple-manager-catalog-wrap"><ul class="meeple-manager-catalog">';

        foreach ($games as $game) {
            $image = get_the_post_thumbnail(
                $game->ID,
                'meeple-manager-game-card',
                array(
                    'class' => 'meeple-manager-game-card__image',
                    'alt' => $game->post_title,
                )
            );

            if ($image === '') {
                $image = '<div class="meeple-manager-game-card__placeholder">Kein Bild hinterlegt</div>';
            }

            $output .= '<li class="meeple-manager-catalog__item">';
            $output .= '<div class="meeple-manager-game-card__image-wrap">' . $image . '</div>';
            $output .= '<h2 class="meeple-manager-catalog__title">' . esc_html($game->post_title) . '</h2>';
            $output .= '</li>';
        }

        $output .= '</ul></div>';

        return $output;
    }

    public function render_lending_button($attributes) {
        $attributes = shortcode_atts(array('url' => ''), $attributes, 'meeple-lending-button');
        $settings = Meeple_Manager_Admin::get_settings();
        $url = trim((string) $attributes['url']);

        if ($url === '') {
            $url = $settings['lending_page_url'];
        }

        $url = esc_url($url);

        if ($url === '') {
            return '';
        }

        $this->enqueue_styles();

        return '<p class="meeple-manager-button-wrap"><a class="meeple-manager-button" href="' . $url . '">Zum Verleihbereich</a></p>';
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

        $output .= '<div class="meeple-manager-table-wrap"><table class="meeple-manager-table">';
        $output .= '<thead><tr><th scope="col">Inventarnummer</th><th scope="col">Titel</th><th scope="col">Kategorie</th><th scope="col">Zustand</th><th scope="col">Verfügbarkeit</th><th scope="col">Voraussichtliche Rückgabe</th></tr></thead><tbody>';
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

            $output .= '<tr>';
            $output .= '<td>' . esc_html($inventory_number) . '</td>';
            $output .= '<td>' . esc_html($game->post_title) . '</td>';
            $output .= '<td><span class="meeple-manager-category meeple-manager-category--' . esc_attr($category_key) . '">' . esc_html($category['short_label']) . '</span></td>';
            $output .= '<td>' . ($condition !== '' ? nl2br(esc_html($condition)) : '–') . '</td>';
            $output .= '<td>' . esc_html($availability) . '</td>';
            $output .= '<td>' . ($due_date !== '' ? esc_html($this->format_date($due_date)) : '–') . '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody></table></div>';
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
