<?php

if (!defined('ABSPATH')) {
    exit;
}

class Event_Blocks_Admin_UI {
    private $normalizer;

    public function __construct(Event_Blocks_Event_Normalizer $normalizer) {
        $this->normalizer = $normalizer;
    }

    public function filter_event_columns($columns) {
        $ordered = array();

        if (isset($columns['cb'])) {
            $ordered['cb'] = $columns['cb'];
        }

        $ordered['title'] = 'Titel';
        $ordered['event_date'] = 'Datum';
        $ordered['event_time'] = 'Uhrzeit';
        $ordered['event_location'] = 'Ort';
        $ordered['taxonomy-' . EVENT_BLOCKS_CATEGORY_TAXONOMY] = 'Kategorien';
        $ordered['event_shortcode'] = 'Shortcode';
        $ordered['date'] = 'Erstellt';

        return $ordered;
    }

    public function render_event_column($column, $post_id) {
        if ($column === 'event_date') {
            $timestamp = (int) get_post_meta($post_id, EVENT_BLOCKS_META_START_TIMESTAMP, true);

            if ($timestamp > 0) {
                echo esc_html($this->normalizer->format_date($timestamp));
            } else {
                echo '-';
            }

            return;
        }

        if ($column === 'event_time') {
            $time = trim((string) get_post_meta($post_id, EVENT_BLOCKS_META_TIME, true));
            echo $time !== '' ? esc_html($time) . ' Uhr' : '-';
            return;
        }

        if ($column === 'event_location') {
            $location = trim((string) get_post_meta($post_id, EVENT_BLOCKS_META_LOCATION, true));
            echo $location !== '' ? esc_html($location) : '-';
            return;
        }

        if ($column === 'event_shortcode') {
            echo '<code>[event_blocks limit="1"]</code>';
        }
    }

    public function register_sortable_event_columns($columns) {
        $columns['event_date'] = 'event_date';

        return $columns;
    }

    public function adjust_event_admin_query($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->get('post_type') !== EVENT_BLOCKS_POST_TYPE) {
            return;
        }

        $orderby = $query->get('orderby');

        if ($orderby === 'event_date' || $orderby === '') {
            $query->set('meta_key', EVENT_BLOCKS_META_START_TIMESTAMP);
            $query->set('orderby', 'meta_value_num');

            if ($query->get('order') === '') {
                $query->set('order', 'ASC');
            }
        }
    }

    public function filter_category_columns($columns) {
        $ordered = array();

        foreach ($columns as $key => $label) {
            $ordered[$key] = $label;

            if ($key === 'name') {
                $ordered['event_blocks_color'] = 'Farbe';
            }
        }

        return $ordered;
    }

    public function render_category_column($content, $column_name, $term_id) {
        if ($column_name !== 'event_blocks_color') {
            return $content;
        }

        $color = (string) get_term_meta($term_id, EVENT_BLOCKS_TERM_META_COLOR, true);

        if ($color === '') {
            $color = '#4f46e5';
        }

        return sprintf(
            '<span style="display:inline-flex;align-items:center;gap:8px;"><span style="width:14px;height:14px;border-radius:999px;background:%1$s;border:1px solid rgba(0,0,0,0.12);display:inline-block;"></span><code>%1$s</code></span>',
            esc_attr($color)
        );
    }
}
