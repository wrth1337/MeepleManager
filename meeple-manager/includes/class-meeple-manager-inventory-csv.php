<?php

if (!defined('ABSPATH')) {
    exit;
}

class Meeple_Manager_Inventory_CSV {
    public static function get_columns() {
        return array(
            'inventory_number' => array('Inventarnummer', 'inventorynumber', 'nummer', 'nr'),
            'title' => array('Titel', 'name', 'spiel'),
            'category' => array('Ausleihkategorie', 'kategorie', 'lending_category'),
            'condition' => array('Zustand'),
            'favorite' => array('Favorit', 'aktueller_favorit', 'current_favorite'),
            'players_min' => array('Spieleranzahl min', 'spieler_min'),
            'players_max' => array('Spieleranzahl max', 'spieler_max'),
            'duration_min' => array('Spieldauer min', 'dauer_min'),
            'duration_max' => array('Spieldauer max', 'dauer_max'),
            'game_categories' => array('Spielkategorien'),
            'image_url' => array('Bild-URL'),
            'image_position_x' => array('Bildposition X'),
            'image_position_y' => array('Bildposition Y'),
            'status' => array('Status', 'post_status'),
        );
    }

    public static function get_headers() {
        return array_map(function ($names) {
            return $names[0];
        }, array_values(self::get_columns()));
    }

    public function map_columns($header) {
        $columns = array();
        $normalized = array();

        foreach ($header as $index => $name) {
            $name = preg_replace('/^\xEF\xBB\xBF/', '', (string) $name);
            $normalized[$this->normalize_header($name)] = $index;
        }

        foreach (self::get_columns() as $key => $names) {
            foreach (array_merge($names, array($key)) as $name) {
                $name = $this->normalize_header($name);
                if (isset($normalized[$name])) {
                    $columns[$key] = $normalized[$name];
                    break;
                }
            }
        }

        return $columns;
    }

    public function export_row($game) {
        $categories = Meeple_Manager_Content_Types::get_categories();
        $category = get_post_meta($game->ID, MEEPLE_MANAGER_META_GAME_CATEGORY, true);
        $paths = $this->export_category_paths($game->ID);

        if (is_wp_error($paths)) {
            return $paths;
        }

        $encoded_paths = empty($paths) ? '' : wp_json_encode($paths, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded_paths === false) {
            return new WP_Error('categories', 'Die Spielkategorien konnten nicht für den Export aufbereitet werden.');
        }
        $thumbnail_id = get_post_thumbnail_id($game->ID);

        $values = array(
            'inventory_number' => get_post_meta($game->ID, MEEPLE_MANAGER_META_INVENTORY_NUMBER, true),
            'title' => $game->post_title,
            'category' => isset($categories[$category]) ? $categories[$category]['short_label'] : 'Grün',
            'condition' => get_post_meta($game->ID, MEEPLE_MANAGER_META_CONDITION, true),
            'favorite' => get_post_meta($game->ID, MEEPLE_MANAGER_META_FAVORITE, true) === '1' ? 'Ja' : 'Nein',
            'game_categories' => $encoded_paths,
            'image_url' => $thumbnail_id ? (wp_get_attachment_url($thumbnail_id) ?: '') : '',
            'status' => $game->post_status,
        );

        foreach ($this->numeric_meta_keys() as $key => $meta_key) {
            $values[$key] = get_post_meta($game->ID, $meta_key, true);
        }

        $row = array();
        foreach (self::get_columns() as $key => $names) {
            $row[] = $values[$key];
        }

        return $row;
    }

    public function import_row($row, $columns) {
        $values = array();
        foreach (self::get_columns() as $key => $names) {
            $values[$key] = isset($columns[$key], $row[$columns[$key]]) ? trim((string) $row[$columns[$key]]) : '';
        }

        $number = sanitize_text_field($values['inventory_number']);
        $title = sanitize_text_field($values['title']);
        if ($number === '' || $title === '') {
            return new WP_Error('required', 'Inventarnummer oder Titel fehlt.');
        }

        if ($this->inventory_number_exists($number)) {
            return array('status' => 'duplicate');
        }

        $meta = array(
            MEEPLE_MANAGER_META_INVENTORY_NUMBER => $number,
            MEEPLE_MANAGER_META_GAME_CATEGORY => $this->normalize_category($values['category']),
            MEEPLE_MANAGER_META_CONDITION => sanitize_textarea_field($values['condition']),
        );

        foreach ($this->numeric_meta_keys() as $key => $meta_key) {
            if ($values[$key] === '') {
                continue;
            }

            $is_position = strpos($key, 'image_position_') === 0;
            $options = array('min_range' => $is_position ? 0 : 1, 'max_range' => $is_position ? 100 : PHP_INT_MAX);
            $value = filter_var($values[$key], FILTER_VALIDATE_INT, array('options' => $options));
            if ($value === false) {
                $label = self::get_columns()[$key][0];
                return new WP_Error('number', $label . ($is_position ? ': Erwartet wird eine ganze Zahl von 0 bis 100.' : ': Erwartet wird eine positive ganze Zahl.'));
            }
            $meta[$meta_key] = $value;
        }

        foreach (array('players' => 'Spieleranzahl', 'duration' => 'Spieldauer') as $prefix => $label) {
            if ($values[$prefix . '_min'] !== '' && $values[$prefix . '_max'] !== '' && (int) $values[$prefix . '_min'] > (int) $values[$prefix . '_max']) {
                return new WP_Error('range', $label . ': Der Maximalwert darf nicht kleiner als der Minimalwert sein.');
            }
        }

        $status = $values['status'] === '' ? 'publish' : $values['status'];
        if (!in_array($status, array('publish', 'draft', 'pending', 'private'), true)) {
            return new WP_Error('status', 'Unbekannter Status. Erlaubt sind publish, draft, pending und private.');
        }

        $paths = $this->parse_category_paths($values['game_categories']);
        if (is_wp_error($paths)) {
            return $paths;
        }

        $terms = $this->import_category_paths($paths);
        if (is_wp_error($terms)) {
            return $terms;
        }

        $game_id = wp_insert_post(wp_slash(array(
            'post_type' => MEEPLE_MANAGER_GAME_POST_TYPE,
            'post_status' => $status,
            'post_title' => $title,
        )), true);

        if (is_wp_error($game_id)) {
            return $game_id;
        }

        $assigned = wp_set_object_terms($game_id, $terms, MEEPLE_MANAGER_GAME_INFO_TAXONOMY);
        if (is_wp_error($assigned)) {
            wp_delete_post($game_id, true);
            return $assigned;
        }

        foreach ($meta as $meta_key => $value) {
            if ($value !== '') {
                update_post_meta($game_id, $meta_key, wp_slash($value));
            }
        }

        $favorite = strtolower(remove_accents($values['favorite']));
        if (in_array($favorite, array('1', 'true', 'yes', 'ja', 'j', 'x', 'favorite', 'favorit'), true)) {
            update_post_meta($game_id, MEEPLE_MANAGER_META_FAVORITE, '1');
        }

        $missing_image = false;
        if ($values['image_url'] !== '') {
            // Only reuse local media; a CSV import does not download external files.
            $attachment_id = attachment_url_to_postid(esc_url_raw($values['image_url'], array('http', 'https')));
            $missing_image = !$attachment_id || !wp_attachment_is_image($attachment_id) || !set_post_thumbnail($game_id, $attachment_id);
        }

        return array('status' => 'created', 'missing_image' => $missing_image);
    }

    private function numeric_meta_keys() {
        return array(
            'players_min' => MEEPLE_MANAGER_META_PLAYERS_MIN,
            'players_max' => MEEPLE_MANAGER_META_PLAYERS_MAX,
            'duration_min' => MEEPLE_MANAGER_META_DURATION_MIN,
            'duration_max' => MEEPLE_MANAGER_META_DURATION_MAX,
            'image_position_x' => MEEPLE_MANAGER_META_IMAGE_POSITION_X,
            'image_position_y' => MEEPLE_MANAGER_META_IMAGE_POSITION_Y,
        );
    }

    private function export_category_paths($game_id) {
        $terms = wp_get_object_terms($game_id, MEEPLE_MANAGER_GAME_INFO_TAXONOMY);
        if (is_wp_error($terms)) {
            return $terms;
        }

        $paths = array();
        foreach ($terms as $term) {
            $path = array();
            $ancestors = array_reverse(get_ancestors($term->term_id, MEEPLE_MANAGER_GAME_INFO_TAXONOMY, 'taxonomy'));
            foreach ($ancestors as $ancestor_id) {
                $ancestor = get_term($ancestor_id, MEEPLE_MANAGER_GAME_INFO_TAXONOMY);
                if (is_wp_error($ancestor) || !$ancestor) {
                    return new WP_Error('category', 'Eine übergeordnete Spielkategorie konnte nicht gelesen werden.');
                }
                $path[] = $ancestor->name;
            }
            $path[] = $term->name;
            $paths[] = $path;
        }

        return $paths;
    }

    private function parse_category_paths($value) {
        if ($value === '') {
            return array();
        }

        $paths = substr($value, 0, 1) === '[' ? json_decode($value, true) : explode('|', $value);
        if (!is_array($paths) || array_values($paths) !== $paths) {
            return new WP_Error('categories', 'Spielkategorien: Ungültige Liste. Verwende JSON oder trenne Kategorienamen mit |.');
        }

        foreach ($paths as &$path) {
            $path = is_string($path) ? array($path) : $path;
            if (!is_array($path) || empty($path) || array_values($path) !== $path) {
                return new WP_Error('categories', 'Spielkategorien: Jeder Kategoriepfad muss aus Namen bestehen.');
            }
            foreach ($path as &$name) {
                if (!is_string($name) || trim(sanitize_text_field($name)) === '') {
                    return new WP_Error('categories', 'Spielkategorien: Ein Kategoriename fehlt oder ist ungültig.');
                }
                $name = trim(sanitize_text_field($name));
            }
            unset($name);
        }
        unset($path);

        return $paths;
    }

    private function import_category_paths($paths) {
        $ids = array();
        foreach ($paths as $path) {
            $parent = 0;
            foreach ($path as $name) {
                // Match names within their parent, not slugs that may collide after transliteration.
                $matches = get_terms(array(
                    'taxonomy' => MEEPLE_MANAGER_GAME_INFO_TAXONOMY,
                    'name' => $name,
                    'parent' => $parent,
                    'hide_empty' => false,
                ));
                if (is_wp_error($matches)) {
                    return $matches;
                }
                $term = null;
                foreach ($matches as $match) {
                    if (strcasecmp($match->name, $name) === 0) {
                        $term = array('term_id' => $match->term_id);
                        break;
                    }
                }
                if (!$term) {
                    $term = wp_insert_term(wp_slash($name), MEEPLE_MANAGER_GAME_INFO_TAXONOMY, array('parent' => $parent));
                }
                if (is_wp_error($term)) {
                    return $term;
                }
                $parent = (int) $term['term_id'];
            }
            $ids[] = $parent;
        }

        return array_values(array_unique($ids));
    }

    private function normalize_header($value) {
        $value = strtolower(remove_accents(trim((string) $value)));
        $value = str_replace(array(' ', '-', '.', '/', '\\'), '_', $value);
        return preg_replace('/[^a-z0-9_]/', '', $value);
    }

    private function normalize_category($value) {
        $value = strtolower(remove_accents(trim((string) $value)));
        $value = trim(preg_replace('/[^a-z0-9]+/', '_', $value), '_');
        $map = array(
            'yellow' => 'yellow', 'gelb' => 'yellow',
            'gelb_nur_nach_freigabe' => 'yellow', 'yellow_nur_nach_freigabe' => 'yellow',
            'red' => 'red', 'rot' => 'red', 'rot_nicht_ausleihbar' => 'red', 'red_nicht_ausleihbar' => 'red',
        );
        return isset($map[$value]) ? $map[$value] : 'green';
    }

    private function inventory_number_exists($number) {
        return !empty(get_posts(array(
            'post_type' => MEEPLE_MANAGER_GAME_POST_TYPE,
            'post_status' => array('publish', 'draft', 'pending', 'private'),
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => array(array('key' => MEEPLE_MANAGER_META_INVENTORY_NUMBER, 'value' => $number, 'compare' => '=')),
        )));
    }
}
