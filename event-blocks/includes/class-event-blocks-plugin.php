<?php

if (!defined('ABSPATH')) {
    exit;
}

class Event_Blocks_Plugin {
    private $assets;
    private $shortcode;
    private $post_type;
    private $meta_boxes;
    private $category_taxonomy;
    private $admin_ui;

    public function __construct() {
        $repository = new Event_Blocks_Event_Repository();
        $normalizer = new Event_Blocks_Event_Normalizer();

        $this->assets = new Event_Blocks_Assets();
        $this->shortcode = new Event_Blocks_Shortcode($repository, $normalizer, $this->assets);
        $this->post_type = new Event_Blocks_Event_Post_Type();
        $this->meta_boxes = new Event_Blocks_Event_Meta_Boxes();
        $this->category_taxonomy = new Event_Blocks_Event_Category_Taxonomy();
        $this->admin_ui = new Event_Blocks_Admin_UI($normalizer);
    }

    public function run() {
        add_action('init', array($this->post_type, 'register'));
        add_action('init', array($this->category_taxonomy, 'register'));
        add_action('wp_enqueue_scripts', array($this->assets, 'register'));
        add_action('add_meta_boxes', array($this->meta_boxes, 'register'));
        add_action('save_post_' . EVENT_BLOCKS_POST_TYPE, array($this->meta_boxes, 'save'));
        add_action(EVENT_BLOCKS_CATEGORY_TAXONOMY . '_add_form_fields', array($this->category_taxonomy, 'render_add_fields'));
        add_action(EVENT_BLOCKS_CATEGORY_TAXONOMY . '_edit_form_fields', array($this->category_taxonomy, 'render_edit_fields'));
        add_action('created_' . EVENT_BLOCKS_CATEGORY_TAXONOMY, array($this->category_taxonomy, 'save_term_meta'));
        add_action('edited_' . EVENT_BLOCKS_CATEGORY_TAXONOMY, array($this->category_taxonomy, 'save_term_meta'));
        add_filter('manage_' . EVENT_BLOCKS_POST_TYPE . '_posts_columns', array($this->admin_ui, 'filter_event_columns'));
        add_action('manage_' . EVENT_BLOCKS_POST_TYPE . '_posts_custom_column', array($this->admin_ui, 'render_event_column'), 10, 2);
        add_filter('manage_edit-' . EVENT_BLOCKS_POST_TYPE . '_sortable_columns', array($this->admin_ui, 'register_sortable_event_columns'));
        add_action('pre_get_posts', array($this->admin_ui, 'adjust_event_admin_query'));
        add_filter('manage_edit-' . EVENT_BLOCKS_CATEGORY_TAXONOMY . '_columns', array($this->admin_ui, 'filter_category_columns'));
        add_filter('manage_' . EVENT_BLOCKS_CATEGORY_TAXONOMY . '_custom_column', array($this->admin_ui, 'render_category_column'), 10, 3);
        $this->shortcode->register();
    }
}
