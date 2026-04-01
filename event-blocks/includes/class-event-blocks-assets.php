<?php

if (!defined('ABSPATH')) {
    exit;
}

class Event_Blocks_Assets {
    private $style_settings;
    private $frontend_inline_style_added = false;

    public function __construct(Event_Blocks_Style_Settings $style_settings) {
        $this->style_settings = $style_settings;
    }

    public function register() {
        if (!wp_style_is('event-blocks-frontend', 'registered')) {
            wp_register_style(
                'event-blocks-frontend',
                EVENT_BLOCKS_URL . 'assets/css/event-blocks.css',
                array(),
                EVENT_BLOCKS_VERSION
            );
        }

        if (!wp_style_is('event-blocks-admin', 'registered')) {
            wp_register_style(
                'event-blocks-admin',
                EVENT_BLOCKS_URL . 'assets/css/event-blocks-admin.css',
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

        if (!wp_script_is('event-blocks-admin', 'registered')) {
            wp_register_script(
                'event-blocks-admin',
                EVENT_BLOCKS_URL . 'assets/js/event-blocks-admin.js',
                array(),
                EVENT_BLOCKS_VERSION,
                true
            );
        }
    }

    public function enqueue_frontend() {
        $this->register();
        wp_enqueue_style('event-blocks-frontend');

        if (!$this->frontend_inline_style_added) {
            wp_add_inline_style('event-blocks-frontend', $this->style_settings->build_inline_css());
            $this->frontend_inline_style_added = true;
        }

        wp_enqueue_script('event-blocks-frontend');
    }

    public function enqueue_style_editor() {
        $this->register();
        wp_enqueue_style('event-blocks-frontend');
        wp_enqueue_style('event-blocks-admin');
        wp_enqueue_script('event-blocks-admin');
    }
}
