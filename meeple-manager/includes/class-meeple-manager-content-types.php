<?php

if (!defined('ABSPATH')) {
    exit;
}

class Meeple_Manager_Content_Types {
    public function register() {
        register_post_type(
            MEEPLE_MANAGER_GAME_POST_TYPE,
            array(
                'labels' => array(
                    'name' => 'Spiele',
                    'singular_name' => 'Spiel',
                    'menu_name' => 'Spiele',
                    'add_new' => 'Spiel hinzufügen',
                    'add_new_item' => 'Neues Spiel hinzufügen',
                    'edit_item' => 'Spiel bearbeiten',
                    'new_item' => 'Neues Spiel',
                    'view_item' => 'Spiel ansehen',
                    'search_items' => 'Spiele durchsuchen',
                    'not_found' => 'Keine Spiele gefunden',
                    'not_found_in_trash' => 'Keine Spiele im Papierkorb gefunden',
                    'all_items' => 'Alle Spiele',
                ),
                'public' => false,
                'publicly_queryable' => false,
                'exclude_from_search' => true,
                'show_ui' => true,
                'show_in_menu' => MEEPLE_MANAGER_MENU_SLUG,
                'show_in_nav_menus' => false,
                'has_archive' => false,
                'rewrite' => false,
                'menu_icon' => 'dashicons-games',
                'supports' => array('title', 'thumbnail'),
                'show_in_rest' => false,
            )
        );

        register_taxonomy(
            MEEPLE_MANAGER_GAME_INFO_TAXONOMY,
            array(MEEPLE_MANAGER_GAME_POST_TYPE),
            array(
                'labels' => array(
                    'name' => 'Spielkategorien',
                    'singular_name' => 'Spielkategorie',
                    'search_items' => 'Spielkategorien durchsuchen',
                    'all_items' => 'Alle Spielkategorien',
                    'parent_item' => 'Übergeordnete Spielkategorie',
                    'parent_item_colon' => 'Übergeordnete Spielkategorie:',
                    'edit_item' => 'Spielkategorie bearbeiten',
                    'update_item' => 'Spielkategorie aktualisieren',
                    'add_new_item' => 'Neue Spielkategorie hinzufügen',
                    'new_item_name' => 'Name der neuen Spielkategorie',
                    'menu_name' => 'Spielkategorien',
                ),
                'public' => false,
                'publicly_queryable' => false,
                'show_ui' => true,
                'show_admin_column' => true,
                'hierarchical' => true,
                'show_in_rest' => false,
                'query_var' => false,
                'rewrite' => false,
            )
        );

        register_post_type(
            MEEPLE_MANAGER_TRANSACTION_POST_TYPE,
            array(
                'labels' => array(
                    'name' => 'Ausleihen und Anfragen',
                    'singular_name' => 'Ausleihvorgang',
                ),
                'public' => false,
                'publicly_queryable' => false,
                'exclude_from_search' => true,
                'show_ui' => false,
                'show_in_menu' => false,
                'show_in_rest' => false,
                'supports' => false,
                'capability_type' => 'post',
                'map_meta_cap' => true,
            )
        );
    }

    public function register_image_sizes() {
        add_image_size('meeple-manager-game-card', 600, 600, true);
    }

    public static function get_categories() {
        return array(
            'green' => array(
                'label' => 'Grün – normal ausleihbar',
                'short_label' => 'Grün',
                'color' => '#25803b',
            ),
            'yellow' => array(
                'label' => 'Gelb – nur nach Freigabe',
                'short_label' => 'Gelb',
                'color' => '#d29b00',
            ),
            'red' => array(
                'label' => 'Rot – nicht ausleihbar',
                'short_label' => 'Rot',
                'color' => '#bb3030',
            ),
        );
    }
}
