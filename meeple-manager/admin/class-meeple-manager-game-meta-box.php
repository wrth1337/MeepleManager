<?php

if (!defined('ABSPATH')) {
    exit;
}

class Meeple_Manager_Game_Meta_Box {
    public function register() {
        add_meta_box(
            'meeple-manager-game-details',
            'Inventar, Spielinfos und Zustand',
            array($this, 'render'),
            MEEPLE_MANAGER_GAME_POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render($post) {
        wp_nonce_field('meeple_manager_save_game', 'meeple_manager_game_nonce');

        $inventory_numbers = Meeple_Manager_Inventory_Numbers::get($post->ID);
        $category = get_post_meta($post->ID, MEEPLE_MANAGER_META_GAME_CATEGORY, true);
        $condition = get_post_meta($post->ID, MEEPLE_MANAGER_META_CONDITION, true);
        $players_min = get_post_meta($post->ID, MEEPLE_MANAGER_META_PLAYERS_MIN, true);
        $players_max = get_post_meta($post->ID, MEEPLE_MANAGER_META_PLAYERS_MAX, true);
        $duration_min = get_post_meta($post->ID, MEEPLE_MANAGER_META_DURATION_MIN, true);
        $duration_max = get_post_meta($post->ID, MEEPLE_MANAGER_META_DURATION_MAX, true);
        $is_favorite = get_post_meta($post->ID, MEEPLE_MANAGER_META_FAVORITE, true) === '1';
        $image_position_x = get_post_meta($post->ID, MEEPLE_MANAGER_META_IMAGE_POSITION_X, true);
        $image_position_y = get_post_meta($post->ID, MEEPLE_MANAGER_META_IMAGE_POSITION_Y, true);

        if (!isset(Meeple_Manager_Content_Types::get_categories()[$category])) {
            $category = 'green';
        }
        ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="meeple-manager-inventory-number">Inventarnummern</label></th>
                    <td>
                        <textarea id="meeple-manager-inventory-number" name="meeple_manager_inventory_number" rows="4" class="regular-text" aria-describedby="meeple-manager-inventory-description" required><?php echo esc_textarea(implode("\n", $inventory_numbers)); ?></textarea>
                        <p id="meeple-manager-inventory-description" class="description">Eine Inventarnummer pro Zeile, zum Beispiel SP-001. Für Hauptspiel und Erweiterungen kannst du mehrere Nummern eintragen; sie werden gemeinsam als ein Spiel ausgeliehen. Die erste Nummer bestimmt die Sortierung. Jede Nummer darf nur einem Spiel zugeordnet sein.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="meeple-manager-category">Ausleihkategorie</label></th>
                    <td>
                        <select id="meeple-manager-category" name="meeple_manager_category">
                            <?php foreach (Meeple_Manager_Content_Types::get_categories() as $value => $details) : ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($category, $value); ?>><?php echo esc_html($details['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Legt fest, ob das Exemplar ausgeliehen werden kann. Spielkategorien pflegst du separat im Kasten „Spielkategorien“.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Spieleranzahl</th>
                    <td>
                        <label for="meeple-manager-players-min">Von</label>
                        <input id="meeple-manager-players-min" name="meeple_manager_players_min" type="number" min="1" step="1" value="<?php echo esc_attr($players_min); ?>" class="small-text">
                        <label for="meeple-manager-players-max">bis</label>
                        <input id="meeple-manager-players-max" name="meeple_manager_players_max" type="number" min="1" step="1" value="<?php echo esc_attr($players_max); ?>" class="small-text">
                        <p class="description">Optional. Trage die kleinste und größte Spielerzahl ein.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Spieldauer in Minuten</th>
                    <td>
                        <label for="meeple-manager-duration-min">Von</label>
                        <input id="meeple-manager-duration-min" name="meeple_manager_duration_min" type="number" min="1" step="1" value="<?php echo esc_attr($duration_min); ?>" class="small-text">
                        <label for="meeple-manager-duration-max">bis</label>
                        <input id="meeple-manager-duration-max" name="meeple_manager_duration_max" type="number" min="1" step="1" value="<?php echo esc_attr($duration_max); ?>" class="small-text">
                        <p class="description">Optional. Trage die kürzeste und längste übliche Spieldauer ein.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Favorit</th>
                    <td>
                        <label for="meeple-manager-favorite">
                            <input id="meeple-manager-favorite" name="meeple_manager_favorite" type="checkbox" value="1" <?php checked($is_favorite); ?>>
                            Aktueller Favorit
                        </label>
                        <p class="description">Favoriten erscheinen in <code>[game-list]</code> zuerst und erhalten eine Flamme.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="meeple-manager-condition">Zustand und bekannte Schäden</label></th>
                    <td>
                        <textarea id="meeple-manager-condition" name="meeple_manager_condition" rows="4" class="large-text"><?php echo esc_textarea($condition); ?></textarea>
                        <p class="description">Freitext. Diese Angabe erscheint nur im Verleihbereich.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Bildposition in der Kachel</th>
                    <td>
                        <label for="meeple-manager-image-position-x">Horizontal</label>
                        <input id="meeple-manager-image-position-x" name="meeple_manager_image_position_x" type="number" min="0" max="100" step="1" value="<?php echo esc_attr($image_position_x); ?>" class="small-text" placeholder="Design-Standard"> %
                        <label for="meeple-manager-image-position-y">Vertikal</label>
                        <input id="meeple-manager-image-position-y" name="meeple_manager_image_position_y" type="number" min="0" max="100" step="1" value="<?php echo esc_attr($image_position_y); ?>" class="small-text" placeholder="Design-Standard"> %
                        <p class="description">Leer lassen, um die Position aus „Spiele-Design“ zu verwenden. 0&nbsp;% entspricht links/oben, 100&nbsp;% rechts/unten. Bei „Kachel füllen“ verschiebt die Position den sichtbaren Ausschnitt.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Spielbild</th>
                    <td><p class="description">Lege das Bild im Feld „Beitragsbild“ rechts fest. Die Originaldatei wird verwendet; Bildanpassung und globale Position legst du unter „Spiele-Design“ fest.</p></td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public function save($post_id) {
        if (!isset($_POST['meeple_manager_game_nonce'])) {
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST['meeple_manager_game_nonce']));

        if (!wp_verify_nonce($nonce, 'meeple_manager_save_game')) {
            return;
        }

        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $inventory_text = isset($_POST['meeple_manager_inventory_number']) && is_scalar($_POST['meeple_manager_inventory_number'])
            ? (string) wp_unslash($_POST['meeple_manager_inventory_number'])
            : '';
        $inventory_result = Meeple_Manager_Inventory_Numbers::save($post_id, Meeple_Manager_Inventory_Numbers::from_text($inventory_text));
        if (is_wp_error($inventory_result)) {
            set_transient('meeple_manager_game_notice_' . get_current_user_id(), $inventory_result->get_error_code(), MINUTE_IN_SECONDS);
        }

        $categories = Meeple_Manager_Content_Types::get_categories();
        $category = isset($_POST['meeple_manager_category'])
            ? sanitize_key(wp_unslash($_POST['meeple_manager_category']))
            : 'green';

        if (!isset($categories[$category])) {
            $category = 'green';
        }

        update_post_meta($post_id, MEEPLE_MANAGER_META_GAME_CATEGORY, $category);

        $condition = isset($_POST['meeple_manager_condition'])
            ? sanitize_textarea_field(wp_unslash($_POST['meeple_manager_condition']))
            : '';

        if ($condition === '') {
            delete_post_meta($post_id, MEEPLE_MANAGER_META_CONDITION);
        } else {
            update_post_meta($post_id, MEEPLE_MANAGER_META_CONDITION, wp_slash($condition));
        }

        if (isset($_POST['meeple_manager_favorite']) && sanitize_key(wp_unslash($_POST['meeple_manager_favorite'])) === '1') {
            update_post_meta($post_id, MEEPLE_MANAGER_META_FAVORITE, '1');
        } else {
            delete_post_meta($post_id, MEEPLE_MANAGER_META_FAVORITE);
        }

        $this->save_image_position($post_id, MEEPLE_MANAGER_META_IMAGE_POSITION_X, 'meeple_manager_image_position_x');
        $this->save_image_position($post_id, MEEPLE_MANAGER_META_IMAGE_POSITION_Y, 'meeple_manager_image_position_y');

        $this->save_optional_positive_integer($post_id, MEEPLE_MANAGER_META_PLAYERS_MIN, 'meeple_manager_players_min');
        $players_max = $this->save_optional_positive_integer($post_id, MEEPLE_MANAGER_META_PLAYERS_MAX, 'meeple_manager_players_max');
        $players_min = (int) get_post_meta($post_id, MEEPLE_MANAGER_META_PLAYERS_MIN, true);
        if ($players_min > 0 && $players_max > 0 && $players_max < $players_min) {
            update_post_meta($post_id, MEEPLE_MANAGER_META_PLAYERS_MAX, $players_min);
        }

        $this->save_optional_positive_integer($post_id, MEEPLE_MANAGER_META_DURATION_MIN, 'meeple_manager_duration_min');
        $duration_max = $this->save_optional_positive_integer($post_id, MEEPLE_MANAGER_META_DURATION_MAX, 'meeple_manager_duration_max');
        $duration_min = (int) get_post_meta($post_id, MEEPLE_MANAGER_META_DURATION_MIN, true);
        if ($duration_min > 0 && $duration_max > 0 && $duration_max < $duration_min) {
            update_post_meta($post_id, MEEPLE_MANAGER_META_DURATION_MAX, $duration_min);
        }
    }

    public function admin_notice() {
        $key = 'meeple_manager_game_notice_' . get_current_user_id();
        $notice = get_transient($key);

        if (!$notice) {
            return;
        }

        delete_transient($key);

        if ($notice === 'duplicate_number') {
            $message = 'Mindestens eine Inventarnummer ist bereits einem anderen Spiel zugeordnet. Die bisherigen Inventarnummern bleiben unverändert.';
        } else {
            $message = 'Bitte trage mindestens eine Inventarnummer ein. Vorhandene Inventarnummern bleiben unverändert; neue Spiele ohne Inventarnummer erscheinen nicht in den Katalogen.';
        }

        echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
    }

    private function save_image_position($post_id, $meta_key, $field_name) {
        $value = isset($_POST[$field_name]) && is_scalar($_POST[$field_name])
            ? trim((string) wp_unslash($_POST[$field_name]))
            : '';

        if ($value === '') {
            delete_post_meta($post_id, $meta_key);
            return;
        }

        update_post_meta($post_id, $meta_key, min(100, absint($value)));
    }

    private function save_optional_positive_integer($post_id, $meta_key, $field_name) {
        if (!isset($_POST[$field_name]) || !is_scalar($_POST[$field_name])) {
            delete_post_meta($post_id, $meta_key);
            return 0;
        }

        $value = absint(wp_unslash($_POST[$field_name]));
        if ($value === 0) {
            delete_post_meta($post_id, $meta_key);
            return 0;
        }

        update_post_meta($post_id, $meta_key, $value);
        return $value;
    }
}
