<div class="wrap smos-wrap">
<h1>Facebook Connector</h1>

<?php if (!empty($_GET['smos_connected'])) : ?><div class="notice notice-success"><p>Facebook berjaya disambungkan.</p></div><?php endif; ?>
<?php if (!empty($_GET['smos_tested'])) : ?><div class="notice notice-success"><p>Facebook connection berjaya diuji.</p></div><?php endif; ?>
<?php if (!empty($_GET['smos_disconnected'])) : ?><div class="notice notice-success"><p>Facebook telah diputuskan.</p></div><?php endif; ?>
<?php if (!empty($_GET['smos_error'])) : ?><div class="notice notice-error"><p><?php echo esc_html(wp_unslash($_GET['smos_error'])); ?></p></div><?php endif; ?>

<div class="smos-card">
<h2>Meta App Configuration</h2>
<form method="post">
<?php wp_nonce_field('smos_save_facebook_app_nonce'); ?>
<table class="form-table">
<tr><th>App ID</th><td><input type="text" name="smos_facebook_app_id" value="<?php echo esc_attr($app_id); ?>" style="width:520px;"></td></tr>
<tr><th>App Secret</th><td><input type="password" name="smos_facebook_app_secret" value="" style="width:520px;" autocomplete="new-password"><p class="description"><?php echo $app_secret_exists ? 'App Secret sudah disimpan. Biarkan kosong untuk kekalkan nilai sedia ada.' : 'Masukkan App Secret daripada Meta App Settings.'; ?></p></td></tr>
<tr><th>Configuration ID</th><td><input type="text" name="smos_facebook_config_id" value="<?php echo esc_attr($config_id); ?>" style="width:520px;"><p class="description">Ambil daripada Meta App → Facebook Login for Business → Configurations.</p></td></tr>

<tr><th>Valid OAuth Redirect URI</th><td><input type="text" readonly value="<?php echo esc_attr($redirect_uri); ?>" style="width:720px;background:#f6f7f7;"><p class="description">Salin URL ini ke Meta App → Facebook Login → Settings → Valid OAuth Redirect URIs.</p></td></tr>
</table>
<p><button type="submit" name="smos_save_facebook_app" class="button button-primary">Save App Settings</button></p>
</form>
</div>

<div class="smos-card">
<h2>Connection Status</h2>
<p><strong>Status:</strong>
<?php if ($status==='connected') : ?><span class="smos-status smos-status-ready">Connected</span>
<?php elseif ($status==='error') : ?><span class="smos-status smos-status-danger">Error</span>
<?php else : ?><span class="smos-status smos-status-warning">Disconnected</span><?php endif; ?>
</p>
<?php if ($last_test) : ?><p><strong>Last Test:</strong> <?php echo esc_html($last_test); ?></p><?php endif; ?>
<?php if ($last_error) : ?><p style="color:#b32d2e;"><strong>Last Error:</strong> <?php echo esc_html($last_error); ?></p><?php endif; ?>

<p>
<?php if ($status!=='connected') : ?>
<a class="button button-primary button-hero" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=smos_facebook_oauth_start'),'smos_facebook_oauth_start')); ?>">Connect Facebook</a>
<?php else : ?>
<a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=smos_facebook_test_connection'),'smos_facebook_test_connection')); ?>">Test Connection</a>
<a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=smos_facebook_oauth_start'),'smos_facebook_oauth_start')); ?>">Reconnect</a>
<a class="button" onclick="return confirm('Disconnect Facebook?');" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=smos_facebook_disconnect'),'smos_facebook_disconnect')); ?>">Disconnect</a>
<?php endif; ?>
</p>
</div>

<div class="smos-card">
<h2>Connected Pages</h2>
<?php if (empty($pages)) : ?><p>Belum ada Page yang disambungkan.</p>
<?php else : ?>
<table class="widefat striped"><thead><tr><th>Page</th><th>Page ID</th><th>Instagram</th></tr></thead><tbody>
<?php foreach ($pages as $page) : ?>
<tr><td><strong><?php echo esc_html($page['name']); ?></strong></td><td><?php echo esc_html($page['id']); ?></td><td><?php echo !empty($page['instagram_id']) ? esc_html('@' . ($page['instagram_username'] ?: $page['instagram_id'])) : 'Tidak dipautkan'; ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</div>

<div class="smos-card">
<h2>Setup di Meta</h2>
<ol>
<li>Tambah Facebook Login pada Meta App.</li>
<li>Buka Facebook Login for Business → Configurations.</li>
<li>Cipta configuration dengan permission pages_show_list, pages_read_engagement dan pages_manage_posts.</li>
<li>Salin Configuration ID ke plugin.</li>
<li>Masukkan Valid OAuth Redirect URI seperti dipaparkan di atas.</li>
<li>Pastikan akaun anda ialah Admin atau Tester app semasa Development Mode.</li>
<li>Klik Connect Facebook.</li>
</ol>
<p><strong>Jangan kongsi App Secret atau access token dalam chat.</strong></p>
</div>
</div>
