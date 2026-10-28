<?php

if (!defined('ABSPATH')) {
    exit;
}

class Event_Blocks_Style_Settings {
    public function get_controls() {
        return array(
            'grid_gap' => array(
                'label' => 'Abstand zwischen Kacheln',
                'type' => 'range',
                'default' => 22,
                'min' => 8,
                'max' => 40,
                'step' => 1,
                'unit' => 'px',
                'css_variable' => '--event-blocks-grid-gap',
                'description' => 'Steuert den vertikalen Abstand der Event-Kacheln.',
            ),
            'card_radius' => array(
                'label' => 'Eckenradius',
                'type' => 'range',
                'default' => 22,
                'min' => 0,
                'max' => 36,
                'step' => 1,
                'unit' => 'px',
                'css_variable' => '--event-blocks-card-radius',
                'description' => 'Bestimmt, wie weich die Kartenkanten gerundet sind.',
            ),
            'card_background' => array(
                'label' => 'Kartenhintergrund',
                'type' => 'color',
                'default' => '#ffffff',
                'css_variable' => '--event-blocks-card-bg',
                'description' => 'Grundfarbe der Kachel.',
            ),
            'content_background' => array(
                'label' => 'Inhaltsverlauf',
                'type' => 'color',
                'default' => '#f9fbf8',
                'css_variable' => '--event-blocks-content-bg',
                'description' => 'Farbton des unteren Verlaufs im Inhaltsbereich.',
            ),
            'border_color' => array(
                'label' => 'Rahmenfarbe',
                'type' => 'color',
                'default' => '#d8dfd4',
                'css_variable' => '--event-blocks-border-color',
                'description' => 'Wird für Karten- und Boxrahmen verwendet.',
            ),
            'accent_color' => array(
                'label' => 'Akzentfarbe',
                'type' => 'color',
                'default' => '#233927',
                'css_variable' => '--event-blocks-accent-color',
                'description' => 'Farbe für Datumsbadge, Footer-Link und Fokusakzente.',
            ),
            'text_color' => array(
                'label' => 'Primäre Textfarbe',
                'type' => 'color',
                'default' => '#1a281d',
                'css_variable' => '--event-blocks-text-color',
                'description' => 'Wird für Titel und Haupttext verwendet.',
            ),
            'muted_text_color' => array(
                'label' => 'Sekundäre Textfarbe',
                'type' => 'color',
                'default' => '#5a6f5f',
                'css_variable' => '--event-blocks-muted-text-color',
                'description' => 'Wird für Labels und dezente Hinweise verwendet.',
            ),
            'meta_background' => array(
                'label' => 'Meta-Boxen',
                'type' => 'color',
                'default' => '#f1f5ee',
                'css_variable' => '--event-blocks-meta-bg',
                'description' => 'Hintergrund für Uhrzeit- und Ortsboxen.',
            ),
            'note_background' => array(
                'label' => 'Hinweis-Boxen',
                'type' => 'color',
                'default' => '#f4f7f2',
                'css_variable' => '--event-blocks-note-bg',
                'description' => 'Hintergrund für die Hinweise und Leermeldungen.',
            ),
            'announcement_label' => array(
                'label' => 'Text für Vorankündigungen',
                'type' => 'text',
                'default' => 'Vorankündigung',
                'description' => 'Dieser Text erscheint auf Kacheln, die als Vorankündigung markiert sind.',
            ),
            'label_time' => array(
                'label' => 'Beschriftung für Uhrzeit',
                'type' => 'text',
                'default' => 'Uhrzeit',
                'description' => 'Beschriftung oberhalb der Uhrzeit in der Kachel.',
            ),
            'time_suffix' => array(
                'label' => 'Suffix für Uhrzeit',
                'type' => 'text',
                'default' => 'Uhr',
                'description' => 'Wird hinter die Uhrzeit gesetzt.',
            ),
            'label_location' => array(
                'label' => 'Beschriftung für Ort',
                'type' => 'text',
                'default' => 'Ort',
                'description' => 'Beschriftung oberhalb des Orts in der Kachel.',
            ),
            'label_notes' => array(
                'label' => 'Beschriftung für Hinweise',
                'type' => 'text',
                'default' => 'Hinweise',
                'description' => 'Titel der Hinweis-Box in der Kachel.',
            ),
            'label_more_info' => array(
                'label' => 'Text für Link-Hinweis',
                'type' => 'text',
                'default' => 'Mehr Informationen',
                'description' => 'Text im Footer klickbarer Kacheln.',
            ),
            'message_no_events' => array(
                'label' => 'Text bei leerer Event-Liste',
                'type' => 'text',
                'default' => 'Zurzeit sind keine Events vorhanden.',
                'description' => 'Wird angezeigt, wenn keine Events vorhanden sind.',
            ),
            'message_no_upcoming_events' => array(
                'label' => 'Text bei keinen kommenden Events',
                'type' => 'text',
                'default' => 'Zurzeit sind keine kommenden Events vorhanden.',
                'description' => 'Wird angezeigt, wenn nur kommende Events angefordert werden und keine vorhanden sind.',
            ),
            'announcement_background' => array(
                'label' => 'Vorankündigungs-Hintergrund',
                'type' => 'color',
                'default' => '#f4f7f2',
                'css_variable' => '--event-blocks-announcement-bg',
                'description' => 'Hintergrundfarbe des Vorankündigungs-Labels.',
            ),
            'announcement_text_color' => array(
                'label' => 'Vorankündigungs-Text',
                'type' => 'color',
                'default' => '#233927',
                'css_variable' => '--event-blocks-announcement-text',
                'description' => 'Textfarbe des Vorankündigungs-Labels.',
            ),
            'announcement_border_color' => array(
                'label' => 'Vorankündigungs-Rahmen',
                'type' => 'color',
                'default' => '#d8dfd4',
                'css_variable' => '--event-blocks-announcement-border',
                'description' => 'Rahmenfarbe des Vorankündigungs-Labels.',
            ),
            'announcement_radius' => array(
                'label' => 'Vorankündigungs-Radius',
                'type' => 'range',
                'default' => 999,
                'min' => 0,
                'max' => 999,
                'step' => 1,
                'unit' => 'px',
                'css_variable' => '--event-blocks-announcement-radius',
                'description' => 'Eckenradius des Vorankündigungs-Labels.',
            ),
        );
    }

    public function get_defaults() {
        $defaults = array();

        foreach ($this->get_controls() as $key => $control) {
            $defaults[$key] = $control['default'];
        }

        return $defaults;
    }

    public function get_settings() {
        $stored_settings = get_option(EVENT_BLOCKS_OPTION_STYLES, array());

        if (!is_array($stored_settings)) {
            $stored_settings = array();
        }

        return wp_parse_args($this->sanitize_settings($stored_settings), $this->get_defaults());
    }

    public function sanitize_settings($input) {
        $controls = $this->get_controls();
        $sanitized = array();

        if (!is_array($input)) {
            $input = array();
        }

        foreach ($controls as $key => $control) {
            $raw_value = isset($input[$key]) ? $input[$key] : $control['default'];

            if ($control['type'] === 'color') {
                $sanitized[$key] = $this->sanitize_color($raw_value, $control['default']);
                continue;
            }

            if ($control['type'] === 'text') {
                $sanitized[$key] = $this->sanitize_text($raw_value, $control['default']);
                continue;
            }

            $sanitized[$key] = $this->sanitize_range_value($raw_value, $control);
        }

        return $sanitized;
    }

    public function build_css_declaration_block($settings = null) {
        $variables = $this->get_css_variables($settings);
        $declarations = array();

        foreach ($variables as $name => $value) {
            $declarations[] = $name . ':' . $value;
        }

        return implode(';', $declarations) . ';';
    }

    public function build_inline_css($selector = ':root', $settings = null) {
        return $selector . '{' . $this->build_css_declaration_block($settings) . '}';
    }

    public function get_css_variables($settings = null) {
        $controls = $this->get_controls();
        $settings = $settings === null ? $this->get_settings() : wp_parse_args($settings, $this->get_defaults());
        $variables = array();

        foreach ($controls as $key => $control) {
            $value = $settings[$key];

            if (!isset($control['css_variable'])) {
                continue;
            }

            if ($control['type'] === 'range') {
                $value .= $control['unit'];
            }

            $variables[$control['css_variable']] = $value;
        }

        return $variables;
    }

    private function sanitize_color($value, $default) {
        $color = sanitize_hex_color((string) $value);

        if (is_string($color) && $color !== '') {
            return $color;
        }

        return $default;
    }

    private function sanitize_range_value($value, $control) {
        $value = absint($value);
        $minimum = (int) $control['min'];
        $maximum = (int) $control['max'];

        if ($value < $minimum) {
            return $minimum;
        }

        if ($value > $maximum) {
            return $maximum;
        }

        return $value;
    }

    private function sanitize_text($value, $default) {
        $value = trim(sanitize_text_field((string) $value));

        if ($value === '') {
            return $default;
        }

        return $value;
    }
}
