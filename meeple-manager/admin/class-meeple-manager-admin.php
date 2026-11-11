<?php

if (!defined('ABSPATH')) {
    exit;
}

class Meeple_Manager_Admin {
    public function register() {
        add_action('admin_menu', array($this, 'register_menu'), 5);
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'render_notices'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_post_meeple_manager_import_csv', array($this, 'import_csv'));
        add_action('admin_post_meeple_manager_export_csv', array($this, 'export_csv'));
    }

    public function register_menu() {
        add_menu_page(
            'MeepleManager',
            'MeepleManager',
            'edit_posts',
            MEEPLE_MANAGER_MENU_SLUG,
            array($this, 'render_dashboard'),
            'dashicons-games',
            25
        );

        add_submenu_page(
            MEEPLE_MANAGER_MENU_SLUG,
            'Ausleihanfragen',
            'Anfragen',
            'manage_options',
            'meeple-manager-requests',
            array($this, 'render_requests')
        );

        add_submenu_page(
            MEEPLE_MANAGER_MENU_SLUG,
            'Aktuelle Ausleihen und Historie',
            'Ausleihen',
            'manage_options',
            'meeple-manager-loans',
            array($this, 'render_loans')
        );

        add_submenu_page(
            MEEPLE_MANAGER_MENU_SLUG,
            'CSV-Import und -Export',
            'CSV-Import/-Export',
            'manage_options',
            'meeple-manager-csv',
            array($this, 'render_csv_page')
        );

        add_submenu_page(
            MEEPLE_MANAGER_MENU_SLUG,
            'MeepleManager-Einstellungen',
            'Einstellungen',
            'manage_options',
            'meeple-manager-settings',
            array($this, 'render_settings')
        );
    }

    public function register_settings() {
        register_setting(
            'meeple_manager_settings_group',
            MEEPLE_MANAGER_OPTION_SETTINGS,
            array($this, 'sanitize_settings')
        );
    }

    public function enqueue_admin_assets() {
        $screen = get_current_screen();

        if (!$screen) {
            return;
        }

        $is_meeple_screen = strpos($screen->id, 'meeple-manager') !== false
            || $screen->post_type === MEEPLE_MANAGER_GAME_POST_TYPE;

        if ($is_meeple_screen) {
            wp_enqueue_style(
                'meeple-manager-admin',
                MEEPLE_MANAGER_URL . 'assets/css/meeple-manager-admin.css',
                array(),
                MEEPLE_MANAGER_VERSION
            );
        }
    }

    public function render_dashboard() {
        if (!current_user_can('edit_posts')) {
            wp_die(esc_html('Du hast keine Berechtigung, MeepleManager zu verwalten.'));
        }

        $game_count = wp_count_posts(MEEPLE_MANAGER_GAME_POST_TYPE);
        $published_games = $game_count && isset($game_count->publish) ? (int) $game_count->publish : 0;
        ?>
        <div class="wrap meeple-manager-admin">
            <h1>MeepleManager</h1>
            <p>Verwalte hier das Spieleinventar und die Vereinsveranstaltungen.</p>
            <div class="meeple-manager-admin-cards">
                <section class="meeple-manager-admin-card">
                    <h2>Spieleinventar</h2>
                    <p><?php echo esc_html($published_games); ?> veröffentlichte Spiele</p>
                    <p><a class="button button-primary" href="<?php echo esc_url(admin_url('edit.php?post_type=' . MEEPLE_MANAGER_GAME_POST_TYPE)); ?>">Spiele verwalten</a></p>
                </section>
                <?php if (current_user_can('manage_options')) : ?>
                    <section class="meeple-manager-admin-card">
                        <h2>Ausleihverwaltung</h2>
                        <p>Prüfe neue Anfragen, bestätige Ausleihen und trage Rückgaben ein.</p>
                        <p><a class="button button-primary" href="<?php echo esc_url($this->page_url('requests')); ?>">Anfragen öffnen</a></p>
                        <p><a href="<?php echo esc_url($this->page_url('loans')); ?>">Aktuelle Ausleihen und Historie</a></p>
                    </section>
                    <section class="meeple-manager-admin-card">
                        <h2>Inventarliste</h2>
                        <p>Importiere oder exportiere die gepflegten Inventardaten.</p>
                        <p><a href="<?php echo esc_url($this->page_url('csv')); ?>">CSV-Import/-Export</a></p>
                    </section>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function render_requests() {
        $this->require_admin_capability();
        $requests = get_posts(
            array(
                'post_type' => MEEPLE_MANAGER_TRANSACTION_POST_TYPE,
                'post_status' => 'private',
                'posts_per_page' => -1,
                'orderby' => 'date',
                'order' => 'ASC',
                'meta_query' => array(
                    array(
                        'key' => MEEPLE_MANAGER_META_TRANSACTION_STATUS,
                        'value' => 'pending',
                        'compare' => '=',
                    ),
                ),
            )
        );
        $categories = Meeple_Manager_Content_Types::get_categories();
        $settings = self::get_settings();
        ?>
        <div class="wrap meeple-manager-admin">
            <h1>Ausleihanfragen</h1>
            <p>Anfragen blockieren kein Spiel. Wenn mehrere Personen dasselbe Spiel möchten, klärt der Spielewart die Reihenfolge außerhalb des Plugins und entscheidet anschließend hier.</p>
            <?php if (empty($requests)) : ?>
                <div class="notice notice-info inline"><p>Es gibt keine offenen Anfragen.</p></div>
            <?php else : ?>
                <div class="meeple-manager-admin-list">
                    <?php foreach ($requests as $request) : ?>
                        <?php
                        $game_id = (int) get_post_meta($request->ID, MEEPLE_MANAGER_META_GAME_ID, true);
                        $game = get_post($game_id);
                        $game_title = $game instanceof WP_Post ? $game->post_title : 'Spiel nicht gefunden';
                        $category_key = $game instanceof WP_Post ? get_post_meta($game_id, MEEPLE_MANAGER_META_GAME_CATEGORY, true) : '';
                        $category = isset($categories[$category_key]) ? $categories[$category_key]['short_label'] : '–';
                        $first_name = get_post_meta($request->ID, MEEPLE_MANAGER_META_FIRST_NAME, true);
                        $last_name = get_post_meta($request->ID, MEEPLE_MANAGER_META_LAST_NAME, true);
                        $email = get_post_meta($request->ID, MEEPLE_MANAGER_META_EMAIL, true);
                        $message = get_post_meta($request->ID, MEEPLE_MANAGER_META_REQUEST_MESSAGE, true);
                        ?>
                        <section class="meeple-manager-admin-card">
                            <h2><?php echo esc_html($game_title); ?> <span class="meeple-manager-admin-muted">(<?php echo esc_html($category); ?>)</span></h2>
                            <p><strong>Mitglied:</strong> <?php echo esc_html(trim($first_name . ' ' . $last_name)); ?> · <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></p>
                            <p><strong>Eingegangen:</strong> <?php echo esc_html(get_date_from_gmt($request->post_date_gmt, 'd.m.Y H:i')); ?></p>
                            <?php if ($message !== '') : ?>
                                <div class="meeple-manager-admin-message"><strong>Nachricht:</strong><br><?php echo nl2br(esc_html($message)); ?></div>
                            <?php endif; ?>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="meeple-manager-request-decision">
                                <input type="hidden" name="action" value="meeple_manager_decide_request">
                                <input type="hidden" name="request_id" value="<?php echo esc_attr($request->ID); ?>">
                                <?php wp_nonce_field('meeple_manager_decide_request_' . $request->ID, 'meeple_manager_decide_nonce'); ?>
                                <div class="meeple-manager-admin-fields">
                                    <p>
                                        <label for="meeple-checkout-<?php echo esc_attr($request->ID); ?>">Ausleihdatum</label>
                                        <input id="meeple-checkout-<?php echo esc_attr($request->ID); ?>" name="checkout_date" type="date" value="<?php echo esc_attr(wp_date('Y-m-d')); ?>">
                                    </p>
                                    <p>
                                        <label for="meeple-due-<?php echo esc_attr($request->ID); ?>">Geplantes Rückgabedatum</label>
                                        <input id="meeple-due-<?php echo esc_attr($request->ID); ?>" name="due_date" type="date">
                                    </p>
                                </div>
                                <div class="meeple-manager-request-decision__email-field">
                                    <label for="meeple-approval-<?php echo esc_attr($request->ID); ?>">Bestätigungs-E-Mail (vor dem Versand bearbeitbar)</label>
                                    <textarea id="meeple-approval-<?php echo esc_attr($request->ID); ?>" name="approval_message" rows="6" class="large-text"><?php echo esc_textarea($settings['approval_template']); ?></textarea>
                                    <p class="description">Du kannst hier Details zur Übergabe ergänzen. Platzhalter: <code>{first_name}</code>, <code>{last_name}</code>, <code>{game_title}</code>, <code>{inventory_number}</code>, <code>{checkout_date}</code>, <code>{due_date}</code>.</p>
                                </div>
                                <div class="meeple-manager-request-decision__email-field">
                                    <label for="meeple-rejection-<?php echo esc_attr($request->ID); ?>">Nachricht bei Ablehnung (vor dem Versand änderbar)</label>
                                    <textarea id="meeple-rejection-<?php echo esc_attr($request->ID); ?>" name="rejection_message" rows="4" class="large-text"><?php echo esc_textarea($settings['rejection_template']); ?></textarea>
                                </div>
                                <p class="meeple-manager-admin-actions">
                                    <button class="button button-primary" type="submit" name="decision" value="approve">Ausleihe bestätigen und E-Mail senden</button>
                                    <button class="button" type="submit" name="decision" value="reject">Ablehnen, E-Mail senden und Anfrage löschen</button>
                                    <button class="button" type="submit" name="decision" value="withdrawn" onclick="return confirm('Diese zurückgezogene Anfrage endgültig löschen?');">Zurückgezogene Anfrage löschen</button>
                                </p>
                            </form>
                        </section>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_loans() {
        $this->require_admin_capability();
        $active = $this->get_transactions('active');
        $returned = $this->get_transactions('returned');
        ?>
        <div class="wrap meeple-manager-admin">
            <h1>Ausleihen</h1>
            <h2>Aktuell ausgeliehen</h2>
            <?php if (empty($active)) : ?>
                <p>Es gibt keine aktiven Ausleihen.</p>
            <?php else : ?>
                <div class="meeple-manager-admin-table-wrap">
                    <table class="widefat striped meeple-manager-loans-table">
                        <thead><tr><th>Spiel</th><th>Mitglied</th><th>Ausgeliehen</th><th>Geplante Rückgabe</th><th>Rückgabe eintragen</th></tr></thead>
                        <tbody>
                            <?php foreach ($active as $loan) : ?>
                                <?php $this->render_loan_row($loan, true); ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <h2 class="meeple-manager-admin-section-title">Ausleihhistorie</h2>
            <?php if (empty($returned)) : ?>
                <p>Es wurden noch keine Spiele zurückgegeben.</p>
            <?php else : ?>
                <div class="meeple-manager-admin-table-wrap">
                    <table class="widefat striped meeple-manager-loans-table">
                        <thead><tr><th>Spiel</th><th>Mitglied</th><th>Ausgeliehen</th><th>Geplante Rückgabe</th><th>Tatsächlich zurück</th></tr></thead>
                        <tbody>
                            <?php foreach ($returned as $loan) : ?>
                                <?php $this->render_loan_row($loan, false); ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_csv_page() {
        $this->require_admin_capability();
        $export_url = wp_nonce_url(
            admin_url('admin-post.php?action=meeple_manager_export_csv'),
            'meeple_manager_export_csv'
        );
        ?>
        <div class="wrap meeple-manager-admin">
            <h1>CSV-Import und -Export</h1>
            <p>Die Datei enthält Inventarnummer, Titel, Kategorie und Zustand. Ausleihvorgänge und Mitgliederdaten werden nicht importiert oder exportiert. Beim Import werden Inventarnummern, die bereits vorhanden sind, übersprungen.</p>
            <section class="meeple-manager-admin-card">
                <h2>Inventar exportieren</h2>
                <p><a class="button button-primary" href="<?php echo esc_url($export_url); ?>">CSV-Datei herunterladen</a></p>
            </section>
            <section class="meeple-manager-admin-card">
                <h2>Inventar importieren</h2>
                <p>Erwartete Spalten: <code>Inventarnummer</code>, <code>Titel</code>, <code>Kategorie</code>, <code>Zustand</code> und optional <code>Favorit</code>. Inventarnummer und Titel sind Pflicht; fehlende Kategorie wird als Grün importiert.</p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="meeple_manager_import_csv">
                    <?php wp_nonce_field('meeple_manager_import_csv', 'meeple_manager_import_nonce'); ?>
                    <p><label for="meeple-manager-csv-file">CSV-Datei</label><br><input id="meeple-manager-csv-file" type="file" name="meeple_csv_file" accept=".csv,text/csv" required></p>
                    <p><button class="button button-primary" type="submit">Import starten</button></p>
                </form>
            </section>
        </div>
        <?php
    }

    public function render_settings() {
        $this->require_admin_capability();
        $settings = self::get_settings();
        ?>
        <div class="wrap meeple-manager-admin">
            <h1>MeepleManager-Einstellungen</h1>
            <form method="post" action="options.php">
                <?php settings_fields('meeple_manager_settings_group'); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="meeple-manager-warden-email">E-Mail des Spielewarts</label></th>
                            <td><input id="meeple-manager-warden-email" class="regular-text" type="email" name="<?php echo esc_attr(MEEPLE_MANAGER_OPTION_SETTINGS); ?>[warden_email]" value="<?php echo esc_attr($settings['warden_email']); ?>" required><p class="description">Diese Adresse erhält eine Nachricht, wenn eine Ausleihanfrage eingeht.</p></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="meeple-manager-receipt-template">Eingangsbestätigung</label></th>
                            <td><textarea id="meeple-manager-receipt-template" class="large-text" rows="5" name="<?php echo esc_attr(MEEPLE_MANAGER_OPTION_SETTINGS); ?>[request_receipt_template]"><?php echo esc_textarea($settings['request_receipt_template']); ?></textarea><p class="description">Platzhalter: <code>{first_name}</code>, <code>{last_name}</code>, <code>{game_title}</code>, <code>{inventory_number}</code>.</p></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="meeple-manager-approval-template">Bestätigung einer Ausleihe</label></th>
                            <td><textarea id="meeple-manager-approval-template" class="large-text" rows="5" name="<?php echo esc_attr(MEEPLE_MANAGER_OPTION_SETTINGS); ?>[approval_template]"><?php echo esc_textarea($settings['approval_template']); ?></textarea><p class="description">Diese Vorlage wird beim Bestätigen einer Anfrage vorausgefüllt und kann dort vor dem Versand ergänzt werden. Platzhalter: <code>{first_name}</code>, <code>{last_name}</code>, <code>{game_title}</code>, <code>{inventory_number}</code>, <code>{checkout_date}</code>, <code>{due_date}</code>.</p></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="meeple-manager-rejection-template">Standardtext bei Ablehnung</label></th>
                            <td><textarea id="meeple-manager-rejection-template" class="large-text" rows="5" name="<?php echo esc_attr(MEEPLE_MANAGER_OPTION_SETTINGS); ?>[rejection_template]"><?php echo esc_textarea($settings['rejection_template']); ?></textarea><p class="description">Der Spielewart kann diesen Text beim Ablehnen einer einzelnen Anfrage bearbeiten. Platzhalter: <code>{first_name}</code>, <code>{last_name}</code>, <code>{game_title}</code>, <code>{inventory_number}</code>.</p></td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button('Einstellungen speichern'); ?>
            </form>
        </div>
        <?php
    }

    public static function get_settings() {
        $defaults = array(
            'warden_email' => sanitize_email(get_option('admin_email')),
            'request_receipt_template' => "Hallo {first_name},\n\nwir haben deine Ausleihanfrage für „{game_title}“ ({inventory_number}) erhalten. Der Spielewart meldet sich bei dir.\n\nViele Grüße",
            'approval_template' => "Hallo {first_name},\n\ndeine Ausleihe für „{game_title}“ ({inventory_number}) wurde bestätigt.\nAusleihdatum: {checkout_date}\nGeplante Rückgabe: {due_date}\n\nViele Grüße",
            'rejection_template' => "Hallo {first_name},\n\nleider können wir deine Anfrage für „{game_title}“ ({inventory_number}) diesmal nicht bestätigen.\n\nViele Grüße",
        );
        $stored = get_option(MEEPLE_MANAGER_OPTION_SETTINGS, array());

        if (!is_array($stored)) {
            $stored = array();
        }

        return wp_parse_args($stored, $defaults);
    }

    public function sanitize_settings($input) {
        $defaults = self::get_settings();

        if (!is_array($input)) {
            $input = array();
        }

        $email = isset($input['warden_email']) && is_scalar($input['warden_email'])
            ? sanitize_email((string) $input['warden_email'])
            : $defaults['warden_email'];
        $settings = array(
            'warden_email' => is_email($email) ? $email : $defaults['warden_email'],
            'request_receipt_template' => isset($input['request_receipt_template']) && is_scalar($input['request_receipt_template']) ? sanitize_textarea_field((string) $input['request_receipt_template']) : $defaults['request_receipt_template'],
            'approval_template' => isset($input['approval_template']) && is_scalar($input['approval_template']) ? sanitize_textarea_field((string) $input['approval_template']) : $defaults['approval_template'],
            'rejection_template' => isset($input['rejection_template']) && is_scalar($input['rejection_template']) ? sanitize_textarea_field((string) $input['rejection_template']) : $defaults['rejection_template'],
        );

        foreach (array('request_receipt_template', 'approval_template', 'rejection_template') as $key) {
            if ($settings[$key] === '') {
                $settings[$key] = $defaults[$key];
            }
        }

        return $settings;
    }

    public function import_csv() {
        $this->require_admin_capability();
        check_admin_referer('meeple_manager_import_csv', 'meeple_manager_import_nonce');

        if (!isset($_FILES['meeple_csv_file']) || !isset($_FILES['meeple_csv_file']['tmp_name']) || $_FILES['meeple_csv_file']['error'] !== UPLOAD_ERR_OK) {
            $this->redirect_import_error('file');
        }

        $file = $_FILES['meeple_csv_file'];

        if ((int) $file['size'] > 5 * MB_IN_BYTES || strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv' || !is_uploaded_file($file['tmp_name'])) {
            $this->redirect_import_error('file');
        }

        $handle = fopen($file['tmp_name'], 'rb');

        if (!$handle) {
            $this->redirect_import_error('file');
        }

        $first_line = fgets($handle);

        if ($first_line === false) {
            fclose($handle);
            $this->redirect_import_error('headers');
        }

        $delimiter = substr_count($first_line, ';') > substr_count($first_line, ',') ? ';' : ',';
        rewind($handle);
        $header = fgetcsv($handle, 0, $delimiter, '"', '\\');

        if (!is_array($header)) {
            fclose($handle);
            $this->redirect_import_error('headers');
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $columns = array();

        foreach ($header as $index => $name) {
            $columns[$this->normalize_header($name)] = (int) $index;
        }

        $number_column = $this->find_column($columns, array('inventarnummer', 'inventorynumber', 'inventory_number', 'nummer', 'nr'));
        $title_column = $this->find_column($columns, array('titel', 'title', 'name', 'spiel'));
        $category_column = $this->find_column($columns, array('kategorie', 'category'));
        $condition_column = $this->find_column($columns, array('zustand', 'condition'));
        $favorite_column = $this->find_column($columns, array('favorit', 'aktueller_favorit', 'favorite', 'current_favorite'));

        if ($number_column === null || $title_column === null) {
            fclose($handle);
            $this->redirect_import_error('headers');
        }

        $created = 0;
        $duplicates = 0;
        $invalid = 0;

        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            if (!is_array($row) || $row === array(null)) {
                continue;
            }

            $inventory_number = isset($row[$number_column]) ? trim(sanitize_text_field($row[$number_column])) : '';
            $title = isset($row[$title_column]) ? trim(sanitize_text_field($row[$title_column])) : '';

            if ($inventory_number === '' || $title === '') {
                $invalid++;
                continue;
            }

            if ($this->inventory_number_exists($inventory_number)) {
                $duplicates++;
                continue;
            }

            $category_value = $category_column !== null && isset($row[$category_column])
                ? $this->normalize_category($row[$category_column])
                : 'green';
            $condition = $condition_column !== null && isset($row[$condition_column])
                ? sanitize_textarea_field($row[$condition_column])
                : '';
            $game_id = wp_insert_post(
                array(
                    'post_type' => MEEPLE_MANAGER_GAME_POST_TYPE,
                    'post_status' => 'publish',
                    'post_title' => $title,
                ),
                true
            );

            if (is_wp_error($game_id)) {
                $invalid++;
                continue;
            }

            update_post_meta($game_id, MEEPLE_MANAGER_META_INVENTORY_NUMBER, $inventory_number);
            update_post_meta($game_id, MEEPLE_MANAGER_META_GAME_CATEGORY, $category_value);

            if ($condition !== '') {
                update_post_meta($game_id, MEEPLE_MANAGER_META_CONDITION, $condition);
            }

            if ($favorite_column !== null && isset($row[$favorite_column]) && $this->is_favorite_csv_value($row[$favorite_column])) {
                update_post_meta($game_id, MEEPLE_MANAGER_META_FAVORITE, '1');
            }

            $created++;
        }

        fclose($handle);
        set_transient(
            'meeple_manager_csv_result_' . get_current_user_id(),
            array('created' => $created, 'duplicates' => $duplicates, 'invalid' => $invalid),
            MINUTE_IN_SECONDS
        );

        wp_safe_redirect(add_query_arg(array('page' => 'meeple-manager-csv', 'meeple_result' => 'import_done'), admin_url('admin.php')));
        exit;
    }

    public function export_csv() {
        $this->require_admin_capability();
        check_admin_referer('meeple_manager_export_csv');
        $games = get_posts(
            array(
                'post_type' => MEEPLE_MANAGER_GAME_POST_TYPE,
                'post_status' => array('publish', 'draft', 'pending', 'private'),
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
            )
        );

        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="meeplemanager-spiele-' . wp_date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'wb');
        fputcsv($output, array('Inventarnummer', 'Titel', 'Kategorie', 'Zustand', 'Favorit'), ',', '"', '\\');
        $categories = Meeple_Manager_Content_Types::get_categories();

        foreach ($games as $game) {
            $category_key = get_post_meta($game->ID, MEEPLE_MANAGER_META_GAME_CATEGORY, true);
            $category_label = isset($categories[$category_key]) ? $categories[$category_key]['short_label'] : 'Grün';
            fputcsv(
                $output,
                array(
                    get_post_meta($game->ID, MEEPLE_MANAGER_META_INVENTORY_NUMBER, true),
                    $game->post_title,
                    $category_label,
                    get_post_meta($game->ID, MEEPLE_MANAGER_META_CONDITION, true),
                    get_post_meta($game->ID, MEEPLE_MANAGER_META_FAVORITE, true) === '1' ? 'Ja' : 'Nein',
                ),
                ',',
                '"',
                '\\'
            );
        }

        fclose($output);
        exit;
    }

    public function render_notices() {
        if (!isset($_GET['meeple_result'])) {
            return;
        }

        $result = sanitize_key(wp_unslash($_GET['meeple_result']));
        $messages = array(
            'invalid_request' => array('error', 'Die Anfrage wurde bereits bearbeitet oder ist nicht mehr verfügbar.'),
            'game_unavailable' => array('error', 'Das Spiel ist inzwischen ausgeliehen oder nicht mehr ausleihbar. Die Anfrage bleibt offen.'),
            'invalid_dates' => array('error', 'Bitte gib ein gültiges Ausleih- und Rückgabedatum ein. Das Rückgabedatum darf nicht vor dem Ausleihdatum liegen.'),
            'loan_created' => array('success', 'Die Ausleihe wurde angelegt und die Bestätigungs-E-Mail wurde an das Mitglied übergeben.'),
            'loan_created_mail_warning' => array('warning', 'Die Ausleihe wurde angelegt, aber WordPress konnte die Bestätigungs-E-Mail nicht zum Versand übergeben.'),
            'request_rejected' => array('success', 'Die Ablehnungs-E-Mail wurde übergeben und die Anfrage gelöscht.'),
            'request_withdrawn' => array('success', 'Die zurückgezogene Anfrage wurde gelöscht.'),
            'rejection_mail_failed' => array('error', 'Die Ablehnungs-E-Mail konnte nicht übergeben werden. Die Anfrage blieb offen.'),
            'invalid_loan' => array('error', 'Diese Ausleihe ist nicht mehr aktiv.'),
            'invalid_return_date' => array('error', 'Bitte gib ein gültiges Rückgabedatum ein.'),
            'loan_returned' => array('success', 'Die Rückgabe wurde eingetragen.'),
            'csv_file' => array('error', 'Die CSV-Datei konnte nicht gelesen werden. Erlaubt sind CSV-Dateien bis 5 MB.'),
            'csv_headers' => array('error', 'Die CSV-Datei braucht Spalten für Inventarnummer und Titel.'),
        );

        if ($result === 'import_done') {
            $key = 'meeple_manager_csv_result_' . get_current_user_id();
            $counts = get_transient($key);
            delete_transient($key);

            if (is_array($counts)) {
                $message = sprintf(
                    'Import abgeschlossen: %1$d Spiele angelegt, %2$d bestehende Inventarnummern übersprungen, %3$d ungültige Zeilen ausgelassen.',
                    (int) $counts['created'],
                    (int) $counts['duplicates'],
                    (int) $counts['invalid']
                );
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
            }

            return;
        }

        if (!isset($messages[$result])) {
            return;
        }

        echo '<div class="notice notice-' . esc_attr($messages[$result][0]) . ' is-dismissible"><p>' . esc_html($messages[$result][1]) . '</p></div>';
    }

    private function render_loan_row($loan, $active) {
        $game_id = (int) get_post_meta($loan->ID, MEEPLE_MANAGER_META_GAME_ID, true);
        $game = get_post($game_id);
        $title = $game instanceof WP_Post ? $game->post_title : 'Spiel nicht gefunden';
        $inventory_number = $game instanceof WP_Post ? get_post_meta($game_id, MEEPLE_MANAGER_META_INVENTORY_NUMBER, true) : '';
        $first_name = get_post_meta($loan->ID, MEEPLE_MANAGER_META_FIRST_NAME, true);
        $last_name = get_post_meta($loan->ID, MEEPLE_MANAGER_META_LAST_NAME, true);
        $email = get_post_meta($loan->ID, MEEPLE_MANAGER_META_EMAIL, true);
        $checkout_date = get_post_meta($loan->ID, MEEPLE_MANAGER_META_CHECKOUT_DATE, true);
        $due_date = get_post_meta($loan->ID, MEEPLE_MANAGER_META_DUE_DATE, true);
        $return_date = get_post_meta($loan->ID, MEEPLE_MANAGER_META_RETURN_DATE, true);
        ?>
        <tr>
            <td><?php echo esc_html($title); ?><br><span class="meeple-manager-admin-muted"><?php echo esc_html($inventory_number); ?></span></td>
            <td>
                <?php if ($first_name !== '' || $last_name !== '') : ?>
                    <?php echo esc_html(trim($first_name . ' ' . $last_name)); ?><br>
                    <?php if (is_email($email)) : ?><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a><?php endif; ?>
                <?php else : ?>
                    Anonymisiert
                <?php endif; ?>
            </td>
            <td><?php echo esc_html($this->format_date($checkout_date)); ?></td>
            <td><?php echo esc_html($this->format_date($due_date)); ?></td>
            <td>
                <?php if ($active) : ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="meeple-manager-return-form">
                        <input type="hidden" name="action" value="meeple_manager_return_loan">
                        <input type="hidden" name="loan_id" value="<?php echo esc_attr($loan->ID); ?>">
                        <?php wp_nonce_field('meeple_manager_return_loan_' . $loan->ID, 'meeple_manager_return_nonce'); ?>
                        <label class="screen-reader-text" for="meeple-return-date-<?php echo esc_attr($loan->ID); ?>">Tatsächliches Rückgabedatum</label>
                        <input id="meeple-return-date-<?php echo esc_attr($loan->ID); ?>" type="date" name="return_date" required>
                        <button class="button" type="submit">Ist zurück</button>
                    </form>
                <?php else : ?>
                    <?php echo esc_html($this->format_date($return_date)); ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

    private function get_transactions($status) {
        return get_posts(
            array(
                'post_type' => MEEPLE_MANAGER_TRANSACTION_POST_TYPE,
                'post_status' => 'private',
                'posts_per_page' => -1,
                'orderby' => 'meta_value',
                'meta_key' => MEEPLE_MANAGER_META_DUE_DATE,
                'order' => 'ASC',
                'meta_query' => array(
                    array(
                        'key' => MEEPLE_MANAGER_META_TRANSACTION_STATUS,
                        'value' => $status,
                        'compare' => '=',
                    ),
                ),
            )
        );
    }

    private function format_date($date) {
        if (!Meeple_Manager_Rentals::is_valid_date($date)) {
            return '–';
        }

        $timestamp = DateTimeImmutable::createFromFormat('!Y-m-d', $date, wp_timezone())->getTimestamp();
        return wp_date('d.m.Y', $timestamp);
    }

    private function normalize_header($value) {
        $value = strtolower(trim((string) $value));
        $value = str_replace(array(' ', '-', '.', '/', '\\'), '_', $value);
        return preg_replace('/[^a-z0-9_]/', '', remove_accents($value));
    }

    private function find_column($columns, $accepted) {
        foreach ($accepted as $name) {
            if (array_key_exists($name, $columns)) {
                return $columns[$name];
            }
        }

        return null;
    }

    private function normalize_category($value) {
        $value = strtolower(remove_accents(trim((string) $value)));
        $value = trim(preg_replace('/[^a-z0-9]+/', '_', $value), '_');
        $map = array(
            'green' => 'green',
            'gruen' => 'green',
            'grun' => 'green',
            'grun_normal_ausleihbar' => 'green',
            'yellow' => 'yellow',
            'gelb' => 'yellow',
            'gelb_nur_nach_freigabe' => 'yellow',
            'yellow_nur_nach_freigabe' => 'yellow',
            'red' => 'red',
            'rot' => 'red',
            'rot_nicht_ausleihbar' => 'red',
            'red_nicht_ausleihbar' => 'red',
        );

        return isset($map[$value]) ? $map[$value] : 'green';
    }

    private function is_favorite_csv_value($value) {
        $value = strtolower(remove_accents(trim((string) $value)));
        return in_array($value, array('1', 'true', 'yes', 'ja', 'j', 'x', 'favorite', 'favorit'), true);
    }

    private function inventory_number_exists($inventory_number) {
        $matches = get_posts(
            array(
                'post_type' => MEEPLE_MANAGER_GAME_POST_TYPE,
                'post_status' => array('publish', 'draft', 'pending', 'private'),
                'posts_per_page' => 1,
                'fields' => 'ids',
                'meta_query' => array(
                    array(
                        'key' => MEEPLE_MANAGER_META_INVENTORY_NUMBER,
                        'value' => $inventory_number,
                        'compare' => '=',
                    ),
                ),
            )
        );

        return !empty($matches);
    }

    private function redirect_import_error($error) {
        wp_safe_redirect(add_query_arg(array('page' => 'meeple-manager-csv', 'meeple_result' => 'csv_' . $error), admin_url('admin.php')));
        exit;
    }

    private function page_url($page) {
        return add_query_arg('page', 'meeple-manager-' . $page, admin_url('admin.php'));
    }

    private function require_admin_capability() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html('Du hast keine Berechtigung, diese Seite zu verwalten.'));
        }
    }
}
