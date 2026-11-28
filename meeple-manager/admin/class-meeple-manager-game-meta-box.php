<?php

if (!defined('ABSPATH')) {
    exit;
}

class Meeple_Manager_Game_Meta_Box {
    public function register() {
        add_meta_box(
            'meeple-manager-game-details',
            'Inventar und Zustand',
            array($this, 'render'),
            MEEPLE_MANAGER_GAME_POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render($post) {
        wp_nonce_field('meeple_manager_save_game', 'meeple_manager_game_nonce');

        $inventory_number = get_post_meta($post->ID, MEEPLE_MANAGER_META_INVENTORY_NUMBER, true);
        $category = get_post_meta($post->ID, MEEPLE_MANAGER_META_GAME_CATEGORY, true);
        $condition = get_post_meta($post->ID, MEEPLE_MANAGER_META_CONDITION, true);
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
                    <th scope="row"><label for="meeple-manager-inventory-number">Inventarnummer</label></th>
                    <td>
                        <input id="meeple-manager-inventory-number" name="meeple_manager_inventory_number" type="text" value="<?php echo esc_attr($inventory_number); ?>" class="regular-text" required>
                        <p class="description">Die Inventarnummer muss eindeutig sein, zum Beispiel SP-001.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="meeple-manager-category">Kategorie</label></th>
                    <td>
                        <select id="meeple-manager-category" name="meeple_manager_category">
                            <?php foreach (Meeple_Manager_Content_Types::get_categories() as $value => $details) : ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($category, $value); ?>><?php echo esc_html($details['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Die Kategorie legt fest, ob das Exemplar ausgeliehen werden kann.</p>
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
                        <p class="description">Freitext. Diese Angabe erscheint nur im passwortgeschützten Verleihbereich.</p>
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

        $inventory_number = isset($_POST['meeple_manager_inventory_number'])
            ? sanitize_text_field(wp_unslash($_POST['meeple_manager_inventory_number']))
            : '';
        $inventory_number = trim($inventory_number);

        if ($inventory_number === '') {
            delete_post_meta($post_id, MEEPLE_MANAGER_META_INVENTORY_NUMBER);
            set_transient('meeple_manager_game_notice_' . get_current_user_id(), 'missing_number', MINUTE_IN_SECONDS);
        } elseif ($this->inventory_number_exists($inventory_number, $post_id)) {
            set_transient('meeple_manager_game_notice_' . get_current_user_id(), 'duplicate_number', MINUTE_IN_SECONDS);
        } else {
            update_post_meta($post_id, MEEPLE_MANAGER_META_INVENTORY_NUMBER, $inventory_number);
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
            update_post_meta($post_id, MEEPLE_MANAGER_META_CONDITION, $condition);
        }

        if (isset($_POST['meeple_manager_favorite']) && sanitize_key(wp_unslash($_POST['meeple_manager_favorite'])) === '1') {
            update_post_meta($post_id, MEEPLE_MANAGER_META_FAVORITE, '1');
        } else {
            delete_post_meta($post_id, MEEPLE_MANAGER_META_FAVORITE);
        }

        $this->save_image_position($post_id, MEEPLE_MANAGER_META_IMAGE_POSITION_X, 'meeple_manager_image_position_x');
        $this->save_image_position($post_id, MEEPLE_MANAGER_META_IMAGE_POSITION_Y, 'meeple_manager_image_position_y');
    }

    public function admin_notice() {
        $key = 'meeple_manager_game_notice_' . get_current_user_id();
        $notice = get_transient($key);

        if (!$notice) {
            return;
        }

        delete_transient($key);

        if ($notice === 'duplicate_number') {
            $message = 'Diese Inventarnummer wird bereits für ein anderes Spiel verwendet. Die bisherige Inventarnummer dieses Eintrags blieb unverändert.';
        } else {
            $message = 'Bitte trage eine Inventarnummer ein. Ohne Inventarnummer erscheint das Spiel nicht in den Katalogen.';
        }

        echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
    }

    private function inventory_number_exists($inventory_number, $exclude_post_id = 0) {
        $matches = get_posts(
            array(
                'post_type' => MEEPLE_MANAGER_GAME_POST_TYPE,
                'post_status' => array('publish', 'draft', 'pending', 'private'),
                'posts_per_page' => 1,
                'fields' => 'ids',
                'post__not_in' => $exclude_post_id > 0 ? array((int) $exclude_post_id) : array(),
                'meta_query' => array(
                    array(
                        'key' => MEEPLE_MANAGER_META_INVENTORY_NUMBER,
                        'value' => $inventory_number,
                        'compare' => '=',
                    ),
                ),
            )
        );

        return !empty($matches);
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
}
