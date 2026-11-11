<?php

if (!defined('ABSPATH')) {
    exit;
}

class Meeple_Manager_Inventory_Numbers {
    public static function get($game_id) {
        $primary = get_post_meta($game_id, MEEPLE_MANAGER_META_INVENTORY_NUMBER, true);
        $additional = get_post_meta($game_id, MEEPLE_MANAGER_META_EXTRA_INVENTORY_NUMBER, false);
        return self::normalize(array_merge(array($primary), $additional));
    }

    public static function format($game_id) {
        return implode(', ', self::get($game_id));
    }

    public static function from_text($text) {
        return self::normalize(preg_split('/\r\n|\r|\n/', (string) $text));
    }

    public static function normalize($numbers) {
        $result = array();
        $seen = array();
        foreach ($numbers as $number) {
            if (!is_scalar($number)) {
                continue;
            }
            $number = trim(sanitize_text_field((string) $number));
            $key = strtolower($number);
            if ($number !== '' && !isset($seen[$key])) {
                $result[] = $number;
                $seen[$key] = true;
            }
        }
        return $result;
    }

    public static function has_conflict($numbers, $exclude_game_id = 0) {
        if (empty($numbers)) {
            return false;
        }

        return !empty(get_posts(array(
            'post_type' => MEEPLE_MANAGER_GAME_POST_TYPE,
            'post_status' => array('publish', 'draft', 'pending', 'private', 'future'),
            'posts_per_page' => 1,
            'fields' => 'ids',
            'post__not_in' => $exclude_game_id > 0 ? array((int) $exclude_game_id) : array(),
            'meta_query' => array(
                'relation' => 'OR',
                array('key' => MEEPLE_MANAGER_META_INVENTORY_NUMBER, 'value' => $numbers, 'compare' => 'IN'),
                array('key' => MEEPLE_MANAGER_META_EXTRA_INVENTORY_NUMBER, 'value' => $numbers, 'compare' => 'IN'),
            ),
        )));
    }

    public static function save($game_id, $numbers) {
        $numbers = self::normalize($numbers);
        if (empty($numbers)) {
            return new WP_Error('missing_number', 'Bitte trage mindestens eine Inventarnummer ein. Vorhandene Inventarnummern bleiben unverändert.');
        }
        if (self::has_conflict($numbers, $game_id)) {
            return new WP_Error('duplicate_number', 'Mindestens eine Inventarnummer ist bereits einem anderen Spiel zugeordnet. Die bisherigen Inventarnummern bleiben unverändert.');
        }

        // Retain the original field as the primary number and sorting key for existing games.
        update_post_meta($game_id, MEEPLE_MANAGER_META_INVENTORY_NUMBER, wp_slash($numbers[0]));
        delete_post_meta($game_id, MEEPLE_MANAGER_META_EXTRA_INVENTORY_NUMBER);
        foreach (array_slice($numbers, 1) as $number) {
            add_post_meta($game_id, MEEPLE_MANAGER_META_EXTRA_INVENTORY_NUMBER, wp_slash($number));
        }
        return true;
    }
}
