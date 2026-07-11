<?php
if (!defined('ABSPATH')) exit;

add_action('init', 'smos_register_campaign_cpt');
add_action('add_meta_boxes', 'smos_campaign_metaboxes');
add_action('save_post_smos_campaign', 'smos_save_campaign_meta');

function smos_register_campaign_cpt()
{
    register_post_type('smos_campaign', array(
        'labels' => array(
            'name' => 'Campaigns',
            'singular_name' => 'Campaign',
            'add_new_item' => 'Add New Campaign',
            'edit_item' => 'Edit Campaign',
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'smos-dashboard',
        'supports' => array('title'),
    ));
}

function smos_campaign_metaboxes()
{
    add_meta_box('smos_campaign_builder', 'Campaign Builder', 'smos_campaign_builder_box', 'smos_campaign', 'normal', 'high');
}

function smos_campaign_builder_box($post)
{
    wp_nonce_field('smos_save_campaign', 'smos_campaign_nonce');
    smos_seed_default_product();

    $products = get_posts(array('post_type'=>'smos_product','post_status'=>'publish','numberposts'=>-1));
    $product_id = get_post_meta($post->ID, '_smos_campaign_product_id', true);
    $template = get_post_meta($post->ID, '_smos_campaign_template', true) ?: 'soft_sell';
    $length = get_post_meta($post->ID, '_smos_campaign_length', true) ?: 'medium';
    $emoji = get_post_meta($post->ID, '_smos_campaign_emoji', true) ?: 'low';
    $cta = get_post_meta($post->ID, '_smos_campaign_cta_type', true) ?: 'website';
    $angle = get_post_meta($post->ID, '_smos_campaign_angle', true) ?: 'Sayang Nabi dengan ilmu, bukan sekadar slogan';
    $fb_media_mode = get_post_meta($post->ID, '_smos_campaign_fb_media_mode', true) ?: 'auto_random';
    $fb_schedule_datetime = get_post_meta($post->ID, '_smos_campaign_fb_schedule_datetime', true);
    $selected_platforms = get_post_meta($post->ID, '_smos_campaign_platforms', true);
    if (!is_array($selected_platforms)) {
        $selected_platforms = array('facebook','instagram','whatsapp','telegram','youtube_community');
    }

    echo '<div class="smos-card">';
    echo '<p><label><strong>Produk</strong></label><select name="smos_campaign_product_id" style="width:100%;">';
    foreach ($products as $product) {
        echo '<option value="' . esc_attr($product->ID) . '" ' . selected($product_id, $product->ID, false) . '>' . esc_html($product->post_title) . '</option>';
    }
    echo '</select></p>';

    echo '<div class="smos-grid">';
    smos_campaign_select('Template', 'smos_campaign_template', smos_get_templates(), $template);
    smos_campaign_select('Panjang', 'smos_campaign_length', smos_get_lengths(), $length);
    smos_campaign_select('Emoji', 'smos_campaign_emoji', smos_get_emoji_levels(), $emoji);
    smos_campaign_select('CTA', 'smos_campaign_cta_type', smos_get_cta_types(), $cta);
    echo '</div>';

    echo '<p><label><strong>Platform</strong></label></p><div class="smos-platforms">';
    foreach (smos_get_platforms() as $key=>$label) {
        echo '<label class="smos-check"><input type="checkbox" name="smos_campaign_platforms[]" value="' . esc_attr($key) . '" ' . checked(in_array($key, $selected_platforms), true, false) . '> ' . esc_html($label) . '</label>';
    }
    echo '</div>';

    $fb_page_key = get_post_meta($post->ID, '_smos_campaign_facebook_page_key', true) ?: 'page_1';
    $fb_pages = function_exists('smos_facebook_get_pages') ? smos_facebook_get_pages() : array();
    echo '<p><label><strong>Facebook Target Page</strong></label><select name="smos_campaign_facebook_page_key" style="width:100%;">';
    if (empty($fb_pages)) {
        echo '<option value="page_1">Page 1 (isi di Settings)</option>';
    } else {
        foreach ($fb_pages as $key => $page) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($fb_page_key, $key, false) . '>' . esc_html($page['label'] . ' — ' . $page['page_id']) . '</option>';
        }
    }
    echo '</select></p>';

    echo '<div class="smos-grid">';
    smos_campaign_select('Facebook Media Mode', 'smos_campaign_fb_media_mode', smos_campaign_fb_media_modes(), $fb_media_mode);
    echo '<p><label><strong>Facebook Schedule Date/Time</strong></label><input type="datetime-local" name="smos_campaign_fb_schedule_datetime" value="' . esc_attr($fb_schedule_datetime) . '" style="width:100%;"><span class="description">Jika mahu schedule, isi masa sekurang-kurangnya 10 minit dari sekarang.</span></p>';
    echo '</div>';

    echo '<p><label><strong>Angle Kempen</strong></label><input type="text" name="smos_campaign_angle" value="' . esc_attr($angle) . '" style="width:100%;"></p>';

    echo '<p>';
    echo '<button type="submit" name="smos_generate_campaign" class="button button-primary button-hero">Generate Campaign Content</button> ';
    echo '<button type="submit" name="smos_approve_campaign" class="button">Approve Campaign</button> ';
    echo '<button type="submit" name="smos_post_facebook_campaign" class="button button-secondary">Post Now to Facebook</button> ';
    echo '<button type="submit" name="smos_schedule_facebook_campaign" class="button button-secondary">Schedule to Facebook</button>';
    echo '</p>';

    $status = get_post_meta($post->ID, '_smos_campaign_status', true) ?: 'draft';
    echo '<p><strong>Status:</strong> ' . esc_html(ucfirst($status)) . '</p>';

    $last_error = get_post_meta($post->ID, '_smos_campaign_last_error', true);
    $fb_post_id = get_post_meta($post->ID, '_smos_facebook_post_id', true);
    $fb_scheduled_time = get_post_meta($post->ID, '_smos_facebook_scheduled_time', true);
    $selected_media_url = get_post_meta($post->ID, '_smos_campaign_selected_media_url', true);
    $selected_media_type = get_post_meta($post->ID, '_smos_campaign_selected_media_type', true);

    if ($last_error) echo '<p style="color:#b32d2e;"><strong>Error:</strong> ' . esc_html($last_error) . '</p>';
    if ($fb_post_id) echo '<p style="color:#008a20;"><strong>Facebook Post ID:</strong> ' . esc_html($fb_post_id) . '</p>';
    if ($fb_scheduled_time) echo '<p style="color:#008a20;"><strong>Facebook Scheduled Time:</strong> ' . esc_html($fb_scheduled_time) . '</p>';
    if ($selected_media_url) echo '<p><strong>Selected Media:</strong> ' . esc_html($selected_media_type) . ' — <a href="' . esc_url($selected_media_url) . '" target="_blank">' . esc_html($selected_media_url) . '</a></p>';

    echo '</div>';

    smos_campaign_preview_box($post->ID);
}

function smos_campaign_select($label, $name, $options, $selected)
{
    echo '<p><label><strong>' . esc_html($label) . '</strong></label><select name="' . esc_attr($name) . '" style="width:100%;">';
    foreach ($options as $key=>$text) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($selected, $key, false) . '>' . esc_html($text) . '</option>';
    }
    echo '</select></p>';
}

function smos_campaign_fb_media_modes()
{
    return array(
        'auto_random' => 'Auto Random: Image / Short Video / Text',
        'image_random' => 'Random Image sahaja',
        'video_random' => 'Random Short Video sahaja',
        'text_only' => 'Text sahaja'
    );
}

function smos_save_campaign_meta($post_id)
{
    if (!isset($_POST['smos_campaign_nonce']) || !wp_verify_nonce($_POST['smos_campaign_nonce'], 'smos_save_campaign')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, '_smos_campaign_product_id', intval($_POST['smos_campaign_product_id'] ?? 0));
    update_post_meta($post_id, '_smos_campaign_template', sanitize_text_field($_POST['smos_campaign_template'] ?? 'soft_sell'));
    update_post_meta($post_id, '_smos_campaign_length', sanitize_text_field($_POST['smos_campaign_length'] ?? 'medium'));
    update_post_meta($post_id, '_smos_campaign_emoji', sanitize_text_field($_POST['smos_campaign_emoji'] ?? 'low'));
    update_post_meta($post_id, '_smos_campaign_cta_type', sanitize_text_field($_POST['smos_campaign_cta_type'] ?? 'website'));
    update_post_meta($post_id, '_smos_campaign_angle', sanitize_text_field($_POST['smos_campaign_angle'] ?? ''));
    update_post_meta($post_id, '_smos_campaign_platforms', array_map('sanitize_text_field', $_POST['smos_campaign_platforms'] ?? array()));
    update_post_meta($post_id, '_smos_campaign_facebook_page_key', sanitize_text_field($_POST['smos_campaign_facebook_page_key'] ?? 'page_1'));
    update_post_meta($post_id, '_smos_campaign_fb_media_mode', sanitize_text_field($_POST['smos_campaign_fb_media_mode'] ?? 'auto_random'));
    update_post_meta($post_id, '_smos_campaign_fb_schedule_datetime', sanitize_text_field($_POST['smos_campaign_fb_schedule_datetime'] ?? ''));

    if (isset($_POST['smos_generate_campaign'])) {
        smos_generate_campaign_content($post_id);
    }

    if (isset($_POST['smos_approve_campaign'])) {
        update_post_meta($post_id, '_smos_campaign_status', 'approved');
    }

    if (isset($_POST['smos_post_facebook_campaign'])) {
        smos_campaign_post_to_facebook($post_id, false);
    }

    if (isset($_POST['smos_schedule_facebook_campaign'])) {
        smos_campaign_post_to_facebook($post_id, true);
    }
}

function smos_generate_campaign_content($campaign_id)
{
    $product_id = intval(get_post_meta($campaign_id, '_smos_campaign_product_id', true));
    if (!$product_id) return;

    $platforms = get_post_meta($campaign_id, '_smos_campaign_platforms', true);
    if (!is_array($platforms) || empty($platforms)) $platforms = array('facebook');

    $template = get_post_meta($campaign_id, '_smos_campaign_template', true) ?: 'soft_sell';
    $length = get_post_meta($campaign_id, '_smos_campaign_length', true) ?: 'medium';
    $emoji = get_post_meta($campaign_id, '_smos_campaign_emoji', true) ?: 'low';
    $cta = get_post_meta($campaign_id, '_smos_campaign_cta_type', true) ?: 'website';
    $angle = get_post_meta($campaign_id, '_smos_campaign_angle', true);

    $product_data = smos_get_product_knowledge($product_id);
    $outputs = array();

    foreach ($platforms as $platform) {
        $prompt = smos_build_prompt(array(
            'product' => $product_data,
            'platform' => $platform,
            'template' => $template,
            'length' => $length,
            'emoji' => $emoji,
            'cta_type' => $cta,
            'angle' => $angle,
            'extra_instruction' => ''
        ));

        $result = smos_call_openai($prompt);
        if (is_wp_error($result)) {
            $outputs[$platform] = 'ERROR: ' . $result->get_error_message();
        } else {
            $outputs[$platform] = $result;
            smos_save_generated_history($product_id, $platform, $template, $result);
        }
    }

    update_post_meta($campaign_id, '_smos_campaign_outputs', $outputs);
    update_post_meta($campaign_id, '_smos_campaign_status', 'generated');
}

function smos_campaign_get_schedule_timestamp($campaign_id)
{
    $datetime = get_post_meta($campaign_id, '_smos_campaign_fb_schedule_datetime', true);

    if (!$datetime) {
        return new WP_Error('missing_schedule_datetime', 'Sila isi Facebook Schedule Date/Time.');
    }

    try {
        $tz = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone(wp_timezone_string() ?: 'UTC');
        $dt = new DateTimeImmutable($datetime, $tz);
        return $dt->getTimestamp();
    } catch (Exception $e) {
        return new WP_Error('invalid_schedule_datetime', 'Format masa schedule tidak sah.');
    }
}

function smos_campaign_resolve_random_media($campaign_id)
{
    $product_id = intval(get_post_meta($campaign_id, '_smos_campaign_product_id', true));
    if (!$product_id) return array('type' => 'text', 'url' => '');

    $mode = get_post_meta($campaign_id, '_smos_campaign_fb_media_mode', true) ?: 'auto_random';

    if ($mode === 'text_only') {
        return array('type' => 'text', 'url' => '');
    }

    if ($mode === 'image_random') {
        $media = smos_get_product_media_urls($product_id, 'image');
    } elseif ($mode === 'video_random') {
        $media = smos_get_product_media_urls($product_id, 'video');
    } else {
        $media = smos_get_product_media_urls($product_id, 'all');
    }

    if (empty($media)) {
        return array('type' => 'text', 'url' => '');
    }

    $selected = $media[array_rand($media)];
    return $selected;
}

function smos_campaign_post_to_facebook($campaign_id, $schedule = false)
{
    $outputs = get_post_meta($campaign_id, '_smos_campaign_outputs', true);

    if (!is_array($outputs) || empty($outputs['facebook'])) {
        update_post_meta($campaign_id, '_smos_campaign_status', 'failed');
        update_post_meta($campaign_id, '_smos_campaign_last_error', 'Tiada kandungan Facebook untuk dipos.');
        return;
    }

    $fb_page_key = get_post_meta($campaign_id, '_smos_campaign_facebook_page_key', true) ?: 'page_1';
    $scheduled_timestamp = 0;

    if ($schedule) {
        $scheduled_timestamp = smos_campaign_get_schedule_timestamp($campaign_id);
        if (is_wp_error($scheduled_timestamp)) {
            update_post_meta($campaign_id, '_smos_campaign_status', 'failed');
            update_post_meta($campaign_id, '_smos_campaign_last_error', $scheduled_timestamp->get_error_message());
            return;
        }
        update_post_meta($campaign_id, '_smos_campaign_status', 'scheduling');
    } else {
        update_post_meta($campaign_id, '_smos_campaign_status', 'posting');
    }

    $media = smos_campaign_resolve_random_media($campaign_id);
    update_post_meta($campaign_id, '_smos_campaign_selected_media_type', $media['type']);
    update_post_meta($campaign_id, '_smos_campaign_selected_media_url', $media['url']);

    if ($media['type'] === 'image' && $media['url']) {
        $result = smos_facebook_post_photo_to_page($outputs['facebook'], $media['url'], $fb_page_key, $scheduled_timestamp);
    } elseif ($media['type'] === 'video' && $media['url']) {
        $result = smos_facebook_post_video_to_page($outputs['facebook'], $media['url'], $fb_page_key, $scheduled_timestamp);
    } else {
        $result = smos_facebook_post_to_page($outputs['facebook'], $fb_page_key, $scheduled_timestamp);
    }

    if (is_wp_error($result)) {
        update_post_meta($campaign_id, '_smos_campaign_status', 'failed');
        update_post_meta($campaign_id, '_smos_campaign_last_error', $result->get_error_message());
        return;
    }

    if ($schedule) {
        update_post_meta($campaign_id, '_smos_campaign_status', 'scheduled');
        update_post_meta($campaign_id, '_smos_facebook_scheduled_time', wp_date('Y-m-d H:i:s', $scheduled_timestamp));
    } else {
        update_post_meta($campaign_id, '_smos_campaign_status', 'posted');
        update_post_meta($campaign_id, '_smos_facebook_scheduled_time', '');
    }

    update_post_meta($campaign_id, '_smos_facebook_post_id', isset($result['post_id']) ? $result['post_id'] : (isset($result['id']) ? $result['id'] : ''));
    update_post_meta($campaign_id, '_smos_campaign_last_error', '');
}

function smos_campaign_preview_box($campaign_id)
{
    $outputs = get_post_meta($campaign_id, '_smos_campaign_outputs', true);

    echo '<div class="smos-card"><h3>Campaign Preview</h3>';

    if (!is_array($outputs) || empty($outputs)) {
        echo '<p>Belum ada content. Klik <strong>Generate Campaign Content</strong>.</p></div>';
        return;
    }

    foreach ($outputs as $platform=>$content) {
        echo '<h3>' . esc_html(strtoupper(str_replace("_", " ", $platform))) . '</h3>';
        echo '<textarea id="smos-campaign-' . esc_attr($platform) . '" rows="12" style="width:100%;">' . esc_textarea($content) . '</textarea>';
        echo '<p><button type="button" class="button" onclick="smosCopyById(\'smos-campaign-' . esc_attr($platform) . '\')">Copy</button></p>';
    }

    echo '</div>';
}
