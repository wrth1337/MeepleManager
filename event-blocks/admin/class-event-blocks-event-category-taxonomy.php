<?php

if (!defined('ABSPATH')) {
    exit;
}

class Event_Blocks_Event_Category_Taxonomy {
    public function register() {
        $labels = array(
            'name' => 'Kategorien',
            'singular_name' => 'Kategorie',
            'search_items' => 'Kategorien durchsuchen',
            'all_items' => 'Alle Kategorien',
            'edit_item' => 'Kategorie bearbeiten',
            'update_item' => 'Kategorie aktualisieren',
            'add_new_item' => 'Neue Kategorie hinzufügen',
            'new_item_name' => 'Neuer Kategoriename',
            'menu_name' => 'Kategorien',
        );

        register_taxonomy(
            EVENT_BLOCKS_CATEGORY_TAXONOMY,
            EVENT_BLOCKS_POST_TYPE,
            array(
                'labels' => $labels,
                'public' => false,
                'show_ui' => true,
                'show_admin_column' => true,
                'show_in_quick_edit' => false,
                'show_tagcloud' => false,
                'hierarchical' => true,
                'rewrite' => false,
            )
        );
    }

    public function render_add_fields() {
        ?>
        <div class="form-field term-color-wrap">
            <label for="event-blocks-category-color">Badge-Farbe</label>
            <?php wp_nonce_field('event_blocks_save_category_color', 'event_blocks_category_nonce'); ?>
            <input id="event-blocks-category-color" name="event_blocks_category_color" type="color" value="#4f46e5">
            <p>Die Farbe wird im Frontend als Badge-Hintergrund verwendet.</p>
        </div>
        <?php
    }

    public function render_edit_fields($term) {
        $color = (string) get_term_meta($term->term_id, EVENT_BLOCKS_TERM_META_COLOR, true);

        if ($color === '') {
            $color = '#4f46e5';
        }
        ?>
        <tr class="form-field term-color-wrap">
            <th scope="row"><label for="event-blocks-category-color">Badge-Farbe</label></th>
            <td>
                <?php wp_nonce_field('event_blocks_save_category_color', 'event_blocks_category_nonce'); ?>
                <input id="event-blocks-category-color" name="event_blocks_category_color" type="color" value="<?php echo esc_attr($color); ?>">
                <p class="description">Die Farbe wird im Frontend als Badge-Hintergrund verwendet.</p>
            </td>
        </tr>
        <?php
    }

    public function save_term_meta($term_id) {
        if (!isset($_POST['event_blocks_category_nonce'])) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['event_blocks_category_nonce'])), 'event_blocks_save_category_color')) {
            return;
        }

        $raw_color = isset($_POST['event_blocks_category_color']) ? wp_unslash($_POST['event_blocks_category_color']) : '';
        $color = sanitize_hex_color($raw_color);

        if ($color === null || $color === '') {
            delete_term_meta($term_id, EVENT_BLOCKS_TERM_META_COLOR);
            return;
        }

        update_term_meta($term_id, EVENT_BLOCKS_TERM_META_COLOR, $color);
    }
}
