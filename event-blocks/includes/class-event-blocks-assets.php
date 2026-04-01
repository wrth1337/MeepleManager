<?php

if (!defined('ABSPATH')) {
    exit;
}

class Event_Blocks_Assets {
    public function register() {
        if (!wp_style_is('event-blocks-frontend', 'registered')) {
            wp_register_style(
                'event-blocks-frontend',
                EVENT_BLOCKS_URL . 'assets/css/event-blocks.css',
                array(),
                EVENT_BLOCKS_VERSION
            );
        }

        if (!wp_script_is('event-blocks-frontend', 'registered')) {
            wp_register_script(
                'event-blocks-frontend',
                EVENT_BLOCKS_URL . 'assets/js/event-blocks.js',
                array(),
                EVENT_BLOCKS_VERSION,
                true
            );
        }
    }

    public function enqueue_frontend() {
        $this->register();
        wp_enqueue_style('event-blocks-frontend');
        wp_enqueue_script('event-blocks-frontend');
    }
}
