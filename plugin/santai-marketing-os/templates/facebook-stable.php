<div class="wrap smos-wrap">
    <h1>Facebook Connector</h1>

    <?php if (!empty($_GET['tested'])) : ?>
        <div class="notice notice-success"><p>Facebook Page Access Token sah dan sepadan dengan Page ID.</p></div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])) : ?>
        <div class="notice notice-error"><p><?php echo esc_html(wp_unslash($_GET['error'])); ?></p></div>
    <?php endif; ?>

    <div class="smos-card">
        <h2>Facebook Page Connection</h2>
        <p>Anda boleh masukkan sama ada <strong>User Access Token</strong> atau <strong>Page Access Token</strong>. Jika User Token dimasukkan, plugin akan mendapatkan Page Token yang betul melalui <code>/me/accounts</code>.</p>

        <form method="post">
            <?php wp_nonce_field('smos_save_facebook_manual_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">Page Name</th>
                    <td><input type="text" name="smos_facebook_page_name" value="<?php echo esc_attr($page_name); ?>" style="width:520px;"></td>
                </tr>
                <tr>
                    <th scope="row">Page ID</th>
                    <td><input type="text" name="smos_facebook_page_id" value="<?php echo esc_attr($page_id); ?>" style="width:520px;" required></td>
                </tr>
                <tr>
                    <th scope="row">Access Token</th>
                    <td>
                        <textarea name="smos_facebook_access_token" rows="5" style="width:720px;"></textarea>
                        <p class="description">
                            <?php echo $token_exists ? 'Page Token sudah disimpan. Biarkan kosong untuk kekalkan token sedia ada.' : 'Paste User Token atau Page Token.'; ?>
                        </p>
                    </td>
                </tr>
            </table>

            <p><button type="submit" name="smos_save_facebook_manual" class="button button-primary">Save & Validate Token</button></p>
        </form>
    </div>

    <div class="smos-card">
        <h2>Connection Health</h2>

        <p><strong>Status:</strong>
            <?php if ($status === 'connected') : ?>
                <span class="smos-status smos-status-ready">Connected</span>
            <?php elseif ($status === 'error') : ?>
                <span class="smos-status smos-status-danger">Error</span>
            <?php else : ?>
                <span class="smos-status smos-status-warning">Not Tested</span>
            <?php endif; ?>
        </p>

        <?php if ($token_source) : ?>
            <p><strong>Token Source:</strong> <?php echo esc_html($token_source === 'page_token' ? 'Page Access Token' : 'Converted from User Access Token'); ?></p>
        <?php endif; ?>

        <?php if ($last_test) : ?>
            <p><strong>Last Test:</strong> <?php echo esc_html($last_test); ?></p>
        <?php endif; ?>

        <?php if ($last_error) : ?>
            <p style="color:#b32d2e;"><strong>Last Error:</strong> <?php echo esc_html($last_error); ?></p>
        <?php endif; ?>

        <p>
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=smos_facebook_test_manual'), 'smos_facebook_test_manual')); ?>" class="button button-primary">
                Test Page Token
            </a>
        </p>
    </div>

    <div class="smos-card">
        <h2>Facebook API Log</h2>

        <?php if (empty($logs)) : ?>
            <p>Belum ada log.</p>
        <?php else : ?>
            <table class="widefat striped">
                <thead><tr><th>Time</th><th>Method</th><th>Endpoint</th><th>HTTP</th><th>Result</th></tr></thead>
                <tbody>
                    <?php foreach ($logs as $log) : ?>
                        <tr>
                            <td><?php echo esc_html($log['time']); ?></td>
                            <td><?php echo esc_html($log['method']); ?></td>
                            <td><code><?php echo esc_html($log['path']); ?></code></td>
                            <td><?php echo intval($log['code']); ?></td>
                            <td><?php echo esc_html($log['message']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p><a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=smos_facebook_clear_logs'), 'smos_facebook_clear_logs')); ?>">Clear Logs</a></p>
        <?php endif; ?>
    </div>
</div>
