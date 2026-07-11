<?php
if (!defined('ABSPATH')) exit;

add_action('init', 'smos_register_history_cpt');

function smos_register_history_cpt()
{
    register_post_type('smos_history', array(
        'labels' => array(
            'name' => 'Generated Posts',
            'singular_name' => 'Generated Post',
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'smos-dashboard',
        'supports' => array('title', 'editor'),
    ));
}

function smos_save_generated_history($product_id, $platform, $template, $content)
{
    $title = get_the_title($product_id) . ' - ' . strtoupper($platform) . ' - ' . current_time('Y-m-d H:i');

    $history_id = wp_insert_post(array(
        'post_title' => $title,
        'post_type' => 'smos_history',
        'post_status' => 'publish',
        'post_content' => $content
    ));

    if ($history_id) {
        update_post_meta($history_id, '_smos_product_id', $product_id);
        update_post_meta($history_id, '_smos_platform', $platform);
        update_post_meta($history_id, '_smos_template', $template);
    }

    return $history_id;
}
