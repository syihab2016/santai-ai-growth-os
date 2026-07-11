<div class="wrap smos-wrap">
    <h1>Product Importer</h1>

    <div class="smos-card">
        <h2>Import Product Knowledge CSV</h2>
        <p>Gunakan fail <code>smos-products-import.csv</code> yang kita hasilkan daripada WooCommerce export.</p>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('smos_product_import_nonce'); ?>

            <p>
                <label><strong>CSV File</strong></label>
                <input type="file" name="smos_import_csv" accept=".csv,text/csv" required>
            </p>

            <p>
                <label class="smos-check">
                    <input type="checkbox" name="smos_update_existing" value="1" checked>
                    Update existing products
                </label>
            </p>

            <p>
                <label class="smos-check">
                    <input type="checkbox" name="smos_skip_empty" value="1" checked>
                    Jangan overwrite field sedia ada jika cell CSV kosong
                </label>
            </p>

            <p>
                <label class="smos-check">
                    <input type="checkbox" name="smos_dry_run" value="1">
                    Dry run sahaja
                </label>
            </p>

            <p>
                <button type="submit" name="smos_run_product_import" class="button button-primary button-hero">Import Products</button>
            </p>
        </form>
    </div>

    <?php if (!empty($result)) : ?>
        <div class="smos-card">
            <h2>Import Result</h2>

            <?php if (!empty($result['success'])) : ?>
                <div class="notice notice-success inline">
                    <p><?php echo esc_html($result['message']); ?></p>
                </div>
            <?php else : ?>
                <div class="notice notice-error inline">
                    <p><?php echo esc_html($result['message']); ?></p>
                </div>
            <?php endif; ?>

            <table class="widefat striped" style="max-width:620px;">
                <tbody>
                    <tr><th>Created</th><td><?php echo intval($result['created']); ?></td></tr>
                    <tr><th>Updated</th><td><?php echo intval($result['updated']); ?></td></tr>
                    <tr><th>Skipped</th><td><?php echo intval($result['skipped']); ?></td></tr>
                </tbody>
            </table>

            <?php if (!empty($result['errors'])) : ?>
                <h3>Notes / Errors</h3>
                <ul>
                    <?php foreach (array_slice($result['errors'], 0, 50) as $error) : ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="smos-card">
        <h2>Expected CSV Columns</h2>
        <p>Importer menyokong column berikut:</p>

        <pre style="white-space:pre-wrap;background:#f6f7f7;padding:14px;border:1px solid #dcdcde;">post_title, woo_id, sku, isbn,
_smos_normal_price, _smos_promo_price, _smos_product_url, _smos_whatsapp_url,
_smos_product_image_url, _smos_product_image_url_2, _smos_product_image_url_3, _smos_product_image_url_4, _smos_product_image_url_5,
_smos_short_video_url_1, _smos_short_video_url_2, _smos_short_video_url_3, _smos_short_video_url_4, _smos_short_video_url_5,
_smos_short_desc, _smos_usp, _smos_pain_points, _smos_target_audience,
_smos_faq, _smos_reviews, _smos_hashtags, _smos_cta, _smos_notes</pre>
    </div>
</div>
