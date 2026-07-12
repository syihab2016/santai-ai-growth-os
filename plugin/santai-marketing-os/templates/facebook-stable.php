<div class="wrap smos-wrap">
    <h1>Facebook Manager</h1>

    <?php
    $redirect_notice = isset($_GET['smos_fb_notice']) ? sanitize_text_field(wp_unslash($_GET['smos_fb_notice'])) : '';
    $redirect_type = isset($_GET['smos_fb_notice_type']) ? sanitize_key($_GET['smos_fb_notice_type']) : '';
    if ($redirect_notice) {
        $class = $redirect_type === 'error' ? 'notice-error' : 'notice-success';
        echo '<div class="notice ' . esc_attr($class) . '"><p>' . esc_html($redirect_notice) . '</p></div>';
    }
    if ($notice) echo '<div class="notice notice-success"><p>' . esc_html($notice) . '</p></div>';
    if ($error) echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
    ?>

    <div class="smos-card">
        <h2>Facebook OAuth Login</h2>
        <p>Sambungkan Facebook melalui aliran OAuth rasmi. Selepas login, sistem akan menyemak permission dan menemui semua Facebook Page secara automatik.</p>

        <form method="post">
            <?php wp_nonce_field('smos_facebook_oauth_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">App ID</th>
                    <td><input type="text" name="smos_facebook_app_id" value="<?php echo esc_attr($facebook_oauth_config['app_id']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row">App Secret</th>
                    <td>
                        <input type="password" name="smos_facebook_app_secret" value="" class="regular-text" autocomplete="new-password">
                        <p class="description"><?php echo $facebook_oauth_config['app_secret'] ? 'App Secret sudah disimpan. Biarkan kosong untuk kekalkan nilai sedia ada.' : 'Masukkan App Secret daripada Meta App Settings → Basic.'; ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Configuration ID</th>
                    <td>
                        <input type="text" name="smos_facebook_configuration_id" value="<?php echo esc_attr($facebook_oauth_config['configuration_id']); ?>" class="regular-text">
                        <p class="description">Configuration Facebook Login for Business yang mengandungi pages_show_list, pages_read_engagement, pages_manage_posts dan business_management.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">OAuth Redirect URI</th>
                    <td>
                        <input type="url" name="smos_facebook_oauth_redirect_uri" value="<?php echo esc_attr($facebook_oauth_config['redirect_uri']); ?>" class="large-text">
                        <p class="description">Salin URL ini tepat ke Meta App → Facebook Login for Business → Valid OAuth Redirect URIs. Untuk LocalWP gunakan HTTPS Live Link, bukan alamat <code>.local</code> biasa.</p>
                    </td>
                </tr>
            </table>
            <p><button type="submit" name="smos_save_facebook_oauth_settings" class="button button-secondary">Save OAuth Settings</button></p>
        </form>

        <hr>

        <?php if ($user_token_exists) : ?>
            <p><strong>Status:</strong> <span class="smos-status smos-status-ready">Connected</span></p>
            <?php if ($facebook_oauth_connected_at) : ?><p><strong>Connected:</strong> <?php echo esc_html($facebook_oauth_connected_at); ?></p><?php endif; ?>
            <?php if ($facebook_token_expires_at) : ?><p><strong>Token expiry:</strong> <?php echo esc_html(wp_date('Y-m-d H:i:s', $facebook_token_expires_at)); ?></p><?php endif; ?>
            <?php if (!empty($facebook_granted_permissions)) : ?><p><strong>Granted:</strong> <?php echo esc_html(implode(', ', $facebook_granted_permissions)); ?></p><?php endif; ?>
            <?php if (!empty($facebook_missing_permissions)) : ?><p style="color:#b32d2e;"><strong>Missing:</strong> <?php echo esc_html(implode(', ', $facebook_missing_permissions)); ?></p><?php endif; ?>
        <?php else : ?>
            <p><strong>Status:</strong> <span class="smos-status smos-status-warning">Not Connected</span></p>
        <?php endif; ?>

        <p>
            <a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=smos_facebook_oauth_start'), 'smos_facebook_oauth_start')); ?>">Connect / Reconnect Facebook</a>
            <?php if ($user_token_exists) : ?>
                <a class="button" onclick="return confirm('Putuskan Facebook dan buang semua Page tersimpan?');" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=smos_facebook_oauth_disconnect'), 'smos_facebook_oauth_disconnect')); ?>">Disconnect</a>
            <?php endif; ?>
        </p>
    </div>

    <div class="smos-card">
        <h2>Manual Token Fallback</h2>
        <p>Gunakan bahagian ini hanya untuk debugging. Workflow utama ialah butang <strong>Connect / Reconnect Facebook</strong> di atas.</p>

        <p>Masukkan satu <strong>User Access Token</strong>. Sistem akan mendapatkan semua Facebook Page dan Page Access Token melalui <code>/me/accounts</code>.</p>

        <form method="post">
            <?php wp_nonce_field('smos_facebook_discovery_nonce'); ?>

            <p>
                <label for="smos_facebook_user_access_token"><strong>User Access Token</strong></label><br>
                <textarea id="smos_facebook_user_access_token" name="smos_facebook_user_access_token" rows="5" style="width:100%;max-width:900px;"></textarea>
            </p>

            <p class="description">
                <?php echo $user_token_exists ? 'User Access Token sudah disimpan. Biarkan kosong untuk menggunakan token sedia ada dan klik Discover My Pages.' : 'Token tidak akan dipaparkan semula selepas disimpan.'; ?>
            </p>

            <p><button type="submit" name="smos_discover_facebook_pages" class="button button-primary">Discover My Pages</button></p>
        </form>
    </div>

    <?php if (!empty($discovered_pages)) : ?>
        <?php
        $already_connected = array_map(function ($page) {
            return (string) ($page['page_id'] ?? '');
        }, $connected_pages);
        ?>
        <div class="smos-card">
            <h2>Pages Discovered</h2>
            <p>Pilih Page yang hendak digunakan dalam Santai AI Growth OS.</p>

            <form method="post">
                <?php wp_nonce_field('smos_facebook_discovery_nonce'); ?>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th style="width:60px;">Pilih</th>
                            <th>Page Name</th>
                            <th>Page ID</th>
                            <th>Tasks</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($discovered_pages as $page) : ?>
                        <?php $is_connected = in_array((string) $page['page_id'], $already_connected, true); ?>
                        <tr>
                            <td><input type="checkbox" name="smos_selected_page_ids[]" value="<?php echo esc_attr($page['page_id']); ?>" <?php checked($is_connected); ?>></td>
                            <td><strong><?php echo esc_html($page['name']); ?></strong></td>
                            <td><code><?php echo esc_html($page['page_id']); ?></code></td>
                            <td><?php echo esc_html(implode(', ', $page['tasks'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <p><button type="submit" name="smos_save_selected_facebook_pages" class="button button-primary">Save Selected Pages</button></p>
            </form>
        </div>
    <?php endif; ?>

    <div class="smos-card">
        <h2>Connected Pages</h2>

        <?php if (empty($connected_pages)) : ?>
            <p>Belum ada Facebook Page disambungkan.</p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Page ID</th>
                        <th>Status</th>
                        <th>Last Test</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($connected_pages as $page) : ?>
                    <?php
                    $status = $page['status'] ?? 'not_tested';
                    $test_url = wp_nonce_url(
                        admin_url('admin-post.php?action=smos_facebook_test_page&page_id=' . rawurlencode($page['page_id'])),
                        'smos_facebook_test_page'
                    );
                    $remove_url = wp_nonce_url(
                        admin_url('admin-post.php?action=smos_facebook_remove_page&page_id=' . rawurlencode($page['page_id'])),
                        'smos_facebook_remove_page'
                    );
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($page['name']); ?></strong></td>
                        <td><code><?php echo esc_html($page['page_id']); ?></code></td>
                        <td>
                            <?php if ($status === 'connected') : ?>
                                <span class="smos-status smos-status-ready">Connected</span>
                            <?php else : ?>
                                <span class="smos-status smos-status-danger">Error</span>
                            <?php endif; ?>
                            <?php if (!empty($page['last_error'])) : ?>
                                <p style="color:#b32d2e;"><?php echo esc_html($page['last_error']); ?></p>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($page['last_test'] ?? '—'); ?></td>
                        <td>
                            <a class="button" href="<?php echo esc_url($test_url); ?>">Test</a>
                            <a class="button" href="<?php echo esc_url($remove_url); ?>" onclick="return confirm('Buang Facebook Page ini?');">Remove</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
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
