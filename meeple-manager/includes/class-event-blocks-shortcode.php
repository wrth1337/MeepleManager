<?php

if (!defined('ABSPATH')) {
    exit;
}

class Event_Blocks_Shortcode {
    private $repository;
    private $normalizer;
    private $assets;
    private $style_settings;
    private $settings = null;

    public function __construct(
        Event_Blocks_Event_Repository $repository,
        Event_Blocks_Event_Normalizer $normalizer,
        Event_Blocks_Assets $assets,
        Event_Blocks_Style_Settings $style_settings
    ) {
        $this->repository = $repository;
        $this->normalizer = $normalizer;
        $this->assets = $assets;
        $this->style_settings = $style_settings;
    }

    public function register() {
        add_shortcode('event_blocks', array($this, 'render'));
        add_shortcode('vereins_events', array($this, 'render'));
    }

    public function render($atts) {
        $atts = shortcode_atts(
            array(
                'limit' => 0,
                'show_past' => '0',
            ),
            $atts,
            'event_blocks'
        );

        $this->assets->enqueue_frontend();

        $posts = $this->repository->get_events(
            array(
                'limit' => (int) $atts['limit'],
                'show_past' => $atts['show_past'] === '1',
            )
        );

        $events = array();

        foreach ($posts as $post) {
            $normalized = $this->normalizer->normalize_event($post);

            if ($normalized !== null) {
                $events[] = $normalized;
            }
        }

        return $this->render_events($events, array('class' => 'event-blocks-grid'), $atts['show_past'] === '1');
    }

    public function render_events($events, $wrapper_attributes = array(), $show_past = false) {
        if (empty($events)) {
            return $show_past
                ? '<div class="event-blocks-note" data-event-blocks-text="message_no_events">' . esc_html($this->get_text('message_no_events', 'Zurzeit sind keine Events vorhanden.')) . '</div>'
                : '<div class="event-blocks-note" data-event-blocks-text="message_no_upcoming_events">' . esc_html($this->get_text('message_no_upcoming_events', 'Zurzeit sind keine kommenden Events vorhanden.')) . '</div>';
        }

        $attributes = wp_parse_args(
            $wrapper_attributes,
            array(
                'class' => 'event-blocks-grid',
            )
        );

        $output = '<div' . $this->build_html_attributes($attributes) . '>';

        foreach ($events as $event) {
            $output .= $this->render_card($event);
        }

        $output .= '</div>';

        return $output;
    }

    private function render_card($event) {
        $title = esc_html($event['title']);
        $title_attr = esc_attr($event['title']);
        $date = esc_html($event['display_date']);
        $is_announcement = !empty($event['is_announcement']);
        $time = esc_html($event['time']);
        $location = esc_html($event['location']);
        $location_link = $event['location_link'];
        $image = $event['image'];
        $link = $event['link'];
        $notes = $event['notes'];
        $categories = $event['categories'];
        $announcement_label = esc_html($this->get_text('announcement_label', 'Vorankündigung'));
        $time_label = esc_html($this->get_text('label_time', 'Uhrzeit'));
        $location_label = esc_html($this->get_text('label_location', 'Ort'));
        $notes_label = esc_html($this->get_text('label_notes', 'Hinweise'));
        $more_info_label = esc_html($this->get_text('label_more_info', 'Mehr Informationen'));

        $classes = 'event-blocks-card';
        $attributes = '';

        if ($link !== '') {
            $classes .= ' event-blocks-card--clickable';
            $attributes = ' data-href="' . esc_url($link) . '" tabindex="0" role="link"';
        }

        if ($image === '') {
            $classes .= ' event-blocks-card--no-image';
        }

        $output = '<article class="' . esc_attr($classes) . '"' . $attributes . '>';

        if ($image !== '') {
            $output .= '<div class="event-blocks-media">';
            $output .= '<img class="event-blocks-image" src="' . esc_url($image) . '" alt="' . $title_attr . '" loading="lazy">';
            $output .= '</div>';
        }

        $output .= '<div class="event-blocks-content">';
        $output .= '<div class="event-blocks-topline">';
        $output .= '<div class="event-blocks-date-group">';
        $output .= '<div class="event-blocks-date-badge">' . $date . '</div>';

        if ($is_announcement) {
            $output .= '<div class="event-blocks-status" data-event-blocks-text="announcement_label">' . $announcement_label . '</div>';
        }

        $output .= '</div>';

        if (!empty($categories)) {
            $output .= '<div class="event-blocks-badges">';

            foreach ($categories as $category) {
                $output .= sprintf(
                    '<span class="event-blocks-badge" style="--event-blocks-badge-color:%1$s;">%2$s</span>',
                    esc_attr($category['color']),
                    esc_html($category['name'])
                );
            }

            $output .= '</div>';
        }

        $output .= '</div>';

        $output .= '<h3 class="event-blocks-title">' . $title . '</h3>';
        $output .= '<div class="event-blocks-meta">';

        if ($time !== '' && !$is_announcement) {
            $output .= '<div class="event-blocks-meta-item"><span class="event-blocks-meta-label" data-event-blocks-text="label_time">' . $time_label . '</span><span class="event-blocks-meta-value" data-event-blocks-time-value="' . esc_attr($event['time']) . '">' . esc_html($this->format_time_value($event['time'])) . '</span></div>';
        }
        if ($location !== '') {
            $output .= $this->render_location_meta_item($location_label, $location, $location_link);
        }
        $output .= '</div>';

        if (!empty($notes)) {
            $output .= '<div class="event-blocks-notes-block">';
            $output .= '<div class="event-blocks-notes-title" data-event-blocks-text="label_notes">' . $notes_label . '</div>';
            $output .= '<ul class="event-blocks-notes">';

            foreach ($notes as $note) {
                $output .= '<li>' . esc_html($note) . '</li>';
            }

            $output .= '</ul>';
            $output .= '</div>';
        }

        if ($link !== '') {
            $output .= '<div class="event-blocks-card-footer" data-event-blocks-text="label_more_info">' . $more_info_label . '</div>';
        }

        $output .= '</div>';
        $output .= '</article>';

        return $output;
    }

    private function render_location($location, $location_link) {
        if ($location_link === '') {
            return $location;
        }

        return sprintf(
            '<a class="event-blocks-location-link" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
            esc_url($location_link),
            $location
        );
    }

    private function render_location_meta_item($location_label, $location, $location_link) {
        if ($location_link === '') {
            return '<div class="event-blocks-meta-item"><span class="event-blocks-meta-label" data-event-blocks-text="label_location">' . $location_label . '</span><span class="event-blocks-meta-value">' . $location . '</span></div>';
        }

        return sprintf(
            '<a class="event-blocks-meta-item event-blocks-meta-item--link" href="%1$s" target="_blank" rel="noopener noreferrer"><span class="event-blocks-meta-label" data-event-blocks-text="label_location">%2$s</span><span class="event-blocks-meta-value">%3$s</span><span class="event-blocks-meta-link-indicator" aria-hidden="true">↗</span></a>',
            esc_url($location_link),
            $location_label,
            $location
        );
    }

    private function build_html_attributes($attributes) {
        $compiled_attributes = '';

        foreach ($attributes as $name => $value) {
            if (!is_string($name) || $name === '' || $value === '') {
                continue;
            }

            $compiled_attributes .= sprintf(
                ' %1$s="%2$s"',
                esc_attr($name),
                esc_attr((string) $value)
            );
        }

        return $compiled_attributes;
    }

    private function get_settings() {
        if ($this->settings === null) {
            $this->settings = $this->style_settings->get_settings();
        }

        return $this->settings;
    }

    private function get_text($key, $default) {
        $settings = $this->get_settings();

        return isset($settings[$key]) && is_string($settings[$key]) && $settings[$key] !== ''
            ? $settings[$key]
            : $default;
    }

    private function format_time_value($time) {
        $time = trim((string) $time);

        if ($time === '') {
            return '';
        }

        $suffix = trim($this->get_text('time_suffix', 'Uhr'));

        if ($suffix === '') {
            return $time;
        }

        return $time . ' ' . $suffix;
    }
}
