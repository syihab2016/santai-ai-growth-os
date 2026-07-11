<?php
if (!defined('ABSPATH')) exit;

define('SMOS_FACEBOOK_GRAPH_VERSION', 'v25.0');

/**
 * Return connected Facebook Pages in the format consumed by Campaigns.
 * New storage is smos_facebook_connected_pages. Legacy single-page settings
 * remain as a fallback so the baseline can be upgraded safely.
 */
function smos_facebook_get_pages()
{
    $stored = get_option('smos_facebook_connected_pages', array());
    $pages = array();

    if (is_array($stored)) {
        foreach ($stored as $page) {
            $page_id = trim((string) ($page['page_id'] ?? ''));
            $name = trim((string) ($page['name'] ?? ''));
            $token = trim((string) ($page['token'] ?? ''));

            if (!$page_id || !$token) continue;

            $key = 'fb_' . preg_replace('/[^0-9A-Za-z_-]/', '', $page_id);
            $pages[$key] = array(
                'label' => $name ?: ('Facebook Page ' . $page_id),
                'page_id' => $page_id,
                'token' => $token,
            );
        }
    }

    if (!empty($pages)) return $pages;

    // Legacy v1.0.4 fallback.
    $page_id = trim((string) get_option('smos_facebook_page_id', ''));
    $token = trim((string) get_option('smos_facebook_page_access_token', ''));
    $name = trim((string) get_option('smos_facebook_page_name', ''));

    if ($page_id && $token) {
        $pages['page_1'] = array(
            'label' => $name ?: 'Facebook Page',
            'page_id' => $page_id,
            'token' => $token,
        );
    }

    return $pages;
}

function smos_facebook_get_page_config($page_key = '')
{
    $pages = smos_facebook_get_pages();

    if ($page_key && isset($pages[$page_key])) return $pages[$page_key];
    if (isset($pages['page_1'])) return $pages['page_1'];
    if (!empty($pages)) return reset($pages);

    return null;
}

function smos_facebook_graph_url($path)
{
    return 'https://graph.facebook.com/' . SMOS_FACEBOOK_GRAPH_VERSION . '/' . ltrim($path, '/');
}

function smos_facebook_api_get($path, $token, $query = array())
{
    $query['access_token'] = $token;
    $url = add_query_arg($query, smos_facebook_graph_url($path));

    $response = wp_remote_get($url, array('timeout' => 45));
    return smos_facebook_parse_response($response, 'GET', $path);
}

function smos_facebook_api_post($path, $token, $body = array(), $timeout = 60)
{
    $body['access_token'] = $token;

    $response = wp_remote_post(smos_facebook_graph_url($path), array(
        'timeout' => $timeout,
        'body' => $body,
    ));

    return smos_facebook_parse_response($response, 'POST', $path);
}

function smos_facebook_parse_response($response, $method = '', $path = '')
{
    if (is_wp_error($response)) {
        smos_facebook_log($method, $path, 0, $response->get_error_message());
        return $response;
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $raw = wp_remote_retrieve_body($response);
    $body = json_decode($raw, true);

    if ($code < 200 || $code >= 300) {
        $message = $body['error']['message'] ?? ('Facebook API error HTTP ' . $code);
        $error_code = $body['error']['code'] ?? '';
        $error_subcode = $body['error']['error_subcode'] ?? '';

        $full = $message;
        if ($error_code !== '') $full .= ' [code ' . $error_code . ']';
        if ($error_subcode !== '') $full .= ' [subcode ' . $error_subcode . ']';

        smos_facebook_log($method, $path, $code, $full);
        return new WP_Error('smos_facebook_api_error', $full, $body);
    }

    smos_facebook_log($method, $path, $code, 'OK');
    return is_array($body) ? $body : array();
}

function smos_facebook_log($method, $path, $code, $message)
{
    $logs = get_option('smos_facebook_logs', array());

    array_unshift($logs, array(
        'time' => current_time('mysql'),
        'method' => sanitize_text_field($method),
        'path' => sanitize_text_field($path),
        'code' => intval($code),
        'message' => sanitize_text_field($message),
    ));

    update_option('smos_facebook_logs', array_slice($logs, 0, 30), false);
}

function smos_facebook_validate_schedule_time($scheduled_timestamp)
{
    if (!$scheduled_timestamp) return true;

    $now = time();

    if ($scheduled_timestamp < ($now + 600)) {
        return new WP_Error('fb_schedule_too_soon', 'Masa schedule mesti sekurang-kurangnya 10 minit dari sekarang.');
    }

    if ($scheduled_timestamp > ($now + (30 * DAY_IN_SECONDS))) {
        return new WP_Error('fb_schedule_too_late', 'Masa schedule tidak boleh lebih 30 hari dari sekarang.');
    }

    return true;
}

function smos_facebook_schedule_fields($scheduled_timestamp = 0)
{
    if (!$scheduled_timestamp) return array();

    return array(
        'published' => 'false',
        'scheduled_publish_time' => (string) intval($scheduled_timestamp),
        'unpublished_content_type' => 'SCHEDULED',
    );
}

function smos_facebook_post_to_page($message, $page_key = '', $scheduled_timestamp = 0)
{
    $validation = smos_facebook_validate_schedule_time($scheduled_timestamp);
    if (is_wp_error($validation)) return $validation;

    $config = smos_facebook_get_page_config($page_key);
    if (!$config) return new WP_Error('fb_missing_settings', 'Facebook Page belum disimpan.');

    $body = array_merge(array('message' => $message), smos_facebook_schedule_fields($scheduled_timestamp));

    return smos_facebook_api_post(
        rawurlencode($config['page_id']) . '/feed',
        $config['token'],
        $body
    );
}

function smos_facebook_post_photo_to_page($message, $image_url, $page_key = '', $scheduled_timestamp = 0)
{
    $validation = smos_facebook_validate_schedule_time($scheduled_timestamp);
    if (is_wp_error($validation)) return $validation;

    $config = smos_facebook_get_page_config($page_key);
    if (!$config) return new WP_Error('fb_missing_settings', 'Facebook Page belum disimpan.');
    if (!$image_url) return new WP_Error('fb_missing_image', 'Image URL belum disediakan.');

    $body = array_merge(array(
        'caption' => $message,
        'url' => $image_url,
    ), smos_facebook_schedule_fields($scheduled_timestamp));

    return smos_facebook_api_post(
        rawurlencode($config['page_id']) . '/photos',
        $config['token'],
        $body
    );
}

function smos_facebook_post_video_to_page($message, $video_url, $page_key = '', $scheduled_timestamp = 0)
{
    $validation = smos_facebook_validate_schedule_time($scheduled_timestamp);
    if (is_wp_error($validation)) return $validation;

    $config = smos_facebook_get_page_config($page_key);
    if (!$config) return new WP_Error('fb_missing_settings', 'Facebook Page belum disimpan.');
    if (!$video_url) return new WP_Error('fb_missing_video', 'Short Video URL belum disediakan.');

    $body = array_merge(array(
        'description' => $message,
        'file_url' => $video_url,
    ), smos_facebook_schedule_fields($scheduled_timestamp));

    return smos_facebook_api_post(
        rawurlencode($config['page_id']) . '/videos',
        $config['token'],
        $body,
        120
    );
}
