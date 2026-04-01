<?php

if (!defined('ABSPATH')) {
    exit;
}

class Event_Blocks_Activator {
    public static function activate() {
        $post_type = new Event_Blocks_Event_Post_Type();
        $taxonomy = new Event_Blocks_Event_Category_Taxonomy();

        $post_type->register();
        $taxonomy->register();
        flush_rewrite_rules();
    }
}
