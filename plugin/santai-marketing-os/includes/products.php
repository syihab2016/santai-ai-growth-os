<?php
if (!defined('ABSPATH')) exit;

add_action('init', 'smos_register_product_cpt');
add_action('add_meta_boxes', 'smos_product_metaboxes');
add_action('save_post_smos_product', 'smos_save_product_meta');

function smos_register_product_cpt()
{
    register_post_type('smos_product', array(
        'labels' => array(
            'name' => 'Products',
            'singular_name' => 'Product',
            'add_new_item' => 'Add New Product',
            'edit_item' => 'Edit Product',
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'smos-dashboard',
        'menu_icon' => 'dashicons-products',
        'supports' => array('title'),
    ));
}

function smos_product_metaboxes()
{
    add_meta_box('smos_product_info', 'Product Knowledge', 'smos_product_info_box', 'smos_product', 'normal', 'high');
}

function smos_product_info_box($post)
{
    wp_nonce_field('smos_save_product', 'smos_product_nonce');

    $fields = smos_product_fields();

    echo '<div class="smos-product-fields">';
    echo '<h2>Product Info</h2>';

    foreach ($fields as $key => $field) {
        $value = get_post_meta($post->ID, '_smos_' . $key, true);

        if (!empty($field['section'])) {
            echo '<hr><h2>' . esc_html($field['section']) . '</h2>';
        }

        echo '<p><label><strong>' . esc_html($field['label']) . '</strong></label>';

        if ($field['type'] === 'textarea') {
            echo '<textarea name="smos_' . esc_attr($key) . '" rows="5" style="width:100%;">' . esc_textarea($value) . '</textarea>';
        } else {
            $input_type = !empty($field['input_type']) ? $field['input_type'] : 'text';
            echo '<input type="' . esc_attr($input_type) . '" name="smos_' . esc_attr($key) . '" value="' . esc_attr($value) . '" style="width:100%;">';
        }

        if (!empty($field['description'])) {
            echo '<span class="description">' . esc_html($field['description']) . '</span>';
        }

        echo '</p>';
    }

    echo '</div>';
}

function smos_product_fields()
{
    $fields = array(
        'normal_price' => array('label' => 'Harga Asal', 'type' => 'text'),
        'promo_price' => array('label' => 'Harga Promosi', 'type' => 'text'),
        'product_url' => array('label' => 'Link Produk', 'type' => 'text', 'input_type' => 'url'),
        'whatsapp_url' => array('label' => 'Link WhatsApp', 'type' => 'text', 'input_type' => 'url'),

        'product_image_url' => array(
            'label' => 'Image URL 1',
            'type' => 'text',
            'input_type' => 'url',
            'section' => 'Media Assets',
            'description' => 'Direct URL gambar. Contoh: https://domain.com/uploads/gambar.jpg'
        ),
        'product_image_url_2' => array('label' => 'Image URL 2', 'type' => 'text', 'input_type' => 'url'),
        'product_image_url_3' => array('label' => 'Image URL 3', 'type' => 'text', 'input_type' => 'url'),
        'product_image_url_4' => array('label' => 'Image URL 4', 'type' => 'text', 'input_type' => 'url'),
        'product_image_url_5' => array('label' => 'Image URL 5', 'type' => 'text', 'input_type' => 'url'),

        'short_video_url_1' => array(
            'label' => 'Short Video URL 1',
            'type' => 'text',
            'input_type' => 'url',
            'description' => 'Direct URL video MP4 pendek. Contoh: https://domain.com/uploads/video.mp4'
        ),
        'short_video_url_2' => array('label' => 'Short Video URL 2', 'type' => 'text', 'input_type' => 'url'),
        'short_video_url_3' => array('label' => 'Short Video URL 3', 'type' => 'text', 'input_type' => 'url'),
        'short_video_url_4' => array('label' => 'Short Video URL 4', 'type' => 'text', 'input_type' => 'url'),
        'short_video_url_5' => array('label' => 'Short Video URL 5', 'type' => 'text', 'input_type' => 'url'),

        'short_desc' => array('label' => 'Deskripsi Pendek', 'type' => 'textarea', 'section' => 'Product Knowledge'),
        'usp' => array('label' => 'USP / Kelebihan', 'type' => 'textarea'),
        'pain_points' => array('label' => 'Pain Points', 'type' => 'textarea'),
        'target_audience' => array('label' => 'Target Pembeli', 'type' => 'textarea'),
        'faq' => array('label' => 'FAQ', 'type' => 'textarea'),
        'reviews' => array('label' => 'Review/Testimoni', 'type' => 'textarea'),
        'hashtags' => array('label' => 'Hashtag', 'type' => 'textarea'),
        'cta' => array('label' => 'CTA', 'type' => 'text'),
        'notes' => array('label' => 'Nota Tambahan', 'type' => 'textarea')
    );

    return $fields;
}

function smos_save_product_meta($post_id)
{
    if (!isset($_POST['smos_product_nonce']) || !wp_verify_nonce($_POST['smos_product_nonce'], 'smos_save_product')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    foreach (smos_product_fields() as $field => $config) {
        $value = $_POST['smos_' . $field] ?? '';

        if (!empty($config['input_type']) && $config['input_type'] === 'url') {
            update_post_meta($post_id, '_smos_' . $field, esc_url_raw($value));
        } else {
            update_post_meta($post_id, '_smos_' . $field, sanitize_textarea_field($value));
        }
    }
}

function smos_seed_default_product()
{
    $existing = get_page_by_title('Kombo Sayang Nabi', OBJECT, 'smos_product');
    if ($existing) return $existing->ID;

    $post_id = wp_insert_post(array(
        'post_title' => 'Kombo Sayang Nabi',
        'post_type' => 'smos_product',
        'post_status' => 'publish'
    ));

    if ($post_id) {
        update_post_meta($post_id, '_smos_normal_price', 'RM168');
        update_post_meta($post_id, '_smos_promo_price', 'RM139');
        update_post_meta($post_id, '_smos_product_url', 'https://www.bukusantaiilmu.com/product/kombo-sayang-nabi/');
        update_post_meta($post_id, '_smos_short_desc', 'Kombo Sayang Nabi menghimpunkan buku pilihan untuk membantu pembaca mengenali Rasulullah SAW melalui sirah yang sahih, memahami hadis mengikut konteks dan mengamalkan sunnah dengan ilmu.');
        update_post_meta($post_id, '_smos_usp', "Menggabungkan bacaan sirah dan hadis dalam satu kombo.\nSesuai untuk pembaca awam, pelajar, guru dan pendakwah.\nMembantu memahami Nabi SAW dengan kefahaman yang berasaskan ilmu.\nHarga promosi lebih jimat berbanding beli berasingan.");
        update_post_meta($post_id, '_smos_pain_points', "Ramai mahu mencintai Nabi SAW tetapi tidak tahu buku yang sesuai.\nBanyak kisah sirah tersebar tanpa semakan.\nSebahagian hadis disalah fahami kerana tidak diketahui konteksnya.");
        update_post_meta($post_id, '_smos_target_audience', "Pencinta sirah Nabi, guru, pelajar, pendakwah, ibu bapa dan pembeli hadiah buku Islam.");
        update_post_meta($post_id, '_smos_faq', "Adakah sesuai untuk orang awam? Ya, sesuai untuk pembaca umum.\nAdakah sesuai untuk hadiah? Ya, sangat sesuai sebagai hadiah buku Islam.");
        update_post_meta($post_id, '_smos_hashtags', "#SantaiIlmu #SayangNabi #SirahNabi #BukuIslam #Hadis");
        update_post_meta($post_id, '_smos_cta', 'Dapatkan Kombo Sayang Nabi pada harga promosi RM139 sekarang.');
        update_post_meta($post_id, '_smos_notes', 'Fokus kepada cinta Nabi dengan ilmu, bukan sekadar slogan.');
    }

    return $post_id;
}

function smos_get_product_knowledge($product_id)
{
    return array(
        'name' => get_the_title($product_id),
        'normal_price' => get_post_meta($product_id, '_smos_normal_price', true),
        'promo_price' => get_post_meta($product_id, '_smos_promo_price', true),
        'product_url' => get_post_meta($product_id, '_smos_product_url', true),
        'product_image_url' => get_post_meta($product_id, '_smos_product_image_url', true),
        'whatsapp_url' => get_post_meta($product_id, '_smos_whatsapp_url', true),
        'short_desc' => get_post_meta($product_id, '_smos_short_desc', true),
        'usp' => get_post_meta($product_id, '_smos_usp', true),
        'pain_points' => get_post_meta($product_id, '_smos_pain_points', true),
        'target_audience' => get_post_meta($product_id, '_smos_target_audience', true),
        'faq' => get_post_meta($product_id, '_smos_faq', true),
        'reviews' => get_post_meta($product_id, '_smos_reviews', true),
        'hashtags' => get_post_meta($product_id, '_smos_hashtags', true),
        'cta' => get_post_meta($product_id, '_smos_cta', true),
        'notes' => get_post_meta($product_id, '_smos_notes', true),
    );
}

function smos_get_product_media_urls($product_id, $type = 'all')
{
    $images = array();
    $videos = array();

    $image_keys = array('product_image_url', 'product_image_url_2', 'product_image_url_3', 'product_image_url_4', 'product_image_url_5');
    foreach ($image_keys as $key) {
        $url = trim(get_post_meta($product_id, '_smos_' . $key, true));
        if ($url) $images[] = array('type' => 'image', 'url' => esc_url_raw($url));
    }

    for ($i = 1; $i <= 5; $i++) {
        $url = trim(get_post_meta($product_id, '_smos_short_video_url_' . $i, true));
        if ($url) $videos[] = array('type' => 'video', 'url' => esc_url_raw($url));
    }

    if ($type === 'image') return $images;
    if ($type === 'video') return $videos;

    return array_merge($images, $videos);
}
