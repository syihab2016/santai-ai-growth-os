<?php
/**
 * Plugin Name: Santai AI Growth OS
 * Plugin URI: https://marketing.bukusantaiilmu.com
 * Description: AI Growth Operating System untuk Santai Ilmu Publication.
 * Version: 1.1.0-dev.2
 * Author: Syihabudin Ahmad
 */

if (!defined('ABSPATH')) exit;

define('SMOS_VERSION', '1.1.0-dev.2');
define('SMOS_PATH', plugin_dir_path(__FILE__));
define('SMOS_URL', plugin_dir_url(__FILE__));

require_once SMOS_PATH . 'includes/admin-menu.php';
require_once SMOS_PATH . 'includes/settings.php';
require_once SMOS_PATH . 'includes/products.php';
require_once SMOS_PATH . 'includes/importer.php';
require_once SMOS_PATH . 'includes/product-health.php';
require_once SMOS_PATH . 'includes/history.php';
require_once SMOS_PATH . 'includes/campaigns.php';
require_once SMOS_PATH . 'includes/openai.php';
require_once SMOS_PATH . 'includes/facebook.php';
require_once SMOS_PATH . 'includes/facebook-oauth.php';
require_once SMOS_PATH . 'includes/facebook-stable.php';
require_once SMOS_PATH . 'includes/prompt-builder.php';
require_once SMOS_PATH . 'includes/copywriter.php';

register_activation_hook(__FILE__, 'smos_activate_plugin');

function smos_activate_plugin()
{
    smos_register_product_cpt();
    smos_register_history_cpt();
    smos_register_campaign_cpt();

    // Facebook has one source of truth: Page 1.
    $page_id = get_option('smos_facebook_page_id', '');
    $page_token = get_option('smos_facebook_page_access_token', '');
    $page_name = get_option('smos_facebook_page_name', '');

    if ($page_id && $page_token) {
        update_option('smos_facebook_page_1_id', $page_id);
        update_option('smos_facebook_page_1_access_token', $page_token);
        update_option('smos_facebook_page_1_name', $page_name ?: 'Facebook Page');
    }

    flush_rewrite_rules();
}
