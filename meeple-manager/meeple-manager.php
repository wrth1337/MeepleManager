<?php
/**
 * Plugin Name: MeepleManager
 * Description: Vereinsverwaltung für Events und Spieleverleih mit öffentlichem Spielekatalog und geschütztem Verleihbereich.
 * Version: 4.3.6
 * Author: wrth1337
 * Text Domain: meeple-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MEEPLE_MANAGER_VERSION', '4.3.6');
define('MEEPLE_MANAGER_FILE', __FILE__);
define('MEEPLE_MANAGER_DIR', plugin_dir_path(__FILE__));
define('MEEPLE_MANAGER_URL', plugin_dir_url(__FILE__));
define('MEEPLE_MANAGER_BASENAME', plugin_basename(__FILE__));
define('MEEPLE_MANAGER_MENU_SLUG', 'meeple-manager');
define('MEEPLE_MANAGER_GAME_POST_TYPE', 'meeple_game');
define('MEEPLE_MANAGER_TRANSACTION_POST_TYPE', 'meeple_transaction');
define('MEEPLE_MANAGER_META_INVENTORY_NUMBER', '_meeple_inventory_number');
define('MEEPLE_MANAGER_META_GAME_CATEGORY', '_meeple_game_category');
define('MEEPLE_MANAGER_META_CONDITION', '_meeple_condition');
define('MEEPLE_MANAGER_META_FAVORITE', '_meeple_current_favorite');
define('MEEPLE_MANAGER_META_IMAGE_POSITION_X', '_meeple_image_position_x');
define('MEEPLE_MANAGER_META_IMAGE_POSITION_Y', '_meeple_image_position_y');
define('MEEPLE_MANAGER_META_TRANSACTION_STATUS', '_meeple_transaction_status');
define('MEEPLE_MANAGER_META_GAME_ID', '_meeple_game_id');
define('MEEPLE_MANAGER_META_FIRST_NAME', '_meeple_first_name');
define('MEEPLE_MANAGER_META_LAST_NAME', '_meeple_last_name');
define('MEEPLE_MANAGER_META_EMAIL', '_meeple_email');
define('MEEPLE_MANAGER_META_REQUEST_MESSAGE', '_meeple_request_message');
define('MEEPLE_MANAGER_META_CHECKOUT_DATE', '_meeple_checkout_date');
define('MEEPLE_MANAGER_META_DUE_DATE', '_meeple_due_date');
define('MEEPLE_MANAGER_META_RETURN_DATE', '_meeple_return_date');
define('MEEPLE_MANAGER_OPTION_SETTINGS', 'meeple_manager_settings');
define('MEEPLE_MANAGER_OPTION_GAME_STYLES', 'meeple_manager_game_styles');
define('MEEPLE_MANAGER_ANONYMIZE_HOOK', 'meeple_manager_anonymize_returned_loans');

// Keep the event subsystem's identifiers stable so existing event data and styles continue to work.
define('EVENT_BLOCKS_VERSION', MEEPLE_MANAGER_VERSION);
define('EVENT_BLOCKS_FILE', __FILE__);
define('EVENT_BLOCKS_DIR', MEEPLE_MANAGER_DIR);
define('EVENT_BLOCKS_URL', MEEPLE_MANAGER_URL);
define('EVENT_BLOCKS_BASENAME', MEEPLE_MANAGER_BASENAME);
define('EVENT_BLOCKS_POST_TYPE', 'event_block');
define('EVENT_BLOCKS_CATEGORY_TAXONOMY', 'event_block_category');
define('EVENT_BLOCKS_META_DATE', '_event_blocks_date');
define('EVENT_BLOCKS_META_TIME', '_event_blocks_time');
define('EVENT_BLOCKS_META_LOCATION', '_event_blocks_location');
define('EVENT_BLOCKS_META_LOCATION_LINK', '_event_blocks_location_link');
define('EVENT_BLOCKS_META_EXTERNAL_LINK', '_event_blocks_external_link');
define('EVENT_BLOCKS_META_IMAGE_URL', '_event_blocks_image_url');
define('EVENT_BLOCKS_META_NOTES', '_event_blocks_notes');
define('EVENT_BLOCKS_META_START_TIMESTAMP', '_event_blocks_start_timestamp');
define('EVENT_BLOCKS_META_IS_ANNOUNCEMENT', '_event_blocks_is_announcement');
define('EVENT_BLOCKS_META_ANNOUNCEMENT_MONTH', '_event_blocks_announcement_month');
define('EVENT_BLOCKS_TERM_META_COLOR', '_event_blocks_color');
define('EVENT_BLOCKS_OPTION_STYLES', 'event_blocks_styles');

require_once MEEPLE_MANAGER_DIR . 'includes/class-event-blocks-event-normalizer.php';
require_once MEEPLE_MANAGER_DIR . 'includes/class-event-blocks-event-repository.php';
require_once MEEPLE_MANAGER_DIR . 'includes/class-event-blocks-style-settings.php';
require_once MEEPLE_MANAGER_DIR . 'includes/class-event-blocks-assets.php';
require_once MEEPLE_MANAGER_DIR . 'includes/class-event-blocks-shortcode.php';
require_once MEEPLE_MANAGER_DIR . 'includes/class-event-blocks-activator.php';
require_once MEEPLE_MANAGER_DIR . 'admin/class-event-blocks-event-post-type.php';
require_once MEEPLE_MANAGER_DIR . 'admin/class-event-blocks-event-meta-boxes.php';
require_once MEEPLE_MANAGER_DIR . 'admin/class-event-blocks-event-category-taxonomy.php';
require_once MEEPLE_MANAGER_DIR . 'admin/class-event-blocks-admin-ui.php';
require_once MEEPLE_MANAGER_DIR . 'includes/class-meeple-manager-content-types.php';
require_once MEEPLE_MANAGER_DIR . 'admin/class-meeple-manager-game-meta-box.php';
require_once MEEPLE_MANAGER_DIR . 'includes/class-meeple-manager-rentals.php';
require_once MEEPLE_MANAGER_DIR . 'includes/class-meeple-manager-shortcodes.php';
require_once MEEPLE_MANAGER_DIR . 'admin/class-meeple-manager-admin.php';
require_once MEEPLE_MANAGER_DIR . 'admin/class-meeple-manager-game-style-settings.php';
require_once MEEPLE_MANAGER_DIR . 'includes/class-event-blocks-plugin.php';

register_activation_hook(MEEPLE_MANAGER_FILE, array('Event_Blocks_Activator', 'activate'));
register_deactivation_hook(MEEPLE_MANAGER_FILE, array('Event_Blocks_Activator', 'deactivate'));

$meeple_manager_plugin = new Event_Blocks_Plugin();
$meeple_manager_plugin->run();
