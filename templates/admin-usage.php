<?php
/**
 * Template for the ChatBudgie admin usage page
 */

if (!defined('ABSPATH')) {
    exit;
}

// $usage_data is passed from the controller
$usage_rows = isset($usage_data['content']) ? $usage_data['content'] : [];
?>
<div class="page page--settings page--usage">
    <?php include CHATBUDGIE_PLUGIN_DIR . 'templates/admin-header.php'; ?>

    <main class="settings usage" role="main">
        <section class="settings__hero" aria-labelledby="usage-title">
            <h1 id="usage-title" class="settings__title"><?php echo esc_html__('Token Usage', 'chatbudgie'); ?></h1>
            <p class="settings__sub"><?php echo esc_html__('Monitor your token consumption and request activity.', 'chatbudgie'); ?></p>
        </section>

        <?php
        include CHATBUDGIE_PLUGIN_DIR . 'templates/admin-account-summary.php';
        ?>

        <section class="settings-card" aria-labelledby="request-logs-title">
            <div class="settings-card__header settings-card__header--stack">
                <div>
                    <h2 id="request-logs-title" class="settings-card__title"><?php echo esc_html__('Request Logs', 'chatbudgie'); ?></h2>
                    <p class="settings-card__sub"><?php echo esc_html__('View a detailed history of your chatbot requests and token usage.', 'chatbudgie'); ?></p>
                </div>
                <button type="button" class="cb-btn cb-btn--ghost usage-toolbar__refresh" onclick="window.location.reload();">
                    <span class="cb-icon cb-icon--sm" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M21 12a9 9 0 1 1-2.64-6.36"></path>
                            <path d="M21 3v6h-6"></path>
                        </svg>
                    </span>
                    <?php echo esc_html__('Refresh', 'chatbudgie'); ?>
                </button>
            </div>

            <div class="usage-table-wrap">
                <table class="usage-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Time', 'chatbudgie'); ?></th>
                            <th><?php echo esc_html__('App Name', 'chatbudgie'); ?></th>
                            <th><?php echo esc_html__('Site', 'chatbudgie'); ?></th>
                            <th><?php echo esc_html__('Request Type', 'chatbudgie'); ?></th>
                            <th><?php echo esc_html__('Token Cost', 'chatbudgie'); ?></th>
                            <th><?php echo esc_html__('Time Cost', 'chatbudgie'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usage_rows)) : ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <?php echo esc_html__('No request logs found.', 'chatbudgie'); ?>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($usage_rows as $row) : ?>
                                <?php
                                $create_time = wp_date(
                                    get_option('date_format') . ' ' . get_option('time_format'),
                                    strtotime($row['createTime'])
                                );
                                $time_cost = isset($row['timeCost']) ? number_format($row['timeCost'] / 1000, 2) . 's' : '-';
                                ?>
                                <tr>
                                    <td><?php echo esc_html($create_time); ?></td>
                                    <td><?php echo esc_html($row['appName'] ?: '-'); ?></td>
                                    <td><?php echo esc_html($row['referer'] ?: '-'); ?></td>
                                    <td><?php echo esc_html($row['type'] ?: '-'); ?></td>
                                    <td><?php echo esc_html($row['tokenCost'] ?: '-'); ?></td>
                                    <td><?php echo esc_html($time_cost); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (isset($usage_data['page']['totalPages']) && $usage_data['page']['totalPages'] > 1) : ?>
                <div class="usage-pagination">
                    <?php
                    $current_page = $usage_data['page']['number'] + 1;
                    $total_pages = $usage_data['page']['totalPages'];
                    
                    $prev_url = add_query_arg('paged', max(1, $current_page - 1));
                    $next_url = add_query_arg('paged', min($total_pages, $current_page + 1));
                    
                    $has_prev = $current_page > 1;
                    $has_next = $current_page < $total_pages;
                    ?>
                    
                    <a href="<?php echo $has_prev ? esc_url($prev_url) : 'javascript:void(0);'; ?>" 
                       class="cb-btn cb-btn--ghost cb-btn--sm <?php echo !$has_prev ? 'is-disabled' : ''; ?>"
                       aria-label="<?php echo esc_attr__('Previous Page', 'chatbudgie'); ?>">
                        <?php echo esc_html__('&laquo; Previous', 'chatbudgie'); ?>
                    </a>

                    <span class="pagination-info">
                        <?php printf(esc_html__('Page %d of %d', 'chatbudgie'), $current_page, $total_pages); ?>
                    </span>

                    <a href="<?php echo $has_next ? esc_url($next_url) : 'javascript:void(0);'; ?>" 
                       class="cb-btn cb-btn--ghost cb-btn--sm <?php echo !$has_next ? 'is-disabled' : ''; ?>"
                       aria-label="<?php echo esc_attr__('Next Page', 'chatbudgie'); ?>">
                        <?php echo esc_html__('Next &raquo;', 'chatbudgie'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </section>

        <?php include CHATBUDGIE_PLUGIN_DIR . 'templates/admin-support-footer.php'; ?>
    </main>
</div>
