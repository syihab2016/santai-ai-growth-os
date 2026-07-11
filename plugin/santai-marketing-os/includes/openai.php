<?php
if (!defined('ABSPATH')) exit;

function smos_call_openai($prompt)
{
    $api_key = get_option('smos_openai_api_key', '');
    $model = get_option('smos_openai_model', 'gpt-4.1-mini');

    if (!$api_key) {
        return new WP_Error('missing_api_key', 'OpenAI API Key belum dimasukkan di Settings.');
    }

    $brand_voice = get_option('smos_brand_voice', '');

    $system = "Anda ialah AI Copywriter untuk Santai Ilmu Publication. Tulis dalam Bahasa Melayu Malaysia. Gaya Syihabudin Ahmad: berilmu, jelas, mesra, tegas tetapi beradab. Jangan terlalu korporat. CTA mesti natural.";
    if ($brand_voice) $system .= "\n\nBrand Voice tambahan:\n" . $brand_voice;

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
        'timeout' => 90,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
        'body' => wp_json_encode(array(
            'model' => $model,
            'messages' => array(
                array('role' => 'system', 'content' => $system),
                array('role' => 'user', 'content' => $prompt)
            ),
            'temperature' => 0.75,
        ))
    ));

    if (is_wp_error($response)) return $response;

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($code < 200 || $code >= 300) {
        $message = $body['error']['message'] ?? 'OpenAI API error.';
        return new WP_Error('openai_error', $message);
    }

    return $body['choices'][0]['message']['content'] ?? '';
}
