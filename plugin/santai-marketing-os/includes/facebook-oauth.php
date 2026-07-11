<?php
if (!defined('ABSPATH')) exit;

define('SMOS_META_GRAPH_VERSION', 'v25.0');

add_action('admin_post_smos_facebook_oauth_start', 'smos_facebook_oauth_start');
add_action('admin_post_smos_facebook_oauth_callback', 'smos_facebook_oauth_callback');
add_action('admin_post_smos_facebook_test_connection', 'smos_facebook_test_connection');
add_action('admin_post_smos_facebook_disconnect', 'smos_facebook_disconnect');

function smos_facebook_connector_page()
{
    if (!current_user_can('manage_options')) wp_die('Unauthorized');

    if (isset($_POST['smos_save_facebook_app'])) {
        check_admin_referer('smos_save_facebook_app_nonce');
        update_option('smos_facebook_app_id', sanitize_text_field($_POST['smos_facebook_app_id'] ?? ''));
        update_option('smos_facebook_config_id', sanitize_text_field($_POST['smos_facebook_config_id'] ?? ''));
        $secret = trim((string) ($_POST['smos_facebook_app_secret'] ?? ''));
        if ($secret !== '') update_option('smos_facebook_app_secret', sanitize_text_field($secret));
        echo '<div class="notice notice-success"><p>Facebook App settings disimpan.</p></div>';
    }

    $pages = get_option('smos_facebook_connected_pages', array());
    $status = get_option('smos_facebook_connection_status', 'disconnected');
    $last_error = get_option('smos_facebook_last_error', '');
    $last_test = get_option('smos_facebook_last_test', '');
    $app_id = get_option('smos_facebook_app_id', '');
    $config_id = get_option('smos_facebook_config_id', '');
    $app_secret_exists = (bool) get_option('smos_facebook_app_secret', '');
    $redirect_uri = smos_facebook_oauth_redirect_uri();

    include SMOS_PATH . 'templates/facebook-connector.php';
}

function smos_facebook_oauth_redirect_uri()
{
    return admin_url('admin-post.php?action=smos_facebook_oauth_callback');
}

function smos_facebook_oauth_start()
{
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_admin_referer('smos_facebook_oauth_start');

    $app_id = get_option('smos_facebook_app_id', '');
    $app_secret = get_option('smos_facebook_app_secret', '');
    $config_id = get_option('smos_facebook_config_id', '');

    if (!$app_id || !$app_secret || !$config_id) {
        smos_facebook_oauth_redirect_with_error('Simpan App ID, App Secret dan Configuration ID dahulu.');
    }

    $state = wp_generate_password(32, false, false);
    set_transient('smos_facebook_oauth_state_' . get_current_user_id(), $state, 15 * MINUTE_IN_SECONDS);

    $auth_url = add_query_arg(array(
        'client_id' => $app_id,
        'redirect_uri' => smos_facebook_oauth_redirect_uri(),
        'state' => $state,
        'response_type' => 'code',
        'config_id' => $config_id,
    ), 'https://www.facebook.com/' . SMOS_META_GRAPH_VERSION . '/dialog/oauth');

    wp_redirect($auth_url);
    exit;
}

function smos_facebook_oauth_callback()
{
    if (!current_user_can('manage_options')) wp_die('Unauthorized');

    $uid = get_current_user_id();
    $expected = get_transient('smos_facebook_oauth_state_' . $uid);
    delete_transient('smos_facebook_oauth_state_' . $uid);

    $state = sanitize_text_field($_GET['state'] ?? '');
    $code = sanitize_text_field($_GET['code'] ?? '');
    $error = sanitize_text_field($_GET['error_description'] ?? ($_GET['error_message'] ?? ''));

    if ($error) smos_facebook_oauth_redirect_with_error($error);
    if (!$expected || !$state || !hash_equals($expected, $state)) {
        smos_facebook_oauth_redirect_with_error('OAuth state tidak sah. Cuba Connect semula.');
    }
    if (!$code) smos_facebook_oauth_redirect_with_error('Authorization code tidak diterima.');

    $app_id = get_option('smos_facebook_app_id', '');
    $app_secret = get_option('smos_facebook_app_secret', '');

    $short = smos_facebook_exchange_code_for_token($app_id, $app_secret, $code);
    if (is_wp_error($short)) smos_facebook_oauth_redirect_with_error($short->get_error_message());

    $long = smos_facebook_exchange_long_lived_token($app_id, $app_secret, $short);
    if (is_wp_error($long)) smos_facebook_oauth_redirect_with_error($long->get_error_message());

    $pages = smos_facebook_fetch_pages($long);
    if (is_wp_error($pages)) smos_facebook_oauth_redirect_with_error($pages->get_error_message());
    if (empty($pages)) smos_facebook_oauth_redirect_with_error('Tiada Facebook Page ditemui untuk akaun ini.');

    update_option('smos_facebook_user_access_token', $long);
    update_option('smos_facebook_connected_pages', $pages);
    update_option('smos_facebook_connection_status', 'connected');
    update_option('smos_facebook_last_error', '');
    update_option('smos_facebook_last_test', current_time('mysql'));
    smos_facebook_sync_legacy_page_options($pages);

    wp_safe_redirect(add_query_arg(array('page'=>'smos-facebook-connector','smos_connected'=>'1'), admin_url('admin.php')));
    exit;
}

function smos_facebook_exchange_code_for_token($app_id, $app_secret, $code)
{
    $url = add_query_arg(array(
        'client_id'=>$app_id,
        'client_secret'=>$app_secret,
        'redirect_uri'=>smos_facebook_oauth_redirect_uri(),
        'code'=>$code,
    ), 'https://graph.facebook.com/' . SMOS_META_GRAPH_VERSION . '/oauth/access_token');

    return smos_facebook_extract_access_token(wp_remote_get($url, array('timeout'=>45)), 'Gagal mendapatkan access token.');
}

function smos_facebook_exchange_long_lived_token($app_id, $app_secret, $short_token)
{
    $url = add_query_arg(array(
        'grant_type'=>'fb_exchange_token',
        'client_id'=>$app_id,
        'client_secret'=>$app_secret,
        'fb_exchange_token'=>$short_token,
    ), 'https://graph.facebook.com/' . SMOS_META_GRAPH_VERSION . '/oauth/access_token');

    return smos_facebook_extract_access_token(wp_remote_get($url, array('timeout'=>45)), 'Gagal mendapatkan long-lived access token.');
}

function smos_facebook_extract_access_token($response, $fallback)
{
    if (is_wp_error($response)) return $response;
    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($code < 200 || $code >= 300 || empty($body['access_token'])) {
        return new WP_Error('facebook_token_error', $body['error']['message'] ?? $fallback);
    }
    return sanitize_text_field($body['access_token']);
}

function smos_facebook_fetch_pages($user_token)
{
    $url = add_query_arg(array(
        'fields'=>'id,name,access_token,instagram_business_account{id,username}',
        'limit'=>100,
        'access_token'=>$user_token,
    ), 'https://graph.facebook.com/' . SMOS_META_GRAPH_VERSION . '/me/accounts');

    $response = wp_remote_get($url, array('timeout'=>45));
    if (is_wp_error($response)) return $response;

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($code < 200 || $code >= 300) {
        return new WP_Error('facebook_pages_error', $body['error']['message'] ?? 'Gagal mendapatkan Facebook Pages.');
    }

    $pages = array();
    foreach (($body['data'] ?? array()) as $item) {
        if (empty($item['id']) || empty($item['access_token'])) continue;
        $pages[] = array(
            'id'=>sanitize_text_field($item['id']),
            'name'=>sanitize_text_field($item['name'] ?? 'Facebook Page'),
            'access_token'=>sanitize_text_field($item['access_token']),
            'instagram_id'=>sanitize_text_field($item['instagram_business_account']['id'] ?? ''),
            'instagram_username'=>sanitize_text_field($item['instagram_business_account']['username'] ?? ''),
        );
    }
    return $pages;
}

function smos_facebook_sync_legacy_page_options($pages)
{
    for ($i=1; $i<=3; $i++) {
        $idx = $i-1;
        if (isset($pages[$idx])) {
            update_option('smos_facebook_page_' . $i . '_name', $pages[$idx]['name']);
            update_option('smos_facebook_page_' . $i . '_id', $pages[$idx]['id']);
            update_option('smos_facebook_page_' . $i . '_access_token', $pages[$idx]['access_token']);
            if ($i===1) {
                update_option('smos_facebook_page_id', $pages[$idx]['id']);
                update_option('smos_facebook_page_access_token', $pages[$idx]['access_token']);
            }
        }
    }
}

function smos_facebook_test_connection()
{
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_admin_referer('smos_facebook_test_connection');

    $pages = get_option('smos_facebook_connected_pages', array());
    if (empty($pages)) smos_facebook_oauth_redirect_with_error('Facebook belum disambungkan.');

    $errors = array();
    foreach ($pages as $page) {
        $url = add_query_arg(array(
            'fields'=>'id,name',
            'access_token'=>$page['access_token'],
        ), 'https://graph.facebook.com/' . SMOS_META_GRAPH_VERSION . '/' . rawurlencode($page['id']));

        $response = wp_remote_get($url, array('timeout'=>30));
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $code = wp_remote_retrieve_response_code($response);

        if (is_wp_error($response) || $code < 200 || $code >= 300 || empty($body['id'])) {
            $errors[] = $page['name'] . ': ' . ($body['error']['message'] ?? 'Connection failed');
        }
    }

    if ($errors) smos_facebook_oauth_redirect_with_error(implode(' | ', $errors));

    update_option('smos_facebook_connection_status', 'connected');
    update_option('smos_facebook_last_error', '');
    update_option('smos_facebook_last_test', current_time('mysql'));

    wp_safe_redirect(add_query_arg(array('page'=>'smos-facebook-connector','smos_tested'=>'1'), admin_url('admin.php')));
    exit;
}

function smos_facebook_disconnect()
{
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_admin_referer('smos_facebook_disconnect');

    delete_option('smos_facebook_user_access_token');
    delete_option('smos_facebook_connected_pages');
    delete_option('smos_facebook_connection_status');
    delete_option('smos_facebook_last_error');
    delete_option('smos_facebook_last_test');
    delete_option('smos_facebook_page_id');
    delete_option('smos_facebook_page_access_token');

    for ($i=1; $i<=3; $i++) {
        delete_option('smos_facebook_page_' . $i . '_name');
        delete_option('smos_facebook_page_' . $i . '_id');
        delete_option('smos_facebook_page_' . $i . '_access_token');
    }

    wp_safe_redirect(add_query_arg(array('page'=>'smos-facebook-connector','smos_disconnected'=>'1'), admin_url('admin.php')));
    exit;
}

function smos_facebook_oauth_redirect_with_error($message)
{
    update_option('smos_facebook_connection_status', 'error');
    update_option('smos_facebook_last_error', sanitize_text_field($message));
    wp_safe_redirect(add_query_arg(array(
        'page'=>'smos-facebook-connector',
        'smos_error'=>rawurlencode($message),
    ), admin_url('admin.php')));
    exit;
}
