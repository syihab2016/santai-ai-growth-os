<?php
if (!defined('ABSPATH')) exit;

add_action('admin_post_smos_facebook_test_page', 'smos_facebook_test_page');
add_action('admin_post_smos_facebook_remove_page', 'smos_facebook_remove_page');
add_action('admin_post_smos_facebook_clear_logs', 'smos_facebook_clear_logs');

function smos_facebook_discovery_transient_key()
{
    return 'smos_fb_discovered_' . get_current_user_id();
}

function smos_facebook_stable_page()
{
    if (!current_user_can('manage_options')) wp_die('Unauthorized');

    $notice = '';
    $error = '';

    if (isset($_POST['smos_discover_facebook_pages'])) {
        check_admin_referer('smos_facebook_discovery_nonce');

        $submitted_token = trim((string) ($_POST['smos_facebook_user_access_token'] ?? ''));
        $stored_token = trim((string) get_option('smos_facebook_user_access_token', ''));
        $token = $submitted_token ?: $stored_token;

        if (!$token) {
            $error = 'Masukkan User Access Token terlebih dahulu.';
        } else {
            $result = smos_facebook_discover_pages($token);

            if (is_wp_error($result)) {
                $error = $result->get_error_message();
            } else {
                update_option('smos_facebook_user_access_token', $token, false);
                set_transient(smos_facebook_discovery_transient_key(), $result, 30 * MINUTE_IN_SECONDS);
                $notice = count($result) . ' Facebook Page berjaya ditemui. Pilih Page dan klik Save Selected Pages.';
            }
        }
    }

    if (isset($_POST['smos_save_selected_facebook_pages'])) {
        check_admin_referer('smos_facebook_discovery_nonce');

        $discovered = get_transient(smos_facebook_discovery_transient_key());
        $selected_ids = array_map('sanitize_text_field', (array) ($_POST['smos_selected_page_ids'] ?? array()));

        if (!is_array($discovered) || empty($discovered)) {
            $error = 'Sesi discovery sudah tamat. Klik Discover My Pages semula.';
        } elseif (empty($selected_ids)) {
            $error = 'Pilih sekurang-kurangnya satu Facebook Page.';
        } else {
            $connected = array();

            foreach ($discovered as $page) {
                $page_id = (string) ($page['page_id'] ?? '');
                if (!$page_id || !in_array($page_id, $selected_ids, true)) continue;

                $connected[] = array(
                    'page_id' => $page_id,
                    'name' => sanitize_text_field($page['name'] ?? ''),
                    'token' => sanitize_text_field($page['token'] ?? ''),
                    'status' => 'connected',
                    'last_test' => current_time('mysql'),
                    'last_error' => '',
                );
            }

            if (empty($connected)) {
                $error = 'Page yang dipilih tidak sah. Jalankan discovery semula.';
            } else {
                update_option('smos_facebook_connected_pages', $connected, false);

                // Maintain v1.0.4 compatibility using the first selected Page.
                $first = reset($connected);
                update_option('smos_facebook_page_name', $first['name']);
                update_option('smos_facebook_page_id', $first['page_id']);
                update_option('smos_facebook_page_access_token', $first['token']);
                update_option('smos_facebook_page_1_name', $first['name']);
                update_option('smos_facebook_page_1_id', $first['page_id']);
                update_option('smos_facebook_page_1_access_token', $first['token']);

                $notice = count($connected) . ' Facebook Page telah disimpan dan tersedia dalam Campaign.';
            }
        }
    }

    $discovered_pages = get_transient(smos_facebook_discovery_transient_key());
    if (!is_array($discovered_pages)) $discovered_pages = array();

    $connected_pages = get_option('smos_facebook_connected_pages', array());
    if (!is_array($connected_pages)) $connected_pages = array();

    $user_token_exists = (bool) get_option('smos_facebook_user_access_token', '');
    $logs = get_option('smos_facebook_logs', array());

    include SMOS_PATH . 'templates/facebook-stable.php';
}

function smos_facebook_discover_pages($user_token)
{
    $identity = smos_facebook_api_get('me', $user_token, array('fields' => 'id,name'));
    if (is_wp_error($identity)) return $identity;

    $accounts = smos_facebook_api_get('me/accounts', $user_token, array(
        'fields' => 'id,name,access_token,tasks',
        'limit' => 100,
    ));

    if (is_wp_error($accounts)) return $accounts;

    $pages = array();

    foreach (($accounts['data'] ?? array()) as $account) {
        $page_id = sanitize_text_field($account['id'] ?? '');
        $name = sanitize_text_field($account['name'] ?? '');
        $token = sanitize_text_field($account['access_token'] ?? '');

        if (!$page_id || !$token) continue;

        $pages[] = array(
            'page_id' => $page_id,
            'name' => $name ?: ('Facebook Page ' . $page_id),
            'token' => $token,
            'tasks' => array_map('sanitize_text_field', (array) ($account['tasks'] ?? array())),
        );
    }

    if (empty($pages)) {
        return new WP_Error(
            'smos_no_facebook_pages',
            'Tiada Facebook Page ditemui. Pastikan token mempunyai pages_show_list dan akaun anda mempunyai akses kepada Page.'
        );
    }

    return $pages;
}

function smos_facebook_find_connected_page($page_id)
{
    $pages = get_option('smos_facebook_connected_pages', array());
    if (!is_array($pages)) return null;

    foreach ($pages as $index => $page) {
        if ((string) ($page['page_id'] ?? '') === (string) $page_id) {
            return array('index' => $index, 'page' => $page);
        }
    }

    return null;
}

function smos_facebook_test_page()
{
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_admin_referer('smos_facebook_test_page');

    $page_id = sanitize_text_field($_GET['page_id'] ?? '');
    $found = smos_facebook_find_connected_page($page_id);

    if (!$found) {
        smos_facebook_redirect_with_message('error', 'Facebook Page tidak ditemui dalam senarai tersimpan.');
    }

    $page = $found['page'];
    $identity = smos_facebook_api_get('me', $page['token'], array('fields' => 'id,name'));

    if (is_wp_error($identity)) {
        smos_facebook_update_page_health($found['index'], 'error', $identity->get_error_message());
        smos_facebook_redirect_with_message('error', $identity->get_error_message());
    }

    if ((string) ($identity['id'] ?? '') !== (string) $page_id) {
        $message = 'Page Access Token tidak sepadan dengan Page ID.';
        smos_facebook_update_page_health($found['index'], 'error', $message);
        smos_facebook_redirect_with_message('error', $message);
    }

    smos_facebook_update_page_health($found['index'], 'connected', '');
    smos_facebook_redirect_with_message('tested', 'Facebook Page berjaya diuji.');
}

function smos_facebook_update_page_health($index, $status, $error)
{
    $pages = get_option('smos_facebook_connected_pages', array());
    if (!isset($pages[$index])) return;

    $pages[$index]['status'] = sanitize_text_field($status);
    $pages[$index]['last_test'] = current_time('mysql');
    $pages[$index]['last_error'] = sanitize_text_field($error);
    update_option('smos_facebook_connected_pages', $pages, false);
}

function smos_facebook_remove_page()
{
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_admin_referer('smos_facebook_remove_page');

    $page_id = sanitize_text_field($_GET['page_id'] ?? '');
    $pages = get_option('smos_facebook_connected_pages', array());

    if (is_array($pages)) {
        $pages = array_values(array_filter($pages, function ($page) use ($page_id) {
            return (string) ($page['page_id'] ?? '') !== (string) $page_id;
        }));
        update_option('smos_facebook_connected_pages', $pages, false);
    }

    smos_facebook_redirect_with_message('removed', 'Facebook Page telah dibuang.');
}

function smos_facebook_redirect_with_message($type, $message)
{
    wp_safe_redirect(add_query_arg(array(
        'page' => 'smos-facebook-stable',
        'smos_fb_notice_type' => sanitize_key($type),
        'smos_fb_notice' => rawurlencode($message),
    ), admin_url('admin.php')));
    exit;
}

function smos_facebook_clear_logs()
{
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_admin_referer('smos_facebook_clear_logs');

    delete_option('smos_facebook_logs');
    smos_facebook_redirect_with_message('cleared', 'Facebook API logs telah dikosongkan.');
}
