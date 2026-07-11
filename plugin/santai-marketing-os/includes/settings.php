<?php
if (!defined('ABSPATH')) exit;

function smos_settings_page()
{
    if (isset($_POST['smos_save_settings'])) {
        check_admin_referer('smos_settings_nonce');

        update_option('smos_openai_api_key', sanitize_text_field($_POST['smos_openai_api_key'] ?? ''));
        update_option('smos_openai_model', sanitize_text_field($_POST['smos_openai_model'] ?? 'gpt-4.1-mini'));
        update_option('smos_brand_voice', sanitize_textarea_field($_POST['smos_brand_voice'] ?? ''));
        update_option('smos_default_cta_url', esc_url_raw($_POST['smos_default_cta_url'] ?? ''));

        echo '<div class="notice notice-success"><p>Settings berjaya disimpan.</p></div>';
    }

    include SMOS_PATH . 'templates/settings.php';
}
