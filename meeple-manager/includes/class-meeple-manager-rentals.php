<?php

if (!defined('ABSPATH')) {
    exit;
}

class Meeple_Manager_Rentals {
    public function register() {
        add_action('admin_post_nopriv_meeple_manager_submit_request', array($this, 'submit_request'));
        add_action('admin_post_meeple_manager_submit_request', array($this, 'submit_request'));
        add_action('admin_post_meeple_manager_decide_request', array($this, 'decide_request'));
        add_action('admin_post_meeple_manager_return_loan', array($this, 'return_loan'));
        add_action('meeple_manager_anonymize_returned_loans', array($this, 'anonymize_returned_loans'));
    }

    public function submit_request() {
        check_admin_referer('meeple_manager_submit_request', 'meeple_manager_request_nonce');

        $game_id = isset($_POST['meeple_game_id']) ? absint($_POST['meeple_game_id']) : 0;
        $first_name = isset($_POST['meeple_first_name']) ? sanitize_text_field(wp_unslash($_POST['meeple_first_name'])) : '';
        $last_name = isset($_POST['meeple_last_name']) ? sanitize_text_field(wp_unslash($_POST['meeple_last_name'])) : '';
        $email = isset($_POST['meeple_email']) ? sanitize_email(wp_unslash($_POST['meeple_email'])) : '';
        $message = isset($_POST['meeple_message']) ? sanitize_textarea_field(wp_unslash($_POST['meeple_message'])) : '';
        $redirect = $this->get_frontend_redirect();

        if ($first_name === '' || $last_name === '' || !is_email($email)) {
            wp_safe_redirect(add_query_arg('meeple_request_status', 'invalid', $redirect));
            exit;
        }

        $game = get_post($game_id);

        if (!$game instanceof WP_Post || $game->post_type !== MEEPLE_MANAGER_GAME_POST_TYPE || $game->post_status !== 'publish') {
            wp_safe_redirect(add_query_arg('meeple_request_status', 'unavailable', $redirect));
            exit;
        }

        $category = get_post_meta($game_id, MEEPLE_MANAGER_META_GAME_CATEGORY, true);

        if (!in_array($category, array('green', 'yellow'), true)
            || get_post_meta($game_id, MEEPLE_MANAGER_META_INVENTORY_NUMBER, true) === ''
            || $this->get_active_loan_for_game($game_id)) {
            wp_safe_redirect(add_query_arg('meeple_request_status', 'unavailable', $redirect));
            exit;
        }

        $request_id = wp_insert_post(
            array(
                'post_type' => MEEPLE_MANAGER_TRANSACTION_POST_TYPE,
                'post_status' => 'private',
                'post_title' => 'Ausleihanfrage: ' . $game->post_title,
                'post_content' => '',
            ),
            true
        );

        if (is_wp_error($request_id)) {
            wp_safe_redirect(add_query_arg('meeple_request_status', 'error', $redirect));
            exit;
        }

        update_post_meta($request_id, MEEPLE_MANAGER_META_TRANSACTION_STATUS, 'pending');
        update_post_meta($request_id, MEEPLE_MANAGER_META_GAME_ID, $game_id);
        update_post_meta($request_id, MEEPLE_MANAGER_META_FIRST_NAME, $first_name);
        update_post_meta($request_id, MEEPLE_MANAGER_META_LAST_NAME, $last_name);
        update_post_meta($request_id, MEEPLE_MANAGER_META_EMAIL, $email);
        update_post_meta($request_id, MEEPLE_MANAGER_META_REQUEST_MESSAGE, $message);

        $inventory_number = get_post_meta($game_id, MEEPLE_MANAGER_META_INVENTORY_NUMBER, true);
        $settings = Meeple_Manager_Admin::get_settings();
        $placeholders = array(
            '{first_name}' => $first_name,
            '{last_name}' => $last_name,
            '{game_title}' => $game->post_title,
            '{inventory_number}' => $inventory_number,
        );

        $receipt = self::format_template($settings['request_receipt_template'], $placeholders);
        $receipt_sent = wp_mail(
            $email,
            'Eingang deiner Ausleihanfrage',
            $receipt
        );

        $admin_message = "Neue Ausleihanfrage\n\n";
        $admin_message .= 'Spiel: ' . $game->post_title . "\n";
        $admin_message .= 'Inventarnummer: ' . $inventory_number . "\n";
        $admin_message .= 'Mitglied: ' . $first_name . ' ' . $last_name . "\n";
        $admin_message .= 'E-Mail: ' . $email . "\n";

        if ($message !== '') {
            $admin_message .= "\nNachricht:\n" . $message . "\n";
        }

        $admin_sent = wp_mail(
            $settings['warden_email'],
            'Neue Ausleihanfrage: ' . $game->post_title,
            $admin_message,
            array('Reply-To: ' . $first_name . ' ' . $last_name . ' <' . $email . '>')
        );

        $status = ($receipt_sent && $admin_sent) ? 'received' : 'received_mail_warning';
        wp_safe_redirect(add_query_arg('meeple_request_status', $status, $redirect));
        exit;
    }

    public function decide_request() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html('Du hast keine Berechtigung, Ausleihanfragen zu verwalten.'));
        }

        $request_id = isset($_POST['request_id']) ? absint($_POST['request_id']) : 0;
        check_admin_referer('meeple_manager_decide_request_' . $request_id, 'meeple_manager_decide_nonce');

        $request = get_post($request_id);

        if (!$request instanceof WP_Post || $request->post_type !== MEEPLE_MANAGER_TRANSACTION_POST_TYPE || get_post_meta($request_id, MEEPLE_MANAGER_META_TRANSACTION_STATUS, true) !== 'pending') {
            $this->redirect_admin('requests', 'invalid_request');
        }

        $decision = isset($_POST['decision']) ? sanitize_key(wp_unslash($_POST['decision'])) : '';
        $game_id = (int) get_post_meta($request_id, MEEPLE_MANAGER_META_GAME_ID, true);
        $game = get_post($game_id);
        $email = sanitize_email(get_post_meta($request_id, MEEPLE_MANAGER_META_EMAIL, true));
        $first_name = (string) get_post_meta($request_id, MEEPLE_MANAGER_META_FIRST_NAME, true);
        $last_name = (string) get_post_meta($request_id, MEEPLE_MANAGER_META_LAST_NAME, true);

        if ($decision === 'approve') {
            $category = get_post_meta($game_id, MEEPLE_MANAGER_META_GAME_CATEGORY, true);
            if (!$game instanceof WP_Post || $game->post_type !== MEEPLE_MANAGER_GAME_POST_TYPE || $game->post_status !== 'publish'
                || !in_array($category, array('green', 'yellow'), true)
                || get_post_meta($game_id, MEEPLE_MANAGER_META_INVENTORY_NUMBER, true) === ''
                || $this->get_active_loan_for_game($game_id)) {
                $this->redirect_admin('requests', 'game_unavailable');
            }

            $checkout_date = isset($_POST['checkout_date']) ? sanitize_text_field(wp_unslash($_POST['checkout_date'])) : '';
            $due_date = isset($_POST['due_date']) ? sanitize_text_field(wp_unslash($_POST['due_date'])) : '';

            if (!self::is_valid_date($checkout_date) || !self::is_valid_date($due_date) || $due_date < $checkout_date) {
                $this->redirect_admin('requests', 'invalid_dates');
            }

            update_post_meta($request_id, MEEPLE_MANAGER_META_TRANSACTION_STATUS, 'active');
            update_post_meta($request_id, MEEPLE_MANAGER_META_CHECKOUT_DATE, $checkout_date);
            update_post_meta($request_id, MEEPLE_MANAGER_META_DUE_DATE, $due_date);
            delete_post_meta($request_id, MEEPLE_MANAGER_META_REQUEST_MESSAGE);

            $settings = Meeple_Manager_Admin::get_settings();
            $body = self::format_template(
                $settings['approval_template'],
                array(
                    '{first_name}' => $first_name,
                    '{last_name}' => $last_name,
                    '{game_title}' => $game->post_title,
                    '{inventory_number}' => get_post_meta($game_id, MEEPLE_MANAGER_META_INVENTORY_NUMBER, true),
                    '{checkout_date}' => $checkout_date,
                    '{due_date}' => $due_date,
                )
            );

            $sent = wp_mail($email, 'Deine Ausleihe wurde bestätigt', $body);
            $this->redirect_admin('loans', $sent ? 'loan_created' : 'loan_created_mail_warning');
        }

        if ($decision === 'reject') {
            $custom_message = isset($_POST['rejection_message'])
                ? sanitize_textarea_field(wp_unslash($_POST['rejection_message']))
                : '';
            $settings = Meeple_Manager_Admin::get_settings();
            $template = $custom_message !== '' ? $custom_message : $settings['rejection_template'];
            $body = self::format_template(
                $template,
                array(
                    '{first_name}' => $first_name,
                    '{last_name}' => $last_name,
                    '{game_title}' => $game instanceof WP_Post ? $game->post_title : '',
                    '{inventory_number}' => $game instanceof WP_Post ? get_post_meta($game_id, MEEPLE_MANAGER_META_INVENTORY_NUMBER, true) : '',
                )
            );

            if (!is_email($email) || !wp_mail($email, 'Rückmeldung zu deiner Ausleihanfrage', $body)) {
                $this->redirect_admin('requests', 'rejection_mail_failed');
            }

            wp_delete_post($request_id, true);
            $this->redirect_admin('requests', 'request_rejected');
        }

        if ($decision === 'withdrawn') {
            wp_delete_post($request_id, true);
            $this->redirect_admin('requests', 'request_withdrawn');
        }

        $this->redirect_admin('requests', 'invalid_request');
    }

    public function return_loan() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html('Du hast keine Berechtigung, Ausleihen zu verwalten.'));
        }

        $loan_id = isset($_POST['loan_id']) ? absint($_POST['loan_id']) : 0;
        check_admin_referer('meeple_manager_return_loan_' . $loan_id, 'meeple_manager_return_nonce');

        $loan = get_post($loan_id);

        if (!$loan instanceof WP_Post || $loan->post_type !== MEEPLE_MANAGER_TRANSACTION_POST_TYPE || get_post_meta($loan_id, MEEPLE_MANAGER_META_TRANSACTION_STATUS, true) !== 'active') {
            $this->redirect_admin('loans', 'invalid_loan');
        }

        $return_date = isset($_POST['return_date']) ? sanitize_text_field(wp_unslash($_POST['return_date'])) : '';
        $checkout_date = (string) get_post_meta($loan_id, MEEPLE_MANAGER_META_CHECKOUT_DATE, true);

        if (!self::is_valid_date($return_date) || (self::is_valid_date($checkout_date) && $return_date < $checkout_date)) {
            $this->redirect_admin('loans', 'invalid_return_date');
        }

        update_post_meta($loan_id, MEEPLE_MANAGER_META_RETURN_DATE, $return_date);
        update_post_meta($loan_id, MEEPLE_MANAGER_META_TRANSACTION_STATUS, 'returned');
        $this->redirect_admin('loans', 'loan_returned');
    }

    public function anonymize_returned_loans() {
        $cutoff = (new DateTimeImmutable('today', wp_timezone()))->modify('-3 months')->format('Y-m-d');
        $loans = get_posts(
            array(
                'post_type' => MEEPLE_MANAGER_TRANSACTION_POST_TYPE,
                'post_status' => 'private',
                'posts_per_page' => 100,
                'fields' => 'ids',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => MEEPLE_MANAGER_META_TRANSACTION_STATUS,
                        'value' => 'returned',
                        'compare' => '=',
                    ),
                    array(
                        'key' => MEEPLE_MANAGER_META_RETURN_DATE,
                        'value' => $cutoff,
                        'compare' => '<=',
                        'type' => 'DATE',
                    ),
                    array(
                        'key' => MEEPLE_MANAGER_META_EMAIL,
                        'compare' => 'EXISTS',
                    ),
                ),
            )
        );

        foreach ($loans as $loan_id) {
            delete_post_meta($loan_id, MEEPLE_MANAGER_META_FIRST_NAME);
            delete_post_meta($loan_id, MEEPLE_MANAGER_META_LAST_NAME);
            delete_post_meta($loan_id, MEEPLE_MANAGER_META_EMAIL);
        }
    }

    public static function get_active_loan_for_game($game_id) {
        global $wpdb;

        $loan_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT p.ID
                FROM {$wpdb->posts} AS p
                INNER JOIN {$wpdb->postmeta} AS status_meta ON status_meta.post_id = p.ID
                INNER JOIN {$wpdb->postmeta} AS game_meta ON game_meta.post_id = p.ID
                WHERE p.post_type = %s
                    AND p.post_status = 'private'
                    AND status_meta.meta_key = %s
                    AND status_meta.meta_value = 'active'
                    AND game_meta.meta_key = %s
                    AND game_meta.meta_value = %d
                LIMIT 1",
                MEEPLE_MANAGER_TRANSACTION_POST_TYPE,
                MEEPLE_MANAGER_META_TRANSACTION_STATUS,
                MEEPLE_MANAGER_META_GAME_ID,
                (int) $game_id
            )
        );

        return (int) $loan_id;
    }

    public static function get_active_loan_map() {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT game_meta.meta_value AS game_id, due_meta.meta_value AS due_date
                FROM {$wpdb->posts} AS p
                INNER JOIN {$wpdb->postmeta} AS status_meta ON status_meta.post_id = p.ID
                INNER JOIN {$wpdb->postmeta} AS game_meta ON game_meta.post_id = p.ID
                LEFT JOIN {$wpdb->postmeta} AS due_meta
                    ON due_meta.post_id = p.ID AND due_meta.meta_key = %s
                WHERE p.post_type = %s
                    AND p.post_status = 'private'
                    AND status_meta.meta_key = %s
                    AND status_meta.meta_value = 'active'
                    AND game_meta.meta_key = %s",
                MEEPLE_MANAGER_META_DUE_DATE,
                MEEPLE_MANAGER_TRANSACTION_POST_TYPE,
                MEEPLE_MANAGER_META_TRANSACTION_STATUS,
                MEEPLE_MANAGER_META_GAME_ID
            ),
            ARRAY_A
        );
        $loans = array();

        foreach ($rows as $row) {
            $game_id = isset($row['game_id']) ? (int) $row['game_id'] : 0;

            if ($game_id > 0 && !isset($loans[$game_id])) {
                $loans[$game_id] = array('due_date' => isset($row['due_date']) ? (string) $row['due_date'] : '');
            }
        }

        return $loans;
    }

    public static function is_valid_date($value) {
        if (!is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, wp_timezone());
        $errors = DateTimeImmutable::getLastErrors();

        return $date instanceof DateTimeImmutable
            && $date->format('Y-m-d') === $value
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
    }

    public static function format_template($template, $placeholders) {
        return strtr((string) $template, $placeholders);
    }

    private function get_frontend_redirect() {
        $referer = wp_get_referer();

        if (!$referer) {
            return home_url('/');
        }

        return wp_validate_redirect($referer, home_url('/'));
    }

    private function redirect_admin($page, $result) {
        $url = add_query_arg(
            array(
                'page' => 'meeple-manager-' . $page,
                'meeple_result' => $result,
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($url);
        exit;
    }
}
