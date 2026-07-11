<?php
if (!defined('ABSPATH')) exit;

function smos_importer_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $result = null;

    if (isset($_POST['smos_run_product_import'])) {
        check_admin_referer('smos_product_import_nonce');

        $result = smos_handle_product_import();
    }

    include SMOS_PATH . 'templates/importer.php';
}

function smos_handle_product_import()
{
    if (empty($_FILES['smos_import_csv']['tmp_name'])) {
        return array(
            'success' => false,
            'message' => 'Sila pilih fail CSV untuk import.',
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => array(),
        );
    }

    $file = $_FILES['smos_import_csv'];

    if (!empty($file['error'])) {
        return array(
            'success' => false,
            'message' => 'Upload error: ' . intval($file['error']),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => array(),
        );
    }

    $update_existing = !empty($_POST['smos_update_existing']);
    $skip_empty = !empty($_POST['smos_skip_empty']);
    $dry_run = !empty($_POST['smos_dry_run']);

    $handle = fopen($file['tmp_name'], 'r');

    if (!$handle) {
        return array(
            'success' => false,
            'message' => 'Fail CSV tidak dapat dibuka.',
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => array(),
        );
    }

    $headers = fgetcsv($handle);

    if (!$headers || !is_array($headers)) {
        fclose($handle);
        return array(
            'success' => false,
            'message' => 'Header CSV tidak sah.',
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => array(),
        );
    }

    $headers = array_map('smos_clean_csv_header', $headers);

    $created = 0;
    $updated = 0;
    $skipped = 0;
    $errors = array();
    $row_number = 1;

    while (($row = fgetcsv($handle)) !== false) {
        $row_number++;

        if (count(array_filter($row, 'strlen')) === 0) {
            continue;
        }

        $data = array();

        foreach ($headers as $index => $header) {
            $data[$header] = isset($row[$index]) ? trim($row[$index]) : '';
        }

        $title = $data['post_title'] ?? '';

        if (!$title) {
            $skipped++;
            $errors[] = 'Row ' . $row_number . ': post_title kosong.';
            continue;
        }

        $existing_id = smos_find_existing_product_for_import($data);

        if ($existing_id && !$update_existing) {
            $skipped++;
            continue;
        }

        if ($dry_run) {
            if ($existing_id) {
                $updated++;
            } else {
                $created++;
            }
            continue;
        }

        if ($existing_id) {
            $post_id = $existing_id;
            wp_update_post(array(
                'ID' => $post_id,
                'post_title' => sanitize_text_field($title),
            ));
            $updated++;
        } else {
            $post_id = wp_insert_post(array(
                'post_title' => sanitize_text_field($title),
                'post_type' => 'smos_product',
                'post_status' => 'publish',
            ));

            if (is_wp_error($post_id) || !$post_id) {
                $skipped++;
                $errors[] = 'Row ' . $row_number . ': gagal cipta produk.';
                continue;
            }

            $created++;
        }

        smos_import_product_meta($post_id, $data, $skip_empty);
    }

    fclose($handle);

    return array(
        'success' => true,
        'message' => $dry_run ? 'Dry run selesai. Tiada data disimpan.' : 'Import selesai.',
        'created' => $created,
        'updated' => $updated,
        'skipped' => $skipped,
        'errors' => $errors,
    );
}

function smos_clean_csv_header($header)
{
    $header = trim($header);
    $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
    return $header;
}

function smos_find_existing_product_for_import($data)
{
    $woo_id = $data['woo_id'] ?? '';

    if ($woo_id !== '') {
        $query = new WP_Query(array(
            'post_type' => 'smos_product',
            'post_status' => 'any',
            'meta_key' => '_smos_woo_id',
            'meta_value' => sanitize_text_field($woo_id),
            'fields' => 'ids',
            'posts_per_page' => 1,
        ));

        if (!empty($query->posts)) {
            return intval($query->posts[0]);
        }
    }

    $title = $data['post_title'] ?? '';

    if ($title) {
        $existing = get_page_by_title($title, OBJECT, 'smos_product');

        if ($existing) {
            return intval($existing->ID);
        }
    }

    return 0;
}

function smos_import_product_meta($post_id, $data, $skip_empty = true)
{
    $special_map = array(
        'woo_id' => '_smos_woo_id',
        'sku' => '_smos_sku',
        'isbn' => '_smos_isbn',
    );

    foreach ($special_map as $csv_key => $meta_key) {
        if (!array_key_exists($csv_key, $data)) continue;

        $value = $data[$csv_key];

        if ($skip_empty && $value === '') continue;

        update_post_meta($post_id, $meta_key, sanitize_text_field($value));
    }

    foreach ($data as $key => $value) {
        if (strpos($key, '_smos_') !== 0) continue;

        if ($skip_empty && $value === '') continue;

        if (smos_import_field_is_url($key)) {
            update_post_meta($post_id, $key, esc_url_raw($value));
        } else {
            update_post_meta($post_id, $key, sanitize_textarea_field($value));
        }
    }
}

function smos_import_field_is_url($key)
{
    $url_fields = array(
        '_smos_product_url',
        '_smos_whatsapp_url',
        '_smos_product_image_url',
        '_smos_product_image_url_2',
        '_smos_product_image_url_3',
        '_smos_product_image_url_4',
        '_smos_product_image_url_5',
        '_smos_short_video_url_1',
        '_smos_short_video_url_2',
        '_smos_short_video_url_3',
        '_smos_short_video_url_4',
        '_smos_short_video_url_5',
    );

    return in_array($key, $url_fields, true);
}
