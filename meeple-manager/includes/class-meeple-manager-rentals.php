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
        $page_id = absint($this->posted_value('meeple_lending_page_id'));
        $page = $page_id > 0 ? get_post($page_id) : null;

        if (!$page instanceof WP_Post
            || ($page->post_status !== 'publish' && !current_user_can('read_post', $page_id))) {
            wp_die(esc_html('Die Formularseite ist nicht verfügbar. Bitte öffne die Verleihseite erneut.'), '', array('response' => 403));
        }

        check_admin_referer('meeple_manager_submit_request_' . $page_id, 'meeple_manager_request_nonce');

        $game_id = absint($this->posted_value('meeple_game_id'));
        $first_name = sanitize_text_field($this->posted_value('meeple_first_name'));
        $last_name = sanitize_text_field($this->posted_value('meeple_last_name'));
        $email = sanitize_email($this->posted_value('meeple_email'));
        $message = sanitize_textarea_field($this->posted_value('meeple_message'));
        $redirect = get_permalink($page);

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
            wp_slash(array(
                'post_type' => MEEPLE_MANAGER_TRANSACTION_POST_TYPE,
                'post_status' => 'private',
                'post_title' => 'Ausleihanfrage: ' . $game->post_title,
                'post_content' => '',
            )),
            true
        );

        if (is_wp_error($request_id)) {
            wp_safe_redirect(add_query_arg('meeple_request_status', 'error', $redirect));
            exit;
        }

        update_post_meta($request_id, MEEPLE_MANAGER_META_TRANSACTION_STATUS, 'pending');
        update_post_meta($request_id, MEEPLE_MANAGER_META_GAME_ID, $game_id);
        update_post_meta($request_id, MEEPLE_MANAGER_META_FIRST_NAME, wp_slash($first_name));
        update_post_meta($request_id, MEEPLE_MANAGER_META_LAST_NAME, wp_slash($last_name));
        update_post_meta($request_id, MEEPLE_MANAGER_META_EMAIL, wp_slash($email));
        update_post_meta($request_id, MEEPLE_MANAGER_META_REQUEST_MESSAGE, wp_slash($message));

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

        $request_id = absint($this->posted_value('request_id'));
        check_admin_referer('meeple_manager_decide_request_' . $request_id, 'meeple_manager_decide_nonce');

        $request = get_post($request_id);

        if (!$request instanceof WP_Post || $request->post_type !== MEEPLE_MANAGER_TRANSACTION_POST_TYPE || get_post_meta($request_id, MEEPLE_MANAGER_META_TRANSACTION_STATUS, true) !== 'pending') {
            $this->redirect_admin('requests', 'invalid_request');
        }

        $decision = sanitize_key($this->posted_value('decision'));
        $game_id = (int) get_post_meta($request_id, MEEPLE_MANAGER_META_GAME_ID, true);

        // Serialize decisions for the same game, including concurrent requests and double clicks.
        if (!$this->lock_game_decision($game_id > 0 ? 'game_' . $game_id : 'request_' . $request_id)) {
            $this->redirect_admin('requests', 'request_busy');
        }
        wp_cache_delete($request_id, 'post_meta');
        if (get_post_meta($request_id, MEEPLE_MANAGER_META_TRANSACTION_STATUS, true) !== 'pending') {
            $this->redirect_admin('requests', 'invalid_request');
        }

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

            $checkout_date = sanitize_text_field($this->posted_value('checkout_date'));
            $due_date = sanitize_text_field($this->posted_value('due_date'));

            if (!self::is_valid_date($checkout_date) || !self::is_valid_date($due_date) || $due_date < $checkout_date) {
                $this->redirect_admin('requests', 'invalid_dates');
            }

            update_post_meta($request_id, MEEPLE_MANAGER_META_TRANSACTION_STATUS, 'active');
            update_post_meta($request_id, MEEPLE_MANAGER_META_CHECKOUT_DATE, $checkout_date);
            update_post_meta($request_id, MEEPLE_MANAGER_META_DUE_DATE, $due_date);
            delete_post_meta($request_id, MEEPLE_MANAGER_META_REQUEST_MESSAGE);

            $settings = Meeple_Manager_Admin::get_settings();
            $custom_message = sanitize_textarea_field($this->posted_value('approval_message'));
            $template = $custom_message !== '' ? $custom_message : $settings['approval_template'];
            $body = self::format_template(
                $template,
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
            $custom_message = sanitize_textarea_field($this->posted_value('rejection_message'));
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

        if ($decision === 'delete') {
            wp_delete_post($request_id, true);
            $this->redirect_admin('requests', 'request_deleted');
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

        $loan_id = absint($this->posted_value('loan_id'));
        check_admin_referer('meeple_manager_return_loan_' . $loan_id, 'meeple_manager_return_nonce');

        $loan = get_post($loan_id);

        if (!$loan instanceof WP_Post || $loan->post_type !== MEEPLE_MANAGER_TRANSACTION_POST_TYPE || get_post_meta($loan_id, MEEPLE_MANAGER_META_TRANSACTION_STATUS, true) !== 'active') {
            $this->redirect_admin('loans', 'invalid_loan');
        }

        $return_date = sanitize_text_field($this->posted_value('return_date'));
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

    private function posted_value($key) {
        return isset($_POST[$key]) && is_scalar($_POST[$key]) ? (string) wp_unslash($_POST[$key]) : '';
    }

    private function lock_game_decision($key) {
        global $wpdb;

        $name = 'meeple_manager_decision_lock_' . $key;
        $now = time();
        $token = ($now + 5 * MINUTE_IN_SECONDS) . ':' . wp_generate_uuid4();
        // Use the unique option name directly; cached option APIs cannot provide an atomic lock.
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name = %s AND CAST(SUBSTRING_INDEX(option_value, ':', 1) AS UNSIGNED) < %d",
            $name,
            $now
        ));
        $acquired = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')",
            $name,
            $token
        ));

        if ($acquired !== 1) {
            return false;
        }

        // Redirect handlers exit; shutdown also releases the lock on those paths and on PHP errors.
        register_shutdown_function(function () use ($name, $token) {
            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",
                $name,
                $token
            ));
        });
        return true;
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
