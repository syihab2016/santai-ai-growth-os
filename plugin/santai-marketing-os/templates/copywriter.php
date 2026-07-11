<?php
$products = get_posts(array('post_type'=>'smos_product','post_status'=>'publish','numberposts'=>-1,'orderby'=>'title','order'=>'ASC'));
$selected_product = $selected_product_id ?: ($products[0]->ID ?? 0);

$platforms = smos_get_platforms();
$templates = smos_get_templates();
$lengths = smos_get_lengths();
$emoji_levels = smos_get_emoji_levels();
$cta_types = smos_get_cta_types();

function smos_field_selected_v04($key, $default = '') {
    return $_POST[$key] ?? $default;
}

$chosen_platforms = $_POST['platforms'] ?? array('facebook','instagram','telegram','whatsapp','youtube_community');
?>
<div class="wrap smos-wrap">
    <h1>AI Copywriter</h1>

    <?php if (!empty($error)) : ?>
        <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
    <?php endif; ?>

    <form method="post" class="smos-card">
        <?php wp_nonce_field('smos_copywriter_nonce'); ?>

        <div class="smos-grid">
            <div>
                <label>Produk</label>
                <select name="product_id">
                    <?php foreach ($products as $product) : ?>
                        <option value="<?php echo esc_attr($product->ID); ?>" <?php selected($selected_product, $product->ID); ?>>
                            <?php echo esc_html($product->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Template</label>
                <select name="template">
                    <?php foreach ($templates as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected(smos_field_selected_v04('template','soft_sell'), $key); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Panjang Penulisan</label>
                <select name="length">
                    <?php foreach ($lengths as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected(smos_field_selected_v04('length','medium'), $key); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Emoji</label>
                <select name="emoji">
                    <?php foreach ($emoji_levels as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected(smos_field_selected_v04('emoji','low'), $key); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>CTA</label>
                <select name="cta_type">
                    <?php foreach ($cta_types as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected(smos_field_selected_v04('cta_type','website'), $key); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <label>Platform</label>
        <div class="smos-platforms">
            <?php foreach ($platforms as $key => $label) : ?>
                <label class="smos-check">
                    <input type="checkbox" name="platforms[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $chosen_platforms)); ?>>
                    <?php echo esc_html($label); ?>
                </label>
            <?php endforeach; ?>
        </div>

        <label>Angle Kempen</label>
        <input type="text" name="angle" value="<?php echo esc_attr(smos_field_selected_v04('angle','Sayang Nabi dengan ilmu, bukan sekadar slogan')); ?>">

        <label>Arahan Tambahan</label>
        <textarea name="extra_instruction" rows="4"><?php echo esc_textarea(smos_field_selected_v04('extra_instruction','')); ?></textarea>

        <p><button type="submit" name="smos_generate_content" class="button button-primary button-hero">Generate Semua Platform</button></p>
    </form>

    <?php if (!empty($outputs)) : ?>
        <h2>Generated Content</h2>
        <?php foreach ($outputs as $platform_key => $item) : ?>
            <div class="smos-card">
                <h2><?php echo esc_html($item['label']); ?></h2>

                <?php if (!empty($item['error'])) : ?>
                    <div class="notice notice-error"><p><?php echo esc_html($item['error']); ?></p></div>
                <?php else : ?>
                    <textarea id="smos-output-<?php echo esc_attr($platform_key); ?>" rows="14" style="width:100%;"><?php echo esc_textarea($item['content']); ?></textarea>
                    <p>
                        <button type="button" class="button button-primary" onclick="smosCopyById('smos-output-<?php echo esc_attr($platform_key); ?>')">Copy <?php echo esc_html($item['label']); ?></button>
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <p class="description">Semua content telah disimpan automatik dalam Generated Posts.</p>
    <?php endif; ?>
</div>
