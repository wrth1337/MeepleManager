<?php

if (!defined('ABSPATH')) {
    exit;
}

class Meeple_Manager_Game_Style_Settings {
    private $page_hook = '';

    public function register() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_init', array($this, 'register_setting'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'), 20);
    }

    public function register_menu() {
        $this->page_hook = add_submenu_page(
            MEEPLE_MANAGER_MENU_SLUG,
            'Spiele-Kachel-Design',
            'Spiele-Design',
            'manage_options',
            'meeple-manager-game-styles',
            array($this, 'render_page')
        );
    }

    public function register_setting() {
        register_setting(
            'meeple_manager_game_styles_group',
            MEEPLE_MANAGER_OPTION_GAME_STYLES,
            array(__CLASS__, 'sanitize_settings')
        );
    }

    public function enqueue_admin_assets($hook_suffix) {
        if ($hook_suffix !== $this->page_hook) {
            return;
        }

        wp_enqueue_style(
            'meeple-manager-frontend',
            MEEPLE_MANAGER_URL . 'assets/css/meeple-manager.css',
            array(),
            MEEPLE_MANAGER_VERSION
        );
        wp_enqueue_script(
            'meeple-manager-game-style-editor',
            MEEPLE_MANAGER_URL . 'assets/js/meeple-manager-game-style-editor.js',
            array(),
            MEEPLE_MANAGER_VERSION,
            true
        );
    }

    public function enqueue_frontend_styles() {
        wp_enqueue_style(
            'meeple-manager-frontend',
            MEEPLE_MANAGER_URL . 'assets/css/meeple-manager.css',
            array(),
            MEEPLE_MANAGER_VERSION
        );

        wp_add_inline_style('meeple-manager-frontend', $this->build_css_declaration_block());
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html('Du hast keine Berechtigung, das Spiele-Design zu verwalten.'));
        }

        if (isset($_GET['settings-updated']) && sanitize_text_field(wp_unslash($_GET['settings-updated'])) === 'true') {
            add_settings_error(
                'meeple_manager_game_style_messages',
                'meeple_manager_game_style_message',
                'Das Spiele-Design wurde gespeichert.',
                'updated'
            );
        }

        $settings = self::get_settings();
        ?>
        <div class="wrap meeple-manager-admin meeple-manager-game-style-editor">
            <h1>Spiele-Design</h1>
            <p>Lege Farben und Eckenradius für den öffentlichen Katalog und den passwortgeschützten Verleihbereich fest. Die Vorschau rechts zeigt beide Ansichten.</p>
            <?php settings_errors('meeple_manager_game_style_messages'); ?>
            <div class="meeple-manager-game-style-editor__layout">
                <form class="meeple-manager-admin-card meeple-manager-game-style-editor__form" method="post" action="options.php" data-meeple-game-style-form>
                    <?php settings_fields('meeple_manager_game_styles_group'); ?>
                    <h2>Design-Einstellungen</h2>
                    <?php $current_section = ''; ?>
                    <?php foreach (self::get_controls() as $key => $control) : ?>
                        <?php if ($current_section !== $control['section']) : ?>
                            <?php $current_section = $control['section']; ?>
                            <h3 class="meeple-manager-game-style-editor__section-title"><?php echo esc_html($current_section); ?></h3>
                        <?php endif; ?>
                        <?php $this->render_control($key, $control, $settings[$key]); ?>
                    <?php endforeach; ?>
                    <div class="meeple-manager-game-style-editor__actions">
                        <?php submit_button('Design speichern', 'primary', 'submit', false); ?>
                        <button type="button" class="button button-secondary" data-meeple-game-style-reset>Standardwerte laden</button>
                    </div>
                </form>
                <aside class="meeple-manager-admin-card meeple-manager-game-style-editor__preview-panel">
                    <h2>Vorschau</h2>
                    <div class="meeple-manager-game-style-editor__preview" data-meeple-game-style-preview style="<?php echo esc_attr($this->build_css_declaration_block($settings)); ?>">
                        <h3>Öffentlicher Katalog</h3>
                        <ul class="meeple-manager-catalog">
                            <li class="meeple-manager-catalog__item">
                                <div class="meeple-manager-game-card__image-wrap"><div class="meeple-manager-game-card__placeholder">Spielbild</div></div>
                                <h2 class="meeple-manager-catalog__title">Catan – Das Duell</h2>
                            </li>
                            <li class="meeple-manager-catalog__item">
                                <div class="meeple-manager-game-card__image-wrap"><div class="meeple-manager-game-card__placeholder">Spielbild</div></div>
                                <h2 class="meeple-manager-catalog__title">Die Crew</h2>
                            </li>
                        </ul>
                        <h3>Passwortgeschützter Verleih</h3>
                        <div class="meeple-manager-lending">
                            <div class="meeple-manager-table-wrap">
                                <table class="meeple-manager-table">
                                    <thead><tr><th>Spiel</th><th>Kategorie</th><th>Verfügbarkeit</th></tr></thead>
                                    <tbody>
                                        <tr><td>Catan – Das Duell</td><td><span class="meeple-manager-category meeple-manager-category--green">Grün</span></td><td>Verfügbar</td></tr>
                                        <tr><td>Die Crew</td><td><span class="meeple-manager-category meeple-manager-category--yellow">Gelb</span></td><td>Ausgeliehen</td></tr>
                                        <tr><td>Terraforming Mars</td><td><span class="meeple-manager-category meeple-manager-category--red">Rot</span></td><td>Nicht ausleihbar</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <section class="meeple-manager-request">
                                <h3>Ausleihanfrage stellen</h3>
                                <p><input type="text" placeholder="Vorname"></p>
                                <button type="button" class="meeple-manager-button">Anfrage senden</button>
                            </section>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
        <?php
    }

    public static function get_controls() {
        return array(
            'border_color' => array(
                'section' => 'Gemeinsame Darstellung',
                'label' => 'Rahmen- und Linienfarbe',
                'type' => 'color',
                'default' => '#d8dfd4',
                'css_variable' => '--meeple-manager-game-card-border',
            ),
            'card_radius' => array(
                'section' => 'Gemeinsame Darstellung',
                'label' => 'Eckenradius von Kacheln und Boxen',
                'type' => 'range',
                'default' => 10,
                'min' => 0,
                'max' => 36,
                'step' => 1,
                'unit' => 'px',
                'css_variable' => '--meeple-manager-game-card-radius',
            ),
            'card_background' => array(
                'section' => 'Spielekatalog',
                'label' => 'Kachelhintergrund',
                'type' => 'color',
                'default' => '#ffffff',
                'css_variable' => '--meeple-manager-game-card-bg',
            ),
            'title_color' => array(
                'section' => 'Spielekatalog',
                'label' => 'Titelfarbe',
                'type' => 'color',
                'default' => '#1a281d',
                'css_variable' => '--meeple-manager-game-title-color',
            ),
            'placeholder_background' => array(
                'section' => 'Spielekatalog',
                'label' => 'Hintergrund bei fehlendem Bild',
                'type' => 'color',
                'default' => '#f1f5ee',
                'css_variable' => '--meeple-manager-game-placeholder-bg',
            ),
            'table_background' => array(
                'section' => 'Verleihbereich',
                'label' => 'Tabellenhintergrund',
                'type' => 'color',
                'default' => '#ffffff',
                'css_variable' => '--meeple-manager-game-table-bg',
            ),
            'table_header_background' => array(
                'section' => 'Verleihbereich',
                'label' => 'Tabellenkopf',
                'type' => 'color',
                'default' => '#f1f5ee',
                'css_variable' => '--meeple-manager-game-table-header-bg',
            ),
            'table_text_color' => array(
                'section' => 'Verleihbereich',
                'label' => 'Tabellentext',
                'type' => 'color',
                'default' => '#1a281d',
                'css_variable' => '--meeple-manager-game-table-text-color',
            ),
            'request_background' => array(
                'section' => 'Verleihbereich',
                'label' => 'Hintergrund des Anfrageformulars',
                'type' => 'color',
                'default' => '#f9fbf8',
                'css_variable' => '--meeple-manager-game-request-bg',
            ),
            'button_background' => array(
                'section' => 'Verleihbereich',
                'label' => 'Farbe der Anfrage-Schaltfläche',
                'type' => 'color',
                'default' => '#233927',
                'css_variable' => '--meeple-manager-game-button-bg',
            ),
            'button_hover_background' => array(
                'section' => 'Verleihbereich',
                'label' => 'Farbe der Anfrage-Schaltfläche bei Hover',
                'type' => 'color',
                'default' => '#36523d',
                'css_variable' => '--meeple-manager-game-button-hover-bg',
            ),
            'category_green' => array(
                'section' => 'Verleihbereich',
                'label' => 'Farbe der Kategorie Grün',
                'type' => 'color',
                'default' => '#25803b',
                'css_variable' => '--meeple-manager-game-category-green',
            ),
            'category_yellow' => array(
                'section' => 'Verleihbereich',
                'label' => 'Farbe der Kategorie Gelb',
                'type' => 'color',
                'default' => '#9a7000',
                'css_variable' => '--meeple-manager-game-category-yellow',
            ),
            'category_red' => array(
                'section' => 'Verleihbereich',
                'label' => 'Farbe der Kategorie Rot',
                'type' => 'color',
                'default' => '#bb3030',
                'css_variable' => '--meeple-manager-game-category-red',
            ),
        );
    }

    public static function get_settings() {
        $stored = get_option(MEEPLE_MANAGER_OPTION_GAME_STYLES, array());
        return wp_parse_args(self::sanitize_settings($stored), self::get_defaults());
    }

    public static function get_defaults() {
        $defaults = array();

        foreach (self::get_controls() as $key => $control) {
            $defaults[$key] = $control['default'];
        }

        return $defaults;
    }

    public static function sanitize_settings($input) {
        if (!is_array($input)) {
            $input = array();
        }

        $settings = self::get_defaults();

        foreach (self::get_controls() as $key => $control) {
            if (!isset($input[$key]) || !is_scalar($input[$key])) {
                continue;
            }

            if ($control['type'] === 'color') {
                $color = sanitize_hex_color((string) $input[$key]);
                $settings[$key] = $color ? $color : $control['default'];
                continue;
            }

            $value = absint($input[$key]);
            $settings[$key] = min((int) $control['max'], max((int) $control['min'], $value));
        }

        return $settings;
    }

    public function build_css_declaration_block($settings = null) {
        $settings = $settings === null ? self::get_settings() : wp_parse_args($settings, self::get_defaults());
        $declarations = array();

        foreach (self::get_controls() as $key => $control) {
            $value = $settings[$key];

            if ($control['type'] === 'range') {
                $value .= $control['unit'];
            }

            $declarations[] = $control['css_variable'] . ':' . $value;
        }

        return implode(';', $declarations) . ';';
    }

    private function render_control($key, $control, $value) {
        $field_id = 'meeple-manager-game-style-' . $key;
        $field_name = MEEPLE_MANAGER_OPTION_GAME_STYLES . '[' . $key . ']';
        ?>
        <p class="meeple-manager-game-style-editor__control">
            <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($control['label']); ?></label>
            <?php if ($control['type'] === 'range') : ?>
                <span class="meeple-manager-game-style-editor__range">
                    <input
                        id="<?php echo esc_attr($field_id); ?>"
                        name="<?php echo esc_attr($field_name); ?>"
                        type="range"
                        min="<?php echo esc_attr($control['min']); ?>"
                        max="<?php echo esc_attr($control['max']); ?>"
                        step="<?php echo esc_attr($control['step']); ?>"
                        value="<?php echo esc_attr($value); ?>"
                        data-meeple-game-style-setting
                        data-css-variable="<?php echo esc_attr($control['css_variable']); ?>"
                        data-css-unit="<?php echo esc_attr($control['unit']); ?>"
                        data-default="<?php echo esc_attr($control['default']); ?>"
                        data-output-id="<?php echo esc_attr($field_id . '-value'); ?>"
                    >
                    <output id="<?php echo esc_attr($field_id . '-value'); ?>"><?php echo esc_html($value . $control['unit']); ?></output>
                </span>
            <?php else : ?>
                <input
                    id="<?php echo esc_attr($field_id); ?>"
                    name="<?php echo esc_attr($field_name); ?>"
                    type="color"
                    value="<?php echo esc_attr($value); ?>"
                    data-meeple-game-style-setting
                    data-css-variable="<?php echo esc_attr($control['css_variable']); ?>"
                    data-default="<?php echo esc_attr($control['default']); ?>"
                >
            <?php endif; ?>
        </p>
        <?php
    }
}
