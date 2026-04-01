<?php

if (!defined('ABSPATH')) {
    exit;
}

class Event_Blocks_Event_Normalizer {
    public function normalize_event($post) {
        if (!$post instanceof WP_Post) {
            return null;
        }

        $title = trim($post->post_title);
        $date = trim((string) get_post_meta($post->ID, EVENT_BLOCKS_META_DATE, true));
        $is_announcement = get_post_meta($post->ID, EVENT_BLOCKS_META_IS_ANNOUNCEMENT, true) === '1';
        $announcement_month = trim((string) get_post_meta($post->ID, EVENT_BLOCKS_META_ANNOUNCEMENT_MONTH, true));
        $time = trim((string) get_post_meta($post->ID, EVENT_BLOCKS_META_TIME, true));
        $location = trim((string) get_post_meta($post->ID, EVENT_BLOCKS_META_LOCATION, true));
        $location_link = trim((string) get_post_meta($post->ID, EVENT_BLOCKS_META_LOCATION_LINK, true));
        $link = trim((string) get_post_meta($post->ID, EVENT_BLOCKS_META_EXTERNAL_LINK, true));
        $image = $this->get_image_url($post);
        $timestamp = (int) get_post_meta($post->ID, EVENT_BLOCKS_META_START_TIMESTAMP, true);

        if ($title === '' || $timestamp <= 0) {
            return null;
        }

        if ($date === '' && $timestamp > 0) {
            $date = wp_date('Y-m-d', $timestamp);
        }

        if ($date === '') {
            return null;
        }

        return array(
            'id' => $post->ID,
            'title' => $title,
            'date' => $date,
            'is_announcement' => $is_announcement,
            'announcement_month' => $announcement_month !== '' ? $announcement_month : wp_date('Y-m', $timestamp),
            'display_date' => $this->format_event_date($timestamp, $is_announcement),
            'time' => $time,
            'location' => $location,
            'location_link' => $location_link,
            'image' => $image,
            'link' => $link,
            'notes' => $this->normalize_notes(get_post_meta($post->ID, EVENT_BLOCKS_META_NOTES, true)),
            'timestamp' => $timestamp,
            'categories' => $this->get_categories($post->ID),
        );
    }

    public function format_date($timestamp) {
        return wp_date('d.m.Y', $timestamp);
    }

    public function format_announcement_month($timestamp) {
        return wp_date('F Y', $timestamp);
    }

    public function format_event_date($timestamp, $is_announcement = false) {
        if ($is_announcement) {
            return $this->format_announcement_month($timestamp);
        }

        return $this->format_date($timestamp);
    }

    public function normalize_notes($notes) {
        $clean_notes = array();

        if (!is_string($notes) || $notes === '') {
            return $clean_notes;
        }

        $lines = preg_split('/\r\n|\r|\n/', $notes);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line !== '') {
                $clean_notes[] = $line;
            }
        }

        return $clean_notes;
    }

    private function get_categories($post_id) {
        $terms = get_the_terms($post_id, EVENT_BLOCKS_CATEGORY_TAXONOMY);

        if (!is_array($terms)) {
            return array();
        }

        $categories = array();

        foreach ($terms as $term) {
            $categories[] = array(
                'name' => $term->name,
                'slug' => $term->slug,
                'color' => $this->get_category_color($term->term_id),
            );
        }

        return $categories;
    }

    private function get_category_color($term_id) {
        $color = (string) get_term_meta($term_id, EVENT_BLOCKS_TERM_META_COLOR, true);

        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1) {
            return $color;
        }

        return '#4f46e5';
    }

    private function get_image_url(WP_Post $post) {
        $thumbnail_url = get_the_post_thumbnail_url($post, 'large');

        if (is_string($thumbnail_url) && $thumbnail_url !== '') {
            return $thumbnail_url;
        }

        return trim((string) get_post_meta($post->ID, EVENT_BLOCKS_META_IMAGE_URL, true));
    }
}
