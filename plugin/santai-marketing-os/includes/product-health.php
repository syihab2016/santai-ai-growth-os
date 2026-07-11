<?php
if (!defined('ABSPATH')) exit;

function smos_product_health_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $products = get_posts(array(
        'post_type' => 'smos_product',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ));

    $rows = array();
    $total_score = 0;
    $complete_count = 0;
    $needs_work_count = 0;

    foreach ($products as $product) {
        $health = smos_calculate_product_health($product->ID);
        $rows[] = array(
            'id' => $product->ID,
            'title' => $product->post_title,
            'health' => $health,
        );

        $total_score += $health['score'];

        if ($health['score'] >= 85) {
            $complete_count++;
        } else {
            $needs_work_count++;
        }
    }

    $average_score = count($rows) ? round($total_score / count($rows)) : 0;

    include SMOS_PATH . 'templates/product-health.php';
}

function smos_calculate_product_health($product_id)
{
    $checks = array(
        '_smos_normal_price' => array('label' => 'Harga Asal', 'weight' => 5),
        '_smos_promo_price' => array('label' => 'Harga Promosi', 'weight' => 5),
        '_smos_product_url' => array('label' => 'Link Produk', 'weight' => 8),
        '_smos_short_desc' => array('label' => 'Deskripsi Pendek', 'weight' => 10),
        '_smos_usp' => array('label' => 'USP / Kelebihan', 'weight' => 12),
        '_smos_pain_points' => array('label' => 'Pain Points', 'weight' => 10),
        '_smos_target_audience' => array('label' => 'Target Pembeli', 'weight' => 8),
        '_smos_faq' => array('label' => 'FAQ', 'weight' => 8),
        '_smos_cta' => array('label' => 'CTA', 'weight' => 8),
        '_smos_hashtags' => array('label' => 'Hashtag', 'weight' => 6),
        '_smos_reviews' => array('label' => 'Review/Testimoni', 'weight' => 8),
    );

    $score = 0;
    $max_score = 0;
    $missing = array();
    $completed = array();

    foreach ($checks as $meta_key => $check) {
        $max_score += $check['weight'];
        $value = trim((string) get_post_meta($product_id, $meta_key, true));

        if ($value !== '') {
            $score += $check['weight'];
            $completed[] = $check['label'];
        } else {
            $missing[] = $check['label'];
        }
    }

    $image_count = smos_count_product_media($product_id, 'image');
    $video_count = smos_count_product_media($product_id, 'video');

    $max_score += 12;
    if ($image_count >= 3) {
        $score += 12;
        $completed[] = 'Gambar Produk 3+';
    } elseif ($image_count >= 1) {
        $score += 7;
        $missing[] = 'Tambah gambar produk lagi';
    } else {
        $missing[] = 'Gambar Produk';
    }

    $max_score += 8;
    if ($video_count >= 1) {
        $score += 8;
        $completed[] = 'Short Video';
    } else {
        $missing[] = 'Short Video';
    }

    $percent = $max_score ? round(($score / $max_score) * 100) : 0;

    return array(
        'score' => $percent,
        'raw_score' => $score,
        'max_score' => $max_score,
        'missing' => $missing,
        'completed' => $completed,
        'image_count' => $image_count,
        'video_count' => $video_count,
        'status' => smos_product_health_status($percent),
    );
}

function smos_count_product_media($product_id, $type)
{
    if ($type === 'image') {
        $keys = array(
            '_smos_product_image_url',
            '_smos_product_image_url_2',
            '_smos_product_image_url_3',
            '_smos_product_image_url_4',
            '_smos_product_image_url_5',
        );
    } else {
        $keys = array(
            '_smos_short_video_url_1',
            '_smos_short_video_url_2',
            '_smos_short_video_url_3',
            '_smos_short_video_url_4',
            '_smos_short_video_url_5',
        );
    }

    $count = 0;

    foreach ($keys as $key) {
        $value = trim((string) get_post_meta($product_id, $key, true));
        if ($value !== '') {
            $count++;
        }
    }

    return $count;
}

function smos_product_health_status($score)
{
    if ($score >= 85) return array('label' => 'Ready', 'class' => 'smos-status-ready');
    if ($score >= 65) return array('label' => 'Almost Ready', 'class' => 'smos-status-warning');
    return array('label' => 'Needs Work', 'class' => 'smos-status-danger');
}
