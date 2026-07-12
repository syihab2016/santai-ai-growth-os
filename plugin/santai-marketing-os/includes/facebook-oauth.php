<?php
if (!defined('ABSPATH')) exit;

add_action('admin_post_smos_facebook_oauth_start', 'smos_facebook_oauth_start');
add_action('admin_post_smos_facebook_oauth_callback', 'smos_facebook_oauth_callback');
add_action('admin_post_smos_facebook_oauth_disconnect', 'smos_facebook_oauth_disconnect');

function smos_facebook_oauth_default_redirect_uri()
{
    return admin_url('admin-post.php?action=smos_facebook_oauth_callback');
}

function smos_facebook_oauth_redirect_uri()
{
    $override = trim((string) get_option('smos_facebook_oauth_redirect_uri', ''));
    return $override ?: smos_facebook_oauth_default_redirect_uri();
}

function smos_facebook_oauth_required_permissions()
{
    return array(
        'pages_show_list',
        'pages_read_engagement',
        'pages_manage_posts',
        'business_management',
    );
}

function smos_facebook_oauth_get_config()
{
    return array(
        'app_id' => trim((string) get_option('smos_facebook_app_id', '')),
        'app_secret' => trim((string) get_option('smos_facebook_app_secret', '')),
        'configuration_id' => trim((string) get_option('smos_facebook_configuration_id', '')),
        'redirect_uri' => smos_facebook_oauth_redirect_uri(),
    );
}

function smos_facebook_oauth_start()
{
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_admin_referer('smos_facebook_oauth_start');

    $config = smos_facebook_oauth_get_config();
    if (!$config['app_id'] || !$config['app_secret']) {
        smos_facebook_redirect_with_message('error', 'Simpan Facebook App ID dan App Secret terlebih dahulu.');
    }

    $state = wp_generate_password(48, false, false);
    set_transient('smos_fb_oauth_state_' . get_current_user_id(), $state, 15 * MINUTE_IN_SECONDS);

    $args = array(
        'client_id' => $config['app_id'],
        'redirect_uri' => $config['redirect_uri'],
        'state' => $state,
        'response_type' => 'code',
        'auth_type' => 'rerequest',
    );

    if ($config['configuration_id']) {
        $args['config_id'] = $config['configuration_id'];
    } else {
        $args['scope'] = implode(',', smos_facebook_oauth_required_permissions());
    }

    $url = add_query_arg($args, 'https://www.facebook.com/' . SMOS_FACEBOOK_GRAPH_VERSION . '/dialog/oauth');
    wp_redirect($url);
    exit;
}

function smos_facebook_oauth_callback()
{
    if (!current_user_can('manage_options')) wp_die('Unauthorized');

    $expected = get_transient('smos_fb_oauth_state_' . get_current_user_id());
    $received = sanitize_text_field($_GET['state'] ?? '');
    delete_transient('smos_fb_oauth_state_' . get_current_user_id());

    if (!$expected || !$received || !hash_equals((string) $expected, (string) $received)) {
        smos_facebook_redirect_with_message('error', 'Facebook OAuth state tidak sah atau sudah tamat. Cuba sambung semula.');
    }

    if (!empty($_GET['error'])) {
        $message = sanitize_text_field($_GET['error_description'] ?? $_GET['error']);
        smos_facebook_redirect_with_message('error', 'Facebook menolak sambungan: ' . $message);
    }

    $code = sanitize_text_field($_GET['code'] ?? '');
    if (!$code) smos_facebook_redirect_with_message('error', 'Facebook tidak memulangkan authorization code.');

    $config = smos_facebook_oauth_get_config();
    $short = smos_facebook_oauth_exchange_code($code, $config);
    if (is_wp_error($short)) smos_facebook_redirect_with_message('error', $short->get_error_message());

    $token = sanitize_text_field($short['access_token'] ?? '');
    if (!$token) smos_facebook_redirect_with_message('error', 'Access token tidak diterima daripada Facebook.');

    $long = smos_facebook_oauth_exchange_long_lived_token($token, $config);
    if (!is_wp_error($long) && !empty($long['access_token'])) {
        $token = sanitize_text_field($long['access_token']);
        if (!empty($long['expires_in'])) {
            update_option('smos_facebook_user_token_expires_at', time() + intval($long['expires_in']), false);
        }
    } elseif (!empty($short['expires_in'])) {
        update_option('smos_facebook_user_token_expires_at', time() + intval($short['expires_in']), false);
    }

    $permission_check = smos_facebook_oauth_permission_status($token);
    if (is_wp_error($permission_check)) {
        smos_facebook_redirect_with_message('error', $permission_check->get_error_message());
    }

    update_option('smos_facebook_user_access_token', $token, false);
    update_option('smos_facebook_granted_permissions', $permission_check['granted'], false);
    update_option('smos_facebook_missing_permissions', $permission_check['missing'], false);
    update_option('smos_facebook_connected_at', current_time('mysql'), false);

    if (!empty($permission_check['missing'])) {
        smos_facebook_redirect_with_message(
            'error',
            'Facebook berjaya disambungkan tetapi permission ini belum diberi: ' . implode(', ', $permission_check['missing']) . '. Semak Configuration ID dan sambung semula.'
        );
    }

    $pages = smos_facebook_discover_pages($token);
    if (is_wp_error($pages)) smos_facebook_redirect_with_message('error', $pages->get_error_message());

    set_transient(smos_facebook_discovery_transient_key(), $pages, 30 * MINUTE_IN_SECONDS);
    smos_facebook_redirect_with_message('connected', count($pages) . ' Facebook Page berjaya ditemui. Pilih Page dan klik Save Selected Pages.');
}

function smos_facebook_oauth_exchange_code($code, $config)
{
    $url = add_query_arg(array(
        'client_id' => $config['app_id'],
        'client_secret' => $config['app_secret'],
        'redirect_uri' => $config['redirect_uri'],
        'code' => $code,
    ), smos_facebook_graph_url('oauth/access_token'));

    $response = wp_remote_get($url, array('timeout' => 45));
    return smos_facebook_parse_response($response, 'GET', 'oauth/access_token');
}

function smos_facebook_oauth_exchange_long_lived_token($short_token, $config)
{
    $url = add_query_arg(array(
        'grant_type' => 'fb_exchange_token',
        'client_id' => $config['app_id'],
        'client_secret' => $config['app_secret'],
        'fb_exchange_token' => $short_token,
    ), smos_facebook_graph_url('oauth/access_token'));

    $response = wp_remote_get($url, array('timeout' => 45));
    return smos_facebook_parse_response($response, 'GET', 'oauth/access_token?grant_type=fb_exchange_token');
}

function smos_facebook_oauth_permission_status($token)
{
    $response = smos_facebook_api_get('me/permissions', $token);
    if (is_wp_error($response)) return $response;

    $granted = array();
    foreach (($response['data'] ?? array()) as $permission) {
        if (($permission['status'] ?? '') === 'granted') {
            $granted[] = sanitize_key($permission['permission'] ?? '');
        }
    }
    $granted = array_values(array_filter(array_unique($granted)));
    $missing = array_values(array_diff(smos_facebook_oauth_required_permissions(), $granted));

    return array('granted' => $granted, 'missing' => $missing);
}

function smos_facebook_oauth_preflight($token = '')
{
    $token = $token ?: trim((string) get_option('smos_facebook_user_access_token', ''));
    if (!$token) return new WP_Error('smos_fb_not_connected', 'Facebook belum disambungkan.');

    $status = smos_facebook_oauth_permission_status($token);
    if (is_wp_error($status)) return $status;
    if (!empty($status['missing'])) {
        return new WP_Error(
            'smos_fb_missing_permissions',
            'Facebook perlu disambungkan semula. Permission belum diberi: ' . implode(', ', $status['missing'])
        );
    }
    return $status;
}

function smos_facebook_oauth_disconnect()
{
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_admin_referer('smos_facebook_oauth_disconnect');

    delete_option('smos_facebook_user_access_token');
    delete_option('smos_facebook_user_token_expires_at');
    delete_option('smos_facebook_granted_permissions');
    delete_option('smos_facebook_missing_permissions');
    delete_option('smos_facebook_connected_at');
    delete_option('smos_facebook_connected_pages');
    delete_transient(smos_facebook_discovery_transient_key());

    smos_facebook_redirect_with_message('removed', 'Facebook connection telah diputuskan.');
}
