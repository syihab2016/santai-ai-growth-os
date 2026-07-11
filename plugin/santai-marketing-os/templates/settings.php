<?php
$api_key = get_option('smos_openai_api_key', '');
$model = get_option('smos_openai_model', 'gpt-4.1-mini');
$brand_voice = get_option('smos_brand_voice', "Gaya penulisan Syihabudin Ahmad:
- Boleh bermula dengan 'Sahabat-sahabat,'
- Berilmu, jelas, mesra, tegas tetapi beradab.
- Gunakan logik dan kesedaran agama.
- Sambungkan promosi dengan ilmu secara natural.
- CTA lembut tetapi jelas.
- Tidak terlalu korporat atau terlalu skema.");
$default_cta_url = get_option('smos_default_cta_url', '');
?>
<div class="wrap smos-wrap">
    <h1>Settings</h1>

    <form method="post" class="smos-card">
        <?php wp_nonce_field('smos_settings_nonce'); ?>

        <h2>OpenAI</h2>

        <table class="form-table">
            <tr>
                <th scope="row">OpenAI API Key</th>
                <td><input type="password" name="smos_openai_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" style="width:620px;"></td>
            </tr>
            <tr>
                <th scope="row">Model</th>
                <td>
                    <input type="text" name="smos_openai_model" value="<?php echo esc_attr($model); ?>" class="regular-text">
                    <p class="description">Cadangan: gpt-4.1-mini</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Default CTA URL</th>
                <td><input type="url" name="smos_default_cta_url" value="<?php echo esc_attr($default_cta_url); ?>" class="regular-text" style="width:620px;"></td>
            </tr>
            <tr>
                <th scope="row">Brand Voice</th>
                <td><textarea name="smos_brand_voice" rows="10" style="width:720px;"><?php echo esc_textarea($brand_voice); ?></textarea></td>
            </tr>
        </table>

        <p><button type="submit" name="smos_save_settings" class="button button-primary">Save Settings</button></p>
    </form>

    <div class="smos-card">
        <h2>Facebook</h2>
        <p>Facebook kini diuruskan hanya melalui menu <strong>Facebook Connector</strong> supaya token tidak bertindih atau terpadam.</p>
    </div>
</div>
