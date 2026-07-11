<?php
if (!defined('ABSPATH')) exit;

add_action('admin_post_smos_facebook_test_manual', 'smos_facebook_test_manual');
add_action('admin_post_smos_facebook_clear_logs', 'smos_facebook_clear_logs');

function smos_facebook_stable_page()
{
    if (!current_user_can('manage_options')) wp_die('Unauthorized');

    if (isset($_POST['smos_save_facebook_manual'])) {
        check_admin_referer('smos_save_facebook_manual_nonce');

        $page_name = sanitize_text_field($_POST['smos_facebook_page_name'] ?? '');
        $page_id = sanitize_text_field($_POST['smos_facebook_page_id'] ?? '');
        $submitted_token = trim((string) ($_POST['smos_facebook_access_token'] ?? ''));

        if (!$page_id) {
            echo '<div class="notice notice-error"><p>Page ID diperlukan.</p></div>';
        } else {
            $current_token = trim((string) get_option('smos_facebook_page_access_token', ''));
            $token_to_resolve = $submitted_token ?: $current_token;

            if (!$token_to_resolve) {
                echo '<div class="notice notice-error"><p>Access Token diperlukan.</p></div>';
            } else {
                $resolved = smos_facebook_resolve_page_token($page_id, $token_to_resolve);

                if (is_wp_error($resolved)) {
                    update_option('smos_facebook_manual_status', 'error');
                    update_option('smos_facebook_manual_last_error', $resolved->get_error_message());
                    echo '<div class="notice notice-error"><p>' . esc_html($resolved->get_error_message()) . '</p></div>';
                } else {
                    $resolved_name = $resolved['name'] ?: $page_name ?: 'Facebook Page';
                    $page_token = $resolved['token'];

                    // One source of truth, synced to Page 1 for Campaign publishing.
                    update_option('smos_facebook_page_name', $resolved_name);
                    update_option('smos_facebook_page_id', $page_id);
                    update_option('smos_facebook_page_access_token', $page_token);
                    update_option('smos_facebook_page_1_name', $resolved_name);
                    update_option('smos_facebook_page_1_id', $page_id);
                    update_option('smos_facebook_page_1_access_token', $page_token);
                    update_option('smos_facebook_token_source', $resolved['source']);
                    update_option('smos_facebook_manual_status', 'connected');
                    update_option('smos_facebook_manual_last_test', current_time('mysql'));
                    update_option('smos_facebook_manual_last_error', '');

                    echo '<div class="notice notice-success"><p>Facebook Page Access Token berjaya disahkan dan disimpan.</p></div>';
                }
            }
        }
    }

    $page_name = get_option('smos_facebook_page_name', '');
    $page_id = get_option('smos_facebook_page_id', '');
    $token_exists = (bool) get_option('smos_facebook_page_access_token', '');
    $token_source = get_option('smos_facebook_token_source', '');
    $status = get_option('smos_facebook_manual_status', 'not_tested');
    $last_test = get_option('smos_facebook_manual_last_test', '');
    $last_error = get_option('smos_facebook_manual_last_error', '');
    $logs = get_option('smos_facebook_logs', array());

    include SMOS_PATH . 'templates/facebook-stable.php';
}

function smos_facebook_resolve_page_token($page_id, $token)
{
    // First identify who/what the submitted token belongs to.
    $identity = smos_facebook_api_get('me', $token, array('fields' => 'id,name'));

    if (is_wp_error($identity)) {
        return $identity;
    }

    $identity_id = (string) ($identity['id'] ?? '');

    // It is already the correct Page Access Token.
    if ($identity_id === (string) $page_id) {
        return array(
            'token' => $token,
            'name' => sanitize_text_field($identity['name'] ?? ''),
            'source' => 'page_token',
        );
    }

    // It appears to be a User Access Token. Convert it using /me/accounts.
    $accounts = smos_facebook_api_get('me/accounts', $token, array(
        'fields' => 'id,name,access_token',
        'limit' => 100,
    ));

    if (is_wp_error($accounts)) {
        return new WP_Error(
            'fb_token_not_page',
            'Token ini bukan Page Access Token dan Page Access Token tidak dapat diperoleh: ' . $accounts->get_error_message()
        );
    }

    foreach (($accounts['data'] ?? array()) as $account) {
        if ((string) ($account['id'] ?? '') === (string) $page_id && !empty($account['access_token'])) {
            return array(
                'token' => sanitize_text_field($account['access_token']),
                'name' => sanitize_text_field($account['name'] ?? ''),
                'source' => 'converted_from_user_token',
            );
        }
    }

    return new WP_Error(
        'fb_page_not_found',
        'Token tersebut tidak mempunyai akses kepada Page ID ini. Gunakan User Access Token yang menyenaraikan Page berkenaan atau salin access_token Page daripada hasil /me/accounts.'
    );
}

function smos_facebook_test_manual()
{
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_admin_referer('smos_facebook_test_manual');

    $config = smos_facebook_get_page_config('page_1');

    if (!$config) {
        smos_facebook_manual_redirect_error('Page ID atau Page Access Token belum disimpan.');
    }

    // A real Page Access Token returns the Page itself from /me.
    $identity = smos_facebook_api_get('me', $config['token'], array('fields' => 'id,name'));

    if (is_wp_error($identity)) {
        smos_facebook_manual_redirect_error($identity->get_error_message());
    }

    if ((string) ($identity['id'] ?? '') !== (string) $config['page_id']) {
        smos_facebook_manual_redirect_error(
            'Token tersimpan bukan Page Access Token untuk Page ID ini. Simpan semula token melalui borang di atas.'
        );
    }

    update_option('smos_facebook_page_name', sanitize_text_field($identity['name'] ?? $config['label']));
    update_option('smos_facebook_page_1_name', sanitize_text_field($identity['name'] ?? $config['label']));
    update_option('smos_facebook_manual_status', 'connected');
    update_option('smos_facebook_manual_last_test', current_time('mysql'));
    update_option('smos_facebook_manual_last_error', '');

    wp_safe_redirect(add_query_arg(array(
        'page' => 'smos-facebook-stable',
        'tested' => '1',
    ), admin_url('admin.php')));
    exit;
}

function smos_facebook_manual_redirect_error($message)
{
    update_option('smos_facebook_manual_status', 'error');
    update_option('smos_facebook_manual_last_error', sanitize_text_field($message));

    wp_safe_redirect(add_query_arg(array(
        'page' => 'smos-facebook-stable',
        'error' => rawurlencode($message),
    ), admin_url('admin.php')));
    exit;
}

function smos_facebook_clear_logs()
{
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_admin_referer('smos_facebook_clear_logs');

    delete_option('smos_facebook_logs');

    wp_safe_redirect(add_query_arg(array('page' => 'smos-facebook-stable'), admin_url('admin.php')));
    exit;
}
