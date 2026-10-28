<?php

if (!defined('ABSPATH')) {
    exit;
}

class Event_Blocks_Plugin {
    private $style_settings;
    private $assets;
    private $shortcode;
    private $post_type;
    private $meta_boxes;
    private $category_taxonomy;
    private $admin_ui;
    private $content_types;
    private $game_meta_box;
    private $rentals;
    private $meeple_shortcodes;
    private $meeple_admin;
    private $game_style_settings;

    public function __construct() {
        $repository = new Event_Blocks_Event_Repository();
        $normalizer = new Event_Blocks_Event_Normalizer();
        $this->style_settings = new Event_Blocks_Style_Settings();

        $this->assets = new Event_Blocks_Assets($this->style_settings);
        $this->shortcode = new Event_Blocks_Shortcode($repository, $normalizer, $this->assets, $this->style_settings);
        $this->post_type = new Event_Blocks_Event_Post_Type();
        $this->meta_boxes = new Event_Blocks_Event_Meta_Boxes();
        $this->category_taxonomy = new Event_Blocks_Event_Category_Taxonomy();
        $this->admin_ui = new Event_Blocks_Admin_UI($normalizer, $this->style_settings, $this->shortcode, $this->assets);
        $this->content_types = new Meeple_Manager_Content_Types();
        $this->game_meta_box = new Meeple_Manager_Game_Meta_Box();
        $this->rentals = new Meeple_Manager_Rentals();
        $this->meeple_shortcodes = new Meeple_Manager_Shortcodes();
        $this->meeple_admin = new Meeple_Manager_Admin();
        $this->game_style_settings = new Meeple_Manager_Game_Style_Settings();
    }

    public function run() {
        add_action('init', array($this->post_type, 'register'));
        add_action('init', array($this->content_types, 'register'));
        add_action('after_setup_theme', array($this->content_types, 'register_image_sizes'));
        add_action('init', array($this->category_taxonomy, 'register'));
        add_action('wp_enqueue_scripts', array($this->assets, 'register'));
        add_action('add_meta_boxes', array($this->meta_boxes, 'register'));
        add_action('add_meta_boxes', array($this->game_meta_box, 'register'));
        add_action('save_post_' . EVENT_BLOCKS_POST_TYPE, array($this->meta_boxes, 'save'));
        add_action('save_post_' . MEEPLE_MANAGER_GAME_POST_TYPE, array($this->game_meta_box, 'save'));
        add_action('admin_notices', array($this->game_meta_box, 'admin_notice'));
        add_action(EVENT_BLOCKS_CATEGORY_TAXONOMY . '_add_form_fields', array($this->category_taxonomy, 'render_add_fields'));
        add_action(EVENT_BLOCKS_CATEGORY_TAXONOMY . '_edit_form_fields', array($this->category_taxonomy, 'render_edit_fields'));
        add_action('created_' . EVENT_BLOCKS_CATEGORY_TAXONOMY, array($this->category_taxonomy, 'save_term_meta'));
        add_action('edited_' . EVENT_BLOCKS_CATEGORY_TAXONOMY, array($this->category_taxonomy, 'save_term_meta'));
        add_action('admin_menu', array($this->admin_ui, 'register_admin_menu'));
        add_action('admin_init', array($this->admin_ui, 'register_style_settings'));
        add_action('admin_enqueue_scripts', array($this->admin_ui, 'enqueue_admin_assets'));
        add_filter('manage_' . EVENT_BLOCKS_POST_TYPE . '_posts_columns', array($this->admin_ui, 'filter_event_columns'));
        add_action('manage_' . EVENT_BLOCKS_POST_TYPE . '_posts_custom_column', array($this->admin_ui, 'render_event_column'), 10, 2);
        add_filter('manage_edit-' . EVENT_BLOCKS_POST_TYPE . '_sortable_columns', array($this->admin_ui, 'register_sortable_event_columns'));
        add_action('pre_get_posts', array($this->admin_ui, 'adjust_event_admin_query'));
        add_filter('manage_edit-' . EVENT_BLOCKS_CATEGORY_TAXONOMY . '_columns', array($this->admin_ui, 'filter_category_columns'));
        add_filter('manage_' . EVENT_BLOCKS_CATEGORY_TAXONOMY . '_custom_column', array($this->admin_ui, 'render_category_column'), 10, 3);
        $this->rentals->register();
        $this->meeple_admin->register();
        $this->game_style_settings->register();
        $this->shortcode->register();
        $this->meeple_shortcodes->register();
    }
}
