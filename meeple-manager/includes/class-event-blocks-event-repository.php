<?php

if (!defined('ABSPATH')) {
    exit;
}

class Event_Blocks_Event_Repository {
    public function get_events($args = array()) {
        $defaults = array(
            'limit' => 0,
            'show_past' => false,
        );

        $args = wp_parse_args($args, $defaults);
        $limit = (int) $args['limit'];
        $show_past = (bool) $args['show_past'];

        $query_args = array(
            'post_type' => EVENT_BLOCKS_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $limit > 0 ? $limit : -1,
            'meta_key' => EVENT_BLOCKS_META_START_TIMESTAMP,
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'no_found_rows' => true,
            'meta_query' => array(),
        );

        if ($show_past) {
            $query_args['meta_query'][] = array(
                'key' => EVENT_BLOCKS_META_START_TIMESTAMP,
                'compare' => 'EXISTS',
            );
        } else {
            $query_args['meta_query'][] = array(
                'key' => EVENT_BLOCKS_META_START_TIMESTAMP,
                'value' => $this->get_today_start_timestamp(),
                'compare' => '>=',
                'type' => 'NUMERIC',
            );
        }

        $query = new WP_Query($query_args);

        return $query->posts;
    }

    private function get_today_start_timestamp() {
        try {
            $today = new DateTimeImmutable('today', wp_timezone());
        } catch (Exception $exception) {
            return strtotime(wp_date('Y-m-d 00:00:00'));
        }

        return $today->getTimestamp();
    }
}
