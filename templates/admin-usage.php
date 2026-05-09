<?php
/**
 * Template for the ChatBudgie admin usage page
 */

if (!defined('ABSPATH')) {
    exit;
}

// $usage_data is passed from the controller
$chatbudgie_usage_rows = isset($usage_data['content']) ? $usage_data['content'] : [];
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
                        <?php if (empty($chatbudgie_usage_rows)) : ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <?php echo esc_html__('No request logs found.', 'chatbudgie'); ?>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($chatbudgie_usage_rows as $chatbudgie_row) : ?>
                                <?php
                                $chatbudgie_create_time = wp_date(
                                    get_option('date_format') . ' ' . get_option('time_format'),
                                    strtotime($chatbudgie_row['createTime'])
                                );
                                $chatbudgie_time_cost = isset($chatbudgie_row['timeCost']) ? number_format($chatbudgie_row['timeCost'] / 1000, 2) . 's' : '-';
                                ?>
                                <tr>
                                    <td><?php echo esc_html($chatbudgie_create_time); ?></td>
                                    <td><?php echo esc_html($chatbudgie_row['appName'] ?: '-'); ?></td>
                                    <td><?php echo esc_html($chatbudgie_row['referer'] ?: '-'); ?></td>
                                    <td><?php echo esc_html($chatbudgie_row['type'] ?: '-'); ?></td>
                                    <td><?php echo esc_html($chatbudgie_row['tokenCost'] ?: '-'); ?></td>
                                    <td><?php echo esc_html($chatbudgie_time_cost); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (isset($usage_data['page']['totalPages']) && $usage_data['page']['totalPages'] > 1) : ?>
                <div class="usage-pagination">
                    <?php
                    $chatbudgie_current_page = $usage_data['page']['number'] + 1;
                    $chatbudgie_total_pages = $usage_data['page']['totalPages'];
                    
                    $chatbudgie_prev_url = add_query_arg('paged', max(1, $chatbudgie_current_page - 1));
                    $chatbudgie_next_url = add_query_arg('paged', min($chatbudgie_total_pages, $chatbudgie_current_page + 1));
                    
                    $chatbudgie_has_prev = $chatbudgie_current_page > 1;
                    $chatbudgie_has_next = $chatbudgie_current_page < $chatbudgie_total_pages;
                    ?>
                    
                    <a href="<?php echo $chatbudgie_has_prev ? esc_url($chatbudgie_prev_url) : 'javascript:void(0);'; ?>" 
                       class="cb-btn cb-btn--ghost cb-btn--sm <?php echo esc_attr($chatbudgie_has_prev ? '' : 'is-disabled'); ?>"
                       aria-label="<?php echo esc_attr__('Previous Page', 'chatbudgie'); ?>">
                        <?php echo esc_html__('&laquo; Previous', 'chatbudgie'); ?>
                    </a>

                    <span class="pagination-info">
                        <?php 
                        /* translators: 1: current page number, 2: total number of pages */
                        printf(esc_html__('Page %1$d of %2$d', 'chatbudgie'), 
                            absint($chatbudgie_current_page), 
                            absint($chatbudgie_total_pages)
                        ); 
                        ?>
                    </span>

                    <a href="<?php echo $chatbudgie_has_next ? esc_url($chatbudgie_next_url) : 'javascript:void(0);'; ?>" 
                       class="cb-btn cb-btn--ghost cb-btn--sm <?php echo esc_attr($chatbudgie_has_next ? '' : 'is-disabled'); ?>"
                       aria-label="<?php echo esc_attr__('Next Page', 'chatbudgie'); ?>">
                        <?php echo esc_html__('Next &raquo;', 'chatbudgie'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </section>

        <?php include CHATBUDGIE_PLUGIN_DIR . 'templates/admin-support-footer.php'; ?>
    </main>
</div>
