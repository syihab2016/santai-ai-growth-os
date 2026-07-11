<?php
if (!defined('ABSPATH')) exit;

function smos_copywriter_page()
{
    $outputs = array();
    $error = '';
    $selected_product_id = 0;

    smos_seed_default_product();

    if (isset($_POST['smos_generate_content'])) {
        check_admin_referer('smos_copywriter_nonce');

        $selected_product_id = intval($_POST['product_id'] ?? 0);
        $platforms = array_map('sanitize_text_field', $_POST['platforms'] ?? array('facebook'));
        $template = sanitize_text_field($_POST['template'] ?? 'soft_sell');
        $length = sanitize_text_field($_POST['length'] ?? 'medium');
        $emoji = sanitize_text_field($_POST['emoji'] ?? 'low');
        $cta_type = sanitize_text_field($_POST['cta_type'] ?? 'website');
        $angle = sanitize_text_field($_POST['angle'] ?? '');
        $extra_instruction = sanitize_textarea_field($_POST['extra_instruction'] ?? '');

        if (!$selected_product_id) {
            $error = 'Sila pilih produk.';
        } elseif (empty($platforms)) {
            $error = 'Sila pilih sekurang-kurangnya satu platform.';
        } else {
            $product_data = smos_get_product_knowledge($selected_product_id);
            $all_platforms = smos_get_platforms();

            foreach ($platforms as $platform) {
                if (!isset($all_platforms[$platform])) continue;

                $prompt = smos_build_prompt(array(
                    'product' => $product_data,
                    'platform' => $platform,
                    'template' => $template,
                    'length' => $length,
                    'emoji' => $emoji,
                    'cta_type' => $cta_type,
                    'angle' => $angle,
                    'extra_instruction' => $extra_instruction
                ));

                $result = smos_call_openai($prompt);

                if (is_wp_error($result)) {
                    $outputs[$platform] = array('label' => $all_platforms[$platform], 'error' => $result->get_error_message(), 'content' => '');
                } else {
                    $outputs[$platform] = array('label' => $all_platforms[$platform], 'error' => '', 'content' => $result);
                    smos_save_generated_history($selected_product_id, $platform, $template, $result);
                }
            }
        }
    }

    include SMOS_PATH . 'templates/copywriter.php';
}
