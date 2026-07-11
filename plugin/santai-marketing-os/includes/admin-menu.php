<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', 'smos_admin_menu');
add_action('admin_enqueue_scripts', 'smos_admin_assets');

function smos_admin_menu()
{
    add_menu_page('Santai AI Growth OS','Santai AI Growth OS','manage_options','smos-dashboard','smos_dashboard_page','dashicons-megaphone',3);
    add_submenu_page('smos-dashboard','AI Copywriter','AI Copywriter','manage_options','smos-copywriter','smos_copywriter_page');
    add_submenu_page('smos-dashboard','Product Importer','Product Importer','manage_options','smos-importer','smos_importer_page');
    add_submenu_page('smos-dashboard','Product Health','Product Health','manage_options','smos-product-health','smos_product_health_page');
    add_submenu_page('smos-dashboard','Facebook Connector','Facebook Connector','manage_options','smos-facebook-stable','smos_facebook_stable_page');
    add_submenu_page('smos-dashboard','Settings','Settings','manage_options','smos-settings','smos_settings_page');
}

function smos_admin_assets($hook)
{
    if (strpos($hook, 'smos') === false) return;
    wp_enqueue_style('smos-admin', SMOS_URL . 'assets/css/admin.css', array(), SMOS_VERSION);
    wp_enqueue_script('smos-admin', SMOS_URL . 'assets/js/admin.js', array(), SMOS_VERSION, true);
}

function smos_dashboard_page()
{
    include SMOS_PATH . 'templates/dashboard.php';
}
