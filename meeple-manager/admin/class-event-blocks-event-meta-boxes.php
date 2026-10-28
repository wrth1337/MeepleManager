<?php

if (!defined('ABSPATH')) {
    exit;
}

class Event_Blocks_Event_Meta_Boxes {
    public function register() {
        add_meta_box(
            'event-blocks-event-details',
            'Event-Details',
            array($this, 'render'),
            EVENT_BLOCKS_POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render($post) {
        wp_nonce_field('event_blocks_save_event', 'event_blocks_event_nonce');

        $date = get_post_meta($post->ID, EVENT_BLOCKS_META_DATE, true);
        $is_announcement = get_post_meta($post->ID, EVENT_BLOCKS_META_IS_ANNOUNCEMENT, true) === '1';
        $time = get_post_meta($post->ID, EVENT_BLOCKS_META_TIME, true);
        $location = get_post_meta($post->ID, EVENT_BLOCKS_META_LOCATION, true);
        $location_link = get_post_meta($post->ID, EVENT_BLOCKS_META_LOCATION_LINK, true);
        $external_link = get_post_meta($post->ID, EVENT_BLOCKS_META_EXTERNAL_LINK, true);
        $image_url = get_post_meta($post->ID, EVENT_BLOCKS_META_IMAGE_URL, true);
        $notes = get_post_meta($post->ID, EVENT_BLOCKS_META_NOTES, true);
        $timestamp = (int) get_post_meta($post->ID, EVENT_BLOCKS_META_START_TIMESTAMP, true);

        if ($date === '' && $timestamp > 0) {
            $date = wp_date('Y-m-d', $timestamp);
        }
        ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">Termin <span aria-hidden="true">*</span></th>
                    <td>
                        <fieldset>
                            <label for="event-blocks-is-announcement">
                                <input id="event-blocks-is-announcement" name="event_blocks_is_announcement" type="checkbox" value="1" <?php checked($is_announcement); ?>>
                                Als Vorankündigung markieren
                            </label>
                            <p class="description">Bei einer Vorankündigung bleibt intern ein exaktes Datum gespeichert, im Frontend wird aber nur der Monat angezeigt.</p>
                            <p>
                                <label for="event-blocks-date"><strong>Exaktes Datum</strong></label><br>
                                <input id="event-blocks-date" name="event_blocks_date" type="date" value="<?php echo esc_attr($date); ?>" class="regular-text" required>
                            </p>
                            <p class="description">Pflichtfeld für alle Events. Die Event-Liste im Frontend wird chronologisch sortiert; bei Vorankündigungen wird trotzdem nur Monat und Jahr dargestellt.</p>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="event-blocks-time">Uhrzeit</label></th>
                    <td>
                        <input id="event-blocks-time" name="event_blocks_time" type="time" value="<?php echo esc_attr($time); ?>" class="regular-text">
                        <p class="description">Optional. Wenn leer, wird intern 00:00 verwendet.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="event-blocks-location">Ort</label></th>
                    <td>
                        <input id="event-blocks-location" name="event_blocks_location" type="text" value="<?php echo esc_attr($location); ?>" class="regular-text">
                        <p class="description">Optional. Wird in der Kachel unter Datum und Uhrzeit angezeigt.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="event-blocks-location-link">Ortslink</label></th>
                    <td><input id="event-blocks-location-link" name="event_blocks_location_link" type="url" value="<?php echo esc_attr($location_link); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="event-blocks-external-link">Externer Link</label></th>
                    <td><input id="event-blocks-external-link" name="event_blocks_external_link" type="url" value="<?php echo esc_attr($external_link); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="event-blocks-image-url">Bild-URL</label></th>
                    <td>
                        <input id="event-blocks-image-url" name="event_blocks_image_url" type="url" value="<?php echo esc_attr($image_url); ?>" class="regular-text">
                        <p class="description">Optional. Falls ein Beitragsbild gesetzt ist, wird dieses im Frontend bevorzugt verwendet.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="event-blocks-notes">Hinweise</label></th>
                    <td>
                        <textarea id="event-blocks-notes" name="event_blocks_notes" rows="6" class="large-text"><?php echo esc_textarea($notes); ?></textarea>
                        <p class="description">Eine Zeile pro Hinweis. Diese werden im Frontend als Liste ausgegeben.</p>
                    </td>
                </tr>
            </tbody>
        </table>
        <p><strong>Pflichtfelder:</strong> Titel und Datum.</p>
        <?php
    }

    public function save($post_id) {
        if (!isset($_POST['event_blocks_event_nonce'])) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['event_blocks_event_nonce'])), 'event_blocks_save_event')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = array(
            EVENT_BLOCKS_META_DATE => array('key' => 'event_blocks_date', 'type' => 'text'),
            EVENT_BLOCKS_META_TIME => array('key' => 'event_blocks_time', 'type' => 'text'),
            EVENT_BLOCKS_META_LOCATION => array('key' => 'event_blocks_location', 'type' => 'text'),
            EVENT_BLOCKS_META_LOCATION_LINK => array('key' => 'event_blocks_location_link', 'type' => 'url'),
            EVENT_BLOCKS_META_EXTERNAL_LINK => array('key' => 'event_blocks_external_link', 'type' => 'url'),
            EVENT_BLOCKS_META_IMAGE_URL => array('key' => 'event_blocks_image_url', 'type' => 'url'),
            EVENT_BLOCKS_META_NOTES => array('key' => 'event_blocks_notes', 'type' => 'textarea'),
        );

        foreach ($fields as $meta_key => $field) {
            $raw_value = isset($_POST[$field['key']]) ? wp_unslash($_POST[$field['key']]) : '';
            $value = $this->sanitize_field($raw_value, $field['type']);

            if ($value === '') {
                delete_post_meta($post_id, $meta_key);
            } else {
                update_post_meta($post_id, $meta_key, $value);
            }
        }

        $is_announcement = isset($_POST['event_blocks_is_announcement']) ? '1' : '';

        if ($is_announcement === '1') {
            update_post_meta($post_id, EVENT_BLOCKS_META_IS_ANNOUNCEMENT, '1');
        } else {
            delete_post_meta($post_id, EVENT_BLOCKS_META_IS_ANNOUNCEMENT);
        }

        delete_post_meta($post_id, EVENT_BLOCKS_META_ANNOUNCEMENT_MONTH);

        $timestamp = $this->build_start_timestamp(
            isset($_POST['event_blocks_date']) ? wp_unslash($_POST['event_blocks_date']) : '',
            isset($_POST['event_blocks_time']) ? wp_unslash($_POST['event_blocks_time']) : ''
        );

        if ($timestamp > 0) {
            update_post_meta($post_id, EVENT_BLOCKS_META_START_TIMESTAMP, $timestamp);
        } else {
            delete_post_meta($post_id, EVENT_BLOCKS_META_START_TIMESTAMP);
        }
    }

    private function sanitize_field($value, $type) {
        if ($type === 'url') {
            return esc_url_raw(trim((string) $value));
        }

        if ($type === 'textarea') {
            return trim((string) $value);
        }

        return trim(sanitize_text_field((string) $value));
    }

    private function build_start_timestamp($date, $time) {
        $date = trim(sanitize_text_field((string) $date));
        $time = trim(sanitize_text_field((string) $time));

        if ($date === '') {
            return 0;
        }

        if ($time === '') {
            $time = '00:00';
        }

        try {
            $datetime = new DateTimeImmutable($date . ' ' . $time, wp_timezone());
        } catch (Exception $exception) {
            return 0;
        }

        return $datetime->getTimestamp();
    }
}
