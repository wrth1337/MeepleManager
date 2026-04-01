<?php

if (!defined('ABSPATH')) {
    exit;
}

class Event_Blocks_Admin_UI {
    private $normalizer;
    private $style_settings;
    private $shortcode;
    private $assets;
    private $style_page_hook = '';

    public function __construct(
        Event_Blocks_Event_Normalizer $normalizer,
        Event_Blocks_Style_Settings $style_settings,
        Event_Blocks_Shortcode $shortcode,
        Event_Blocks_Assets $assets
    ) {
        $this->normalizer = $normalizer;
        $this->style_settings = $style_settings;
        $this->shortcode = $shortcode;
        $this->assets = $assets;
    }

    public function filter_event_columns($columns) {
        $ordered = array();

        if (isset($columns['cb'])) {
            $ordered['cb'] = $columns['cb'];
        }

        $ordered['title'] = 'Titel';
        $ordered['event_date'] = 'Datum';
        $ordered['event_time'] = 'Uhrzeit';
        $ordered['event_location'] = 'Ort';
        $ordered['taxonomy-' . EVENT_BLOCKS_CATEGORY_TAXONOMY] = 'Kategorien';
        $ordered['event_shortcode'] = 'Shortcode';
        $ordered['date'] = 'Erstellt';

        return $ordered;
    }

    public function render_event_column($column, $post_id) {
        if ($column === 'event_date') {
            $timestamp = (int) get_post_meta($post_id, EVENT_BLOCKS_META_START_TIMESTAMP, true);

            if ($timestamp > 0) {
                echo esc_html($this->normalizer->format_date($timestamp));
            } else {
                echo '-';
            }

            return;
        }

        if ($column === 'event_time') {
            $time = trim((string) get_post_meta($post_id, EVENT_BLOCKS_META_TIME, true));
            echo $time !== '' ? esc_html($time) . ' Uhr' : '-';
            return;
        }

        if ($column === 'event_location') {
            $location = trim((string) get_post_meta($post_id, EVENT_BLOCKS_META_LOCATION, true));
            echo $location !== '' ? esc_html($location) : '-';
            return;
        }

        if ($column === 'event_shortcode') {
            echo '<code>[event_blocks limit="1"]</code>';
        }
    }

    public function register_sortable_event_columns($columns) {
        $columns['event_date'] = 'event_date';

        return $columns;
    }

    public function adjust_event_admin_query($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->get('post_type') !== EVENT_BLOCKS_POST_TYPE) {
            return;
        }

        $orderby = $query->get('orderby');

        if ($orderby === 'event_date' || $orderby === '') {
            $query->set('meta_key', EVENT_BLOCKS_META_START_TIMESTAMP);
            $query->set('orderby', 'meta_value_num');

            if ($query->get('order') === '') {
                $query->set('order', 'ASC');
            }
        }
    }

    public function filter_category_columns($columns) {
        $ordered = array();

        foreach ($columns as $key => $label) {
            $ordered[$key] = $label;

            if ($key === 'name') {
                $ordered['event_blocks_color'] = 'Farbe';
            }
        }

        return $ordered;
    }

    public function render_category_column($content, $column_name, $term_id) {
        if ($column_name !== 'event_blocks_color') {
            return $content;
        }

        $color = (string) get_term_meta($term_id, EVENT_BLOCKS_TERM_META_COLOR, true);

        if ($color === '') {
            $color = '#4f46e5';
        }

        return sprintf(
            '<span style="display:inline-flex;align-items:center;gap:8px;"><span style="width:14px;height:14px;border-radius:999px;background:%1$s;border:1px solid rgba(0,0,0,0.12);display:inline-block;"></span><code>%1$s</code></span>',
            esc_attr($color)
        );
    }

    public function register_admin_menu() {
        $this->style_page_hook = add_submenu_page(
            'edit.php?post_type=' . EVENT_BLOCKS_POST_TYPE,
            'Kachel-Design',
            'Kachel-Design',
            'manage_options',
            'event-blocks-style-editor',
            array($this, 'render_style_page')
        );
    }

    public function register_style_settings() {
        register_setting(
            'event_blocks_styles',
            EVENT_BLOCKS_OPTION_STYLES,
            array($this->style_settings, 'sanitize_settings')
        );
    }

    public function enqueue_admin_assets($hook_suffix) {
        if ($hook_suffix !== $this->style_page_hook) {
            return;
        }

        $this->assets->enqueue_style_editor();
    }

    public function render_style_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html('Du hast keine Berechtigung, diese Seite zu verwalten.'));
        }

        $settings_updated = isset($_GET['settings-updated'])
            ? sanitize_text_field(wp_unslash($_GET['settings-updated']))
            : '';

        if ($settings_updated === 'true') {
            add_settings_error(
                'event_blocks_style_messages',
                'event_blocks_style_message',
                'Das Kachel-Design wurde gespeichert.',
                'updated'
            );
        }

        $settings = $this->style_settings->get_settings();
        $controls = $this->style_settings->get_controls();
        ?>
        <div class="wrap event-blocks-style-editor">
            <h1>Kachel-Design</h1>
            <p>Hier passt du das Erscheinungsbild der Event-Kacheln zentral an. Die Live-Vorschau rechts verwendet dieselbe Markup-Struktur wie der Shortcode im Frontend.</p>
            <?php settings_errors('event_blocks_style_messages'); ?>
            <div class="event-blocks-style-editor__layout">
                <form class="event-blocks-style-editor__form" method="post" action="options.php" data-event-blocks-style-form>
                    <?php settings_fields('event_blocks_styles'); ?>
                    <div class="event-blocks-style-editor__card">
                        <div class="event-blocks-style-editor__card-head">
                            <h2>Farben und Abstände</h2>
                            <p>Alle Änderungen werden sofort in der Vorschau angezeigt. Gespeichert wird erst mit dem Button unten.</p>
                        </div>
                        <div class="event-blocks-style-editor__controls">
                            <?php foreach ($controls as $key => $control) : ?>
                                <?php $this->render_style_control($key, $control, $settings[$key]); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="event-blocks-style-editor__actions">
                        <?php submit_button('Design speichern', 'primary', 'submit', false); ?>
                        <button type="button" class="button button-secondary" data-event-blocks-reset-defaults>Standardwerte laden</button>
                    </div>
                </form>
                <aside class="event-blocks-style-editor__preview-panel">
                    <div class="event-blocks-style-editor__preview-head">
                        <h2>Live-Vorschau</h2>
                        <p>Die Vorschau ist lokal auf dieser Seite und speichert noch nichts.</p>
                    </div>
                    <div
                        class="event-blocks-style-editor__preview"
                        data-event-blocks-preview
                        style="<?php echo esc_attr($this->style_settings->build_css_declaration_block($settings)); ?>"
                    >
                        <?php
                        echo $this->shortcode->render_events(
                            $this->get_preview_events(),
                            array('class' => 'event-blocks-grid event-blocks-grid--preview')
                        );
                        ?>
                    </div>
                </aside>
            </div>
        </div>
        <?php
    }

    private function render_style_control($key, $control, $value) {
        $field_id = 'event-blocks-style-' . $key;
        $field_name = EVENT_BLOCKS_OPTION_STYLES . '[' . $key . ']';
        $description_id = $field_id . '-description';
        $output_id = $field_id . '-value';
        ?>
        <div class="event-blocks-style-editor__control">
            <label for="<?php echo esc_attr($field_id); ?>" class="event-blocks-style-editor__label"><?php echo esc_html($control['label']); ?></label>
            <?php if ($control['type'] === 'range') : ?>
                <div class="event-blocks-style-editor__range-wrap">
                    <input
                        id="<?php echo esc_attr($field_id); ?>"
                        name="<?php echo esc_attr($field_name); ?>"
                        type="range"
                        min="<?php echo esc_attr($control['min']); ?>"
                        max="<?php echo esc_attr($control['max']); ?>"
                        step="<?php echo esc_attr($control['step']); ?>"
                        value="<?php echo esc_attr($value); ?>"
                        aria-describedby="<?php echo esc_attr($description_id); ?>"
                        data-event-blocks-setting
                        data-css-variable="<?php echo esc_attr($control['css_variable']); ?>"
                        data-css-unit="<?php echo esc_attr($control['unit']); ?>"
                        data-default="<?php echo esc_attr($control['default']); ?>"
                        data-output-id="<?php echo esc_attr($output_id); ?>"
                    >
                    <output id="<?php echo esc_attr($output_id); ?>" class="event-blocks-style-editor__range-value"><?php echo esc_html($value . $control['unit']); ?></output>
                </div>
            <?php else : ?>
                <input
                    id="<?php echo esc_attr($field_id); ?>"
                    name="<?php echo esc_attr($field_name); ?>"
                    type="color"
                    value="<?php echo esc_attr($value); ?>"
                    aria-describedby="<?php echo esc_attr($description_id); ?>"
                    data-event-blocks-setting
                    data-css-variable="<?php echo esc_attr($control['css_variable']); ?>"
                    data-default="<?php echo esc_attr($control['default']); ?>"
                >
            <?php endif; ?>
            <p id="<?php echo esc_attr($description_id); ?>" class="description"><?php echo esc_html($control['description']); ?></p>
        </div>
        <?php
    }

    private function get_preview_events() {
        return array(
            array(
                'title' => 'Sommerkonzert im Park',
                'timestamp' => strtotime('2026-07-18 19:30:00'),
                'time' => '19:30',
                'location' => 'Stadtpark Nord',
                'location_link' => 'https://example.com/anfahrt',
                'image' => '',
                'link' => 'https://example.com/tickets',
                'notes' => array(
                    'Einlass ab 18:45 Uhr',
                    'Bei Regen findet das Event im Vereinshaus statt',
                ),
                'categories' => array(
                    array(
                        'name' => 'Konzert',
                        'slug' => 'konzert',
                        'color' => '#1f7a8c',
                    ),
                    array(
                        'name' => 'Open Air',
                        'slug' => 'open-air',
                        'color' => '#c96834',
                    ),
                ),
            ),
            array(
                'title' => 'Mitgliederversammlung',
                'timestamp' => strtotime('2026-08-05 18:00:00'),
                'time' => '18:00',
                'location' => 'Vereinsheim West',
                'location_link' => '',
                'image' => '',
                'link' => '',
                'notes' => array(
                    'Tagesordnung folgt separat per E-Mail',
                ),
                'categories' => array(
                    array(
                        'name' => 'Intern',
                        'slug' => 'intern',
                        'color' => '#7b8c3a',
                    ),
                ),
            ),
        );
    }
}
