<?php

if (!defined('ABSPATH')) {
    exit;
}

class Event_Blocks_Activator {
    public static function activate() {
        $post_type = new Event_Blocks_Event_Post_Type();
        $taxonomy = new Event_Blocks_Event_Category_Taxonomy();
        $content_types = new Meeple_Manager_Content_Types();

        $post_type->register();
        $taxonomy->register();
        $content_types->register();

        if (!wp_next_scheduled(MEEPLE_MANAGER_ANONYMIZE_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', MEEPLE_MANAGER_ANONYMIZE_HOOK);
        }

        flush_rewrite_rules();
    }

    public static function deactivate() {
        wp_clear_scheduled_hook(MEEPLE_MANAGER_ANONYMIZE_HOOK);
        flush_rewrite_rules();
    }
}
