<?php
/**
 * Plugin Name: Event Blocks
 * Description: Verwaltet Events als WordPress-Inhaltstyp und zeigt sie als Event-Kacheln per Shortcode an.
 * Version: 3.0.0
 * Author: Verein
 */

if (!defined('ABSPATH')) {
    exit;
}

define('EVENT_BLOCKS_VERSION', '3.0.0');
define('EVENT_BLOCKS_FILE', __FILE__);
define('EVENT_BLOCKS_DIR', plugin_dir_path(__FILE__));
define('EVENT_BLOCKS_URL', plugin_dir_url(__FILE__));
define('EVENT_BLOCKS_BASENAME', plugin_basename(__FILE__));
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
define('EVENT_BLOCKS_TERM_META_COLOR', '_event_blocks_color');

require_once EVENT_BLOCKS_DIR . 'includes/class-event-blocks-event-normalizer.php';
require_once EVENT_BLOCKS_DIR . 'includes/class-event-blocks-event-repository.php';
require_once EVENT_BLOCKS_DIR . 'includes/class-event-blocks-assets.php';
require_once EVENT_BLOCKS_DIR . 'includes/class-event-blocks-shortcode.php';
require_once EVENT_BLOCKS_DIR . 'includes/class-event-blocks-activator.php';
require_once EVENT_BLOCKS_DIR . 'admin/class-event-blocks-event-post-type.php';
require_once EVENT_BLOCKS_DIR . 'admin/class-event-blocks-event-meta-boxes.php';
require_once EVENT_BLOCKS_DIR . 'admin/class-event-blocks-event-category-taxonomy.php';
require_once EVENT_BLOCKS_DIR . 'admin/class-event-blocks-admin-ui.php';
require_once EVENT_BLOCKS_DIR . 'includes/class-event-blocks-plugin.php';

register_activation_hook(EVENT_BLOCKS_FILE, array('Event_Blocks_Activator', 'activate'));

$event_blocks_plugin = new Event_Blocks_Plugin();
$event_blocks_plugin->run();
