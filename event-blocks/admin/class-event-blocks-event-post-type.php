<?php

if (!defined('ABSPATH')) {
    exit;
}

class Event_Blocks_Event_Post_Type {
    public function register() {
        $labels = array(
            'name' => 'Events',
            'singular_name' => 'Event',
            'menu_name' => 'Event Blocks',
            'name_admin_bar' => 'Event',
            'add_new' => 'Event erstellen',
            'add_new_item' => 'Neues Event erstellen',
            'edit_item' => 'Event bearbeiten',
            'new_item' => 'Neues Event',
            'view_item' => 'Event ansehen',
            'search_items' => 'Events durchsuchen',
            'not_found' => 'Keine Events gefunden',
            'not_found_in_trash' => 'Keine Events im Papierkorb gefunden',
            'all_items' => 'Alle Events',
        );

        register_post_type(
            EVENT_BLOCKS_POST_TYPE,
            array(
                'labels' => $labels,
                'public' => true,
                'publicly_queryable' => false,
                'exclude_from_search' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_nav_menus' => false,
                'has_archive' => false,
                'rewrite' => false,
                'menu_icon' => 'dashicons-calendar-alt',
                'menu_position' => 25,
                'supports' => array('title', 'thumbnail'),
                'show_in_rest' => false,
            )
        );
    }
}
