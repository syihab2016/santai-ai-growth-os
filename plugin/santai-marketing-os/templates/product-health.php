<div class="wrap smos-wrap">
    <h1>Product Health</h1>

    <div class="smos-card">
        <h2>Product Knowledge Completeness</h2>

        <div class="smos-stats">
            <div class="smos-stat">
                <strong><?php echo intval(count($rows)); ?></strong>
                <span>Total Products</span>
            </div>
            <div class="smos-stat">
                <strong><?php echo intval($average_score); ?>%</strong>
                <span>Average Completeness</span>
            </div>
            <div class="smos-stat">
                <strong><?php echo intval($complete_count); ?></strong>
                <span>Ready</span>
            </div>
            <div class="smos-stat">
                <strong><?php echo intval($needs_work_count); ?></strong>
                <span>Need Work</span>
            </div>
        </div>
    </div>

    <div class="smos-card">
        <h2>Products</h2>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th style="width:28%;">Product</th>
                    <th style="width:12%;">Score</th>
                    <th style="width:12%;">Status</th>
                    <th style="width:10%;">Images</th>
                    <th style="width:10%;">Videos</th>
                    <th>Missing / Need Work</th>
                    <th style="width:10%;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)) : ?>
                    <tr>
                        <td colspan="7">Belum ada produk.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($rows as $row) : ?>
                        <?php
                        $health = $row['health'];
                        $status = $health['status'];
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($row['title']); ?></strong>
                            </td>
                            <td>
                                <div class="smos-progress">
                                    <span style="width:<?php echo intval($health['score']); ?>%;"></span>
                                </div>
                                <strong><?php echo intval($health['score']); ?>%</strong>
                            </td>
                            <td>
                                <span class="smos-status <?php echo esc_attr($status['class']); ?>">
                                    <?php echo esc_html($status['label']); ?>
                                </span>
                            </td>
                            <td><?php echo intval($health['image_count']); ?>/5</td>
                            <td><?php echo intval($health['video_count']); ?>/5</td>
                            <td>
                                <?php if (empty($health['missing'])) : ?>
                                    <span style="color:#008a20;">Lengkap</span>
                                <?php else : ?>
                                    <?php echo esc_html(implode(', ', array_slice($health['missing'], 0, 6))); ?>
                                    <?php if (count($health['missing']) > 6) : ?>
                                        ...
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a class="button" href="<?php echo esc_url(get_edit_post_link($row['id'])); ?>">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
