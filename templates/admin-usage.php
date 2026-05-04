<?php
/**
 * Template for the ChatBudgie admin usage page
 */

if (!defined('ABSPATH')) {
    exit;
}

$tokens = (int) get_option('chatbudgie_tokens', 1000);
$admin_email = get_option('admin_email', '');
$site_name = get_bloginfo('name');
$account_name = $site_name ? $site_name : __('ChatBudgie Workspace', 'chatbudgie');
$account_meta = $admin_email ? $admin_email : __('Connected via your WordPress admin account', 'chatbudgie');

$monthly_used_tokens = 874320;
$total_used_tokens = 2560000;
$remaining_tokens = max($tokens, 125680);

$usage_rows = array(
    array(
        'time' => __('May 26, 2024', 'chatbudgie'),
        'time_detail' => '09:42:15 AM',
        'app_name' => __('Support Assistant', 'chatbudgie'),
        'site' => 'help.chatbudgie.com',
        'request_type' => __('Chat Completion', 'chatbudgie'),
        'token_cost' => 1256,
        'time_cost' => '2.45s',
        'icon' => 'support',
    ),
    array(
        'time' => __('May 26, 2024', 'chatbudgie'),
        'time_detail' => '09:41:02 AM',
        'app_name' => __('Support Assistant', 'chatbudgie'),
        'site' => 'help.chatbudgie.com',
        'request_type' => __('Chat Completion', 'chatbudgie'),
        'token_cost' => 842,
        'time_cost' => '1.89s',
        'icon' => 'support',
    ),
    array(
        'time' => __('May 26, 2024', 'chatbudgie'),
        'time_detail' => '09:39:48 AM',
        'app_name' => __('AI Sales Bot', 'chatbudgie'),
        'site' => 'sales.chatbudgie.com',
        'request_type' => __('Chat Completion', 'chatbudgie'),
        'token_cost' => 2134,
        'time_cost' => '3.21s',
        'icon' => 'sales',
    ),
    array(
        'time' => __('May 26, 2024', 'chatbudgie'),
        'time_detail' => '09:37:33 AM',
        'app_name' => __('AI Sales Bot', 'chatbudgie'),
        'site' => 'sales.chatbudgie.com',
        'request_type' => __('Embeddings', 'chatbudgie'),
        'token_cost' => 512,
        'time_cost' => '0.98s',
        'icon' => 'sales',
    ),
    array(
        'time' => __('May 26, 2024', 'chatbudgie'),
        'time_detail' => '09:36:11 AM',
        'app_name' => __('Knowledge Base', 'chatbudgie'),
        'site' => 'docs.chatbudgie.com',
        'request_type' => __('Retrieval', 'chatbudgie'),
        'token_cost' => 324,
        'time_cost' => '0.76s',
        'icon' => 'knowledge',
    ),
    array(
        'time' => __('May 26, 2024', 'chatbudgie'),
        'time_detail' => '09:34:55 AM',
        'app_name' => __('Marketing Bot', 'chatbudgie'),
        'site' => 'www.chatbudgie.com',
        'request_type' => __('Chat Completion', 'chatbudgie'),
        'token_cost' => 1024,
        'time_cost' => '2.01s',
        'icon' => 'marketing',
    ),
    array(
        'time' => __('May 26, 2024', 'chatbudgie'),
        'time_detail' => '09:33:20 AM',
        'app_name' => __('Support Assistant', 'chatbudgie'),
        'site' => 'help.chatbudgie.com',
        'request_type' => __('Chat Completion', 'chatbudgie'),
        'token_cost' => 1102,
        'time_cost' => '2.32s',
        'icon' => 'support',
    ),
    array(
        'time' => __('May 26, 2024', 'chatbudgie'),
        'time_detail' => '09:31:08 AM',
        'app_name' => __('AI Sales Bot', 'chatbudgie'),
        'site' => 'sales.chatbudgie.com',
        'request_type' => __('Chat Completion', 'chatbudgie'),
        'token_cost' => 1876,
        'time_cost' => '2.85s',
        'icon' => 'sales',
    ),
    array(
        'time' => __('May 26, 2024', 'chatbudgie'),
        'time_detail' => '09:28:47 AM',
        'app_name' => __('Knowledge Base', 'chatbudgie'),
        'site' => 'docs.chatbudgie.com',
        'request_type' => __('Embeddings', 'chatbudgie'),
        'token_cost' => 768,
        'time_cost' => '1.34s',
        'icon' => 'knowledge',
    ),
    array(
        'time' => __('May 26, 2024', 'chatbudgie'),
        'time_detail' => '09:26:19 AM',
        'app_name' => __('Marketing Bot', 'chatbudgie'),
        'site' => 'www.chatbudgie.com',
        'request_type' => __('Chat Completion', 'chatbudgie'),
        'token_cost' => 1342,
        'time_cost' => '2.67s',
        'icon' => 'marketing',
    ),
);
?>
<div class="page page--settings page--usage">
    <header class="header header--settings">
        <div class="brand">
            <img class="brand__mark" src="<?php echo esc_url(CHATBUDGIE_PLUGIN_URL . 'assets/images/logo.png'); ?>" alt="" />
            <span class="brand__name">Chat<span class="brand__name--accent">Budgie</span></span>
        </div>
    </header>

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
            </div>

            <div class="usage-toolbar" aria-label="<?php echo esc_attr__('Usage filters', 'chatbudgie'); ?>">
                <button type="button" class="usage-select usage-select--wide">
                    <span><?php echo esc_html__('May 20 - May 26, 2024', 'chatbudgie'); ?></span>
                    <span class="cb-icon cb-icon--sm" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3" y="5" width="18" height="16" rx="2"></rect>
                            <path d="M16 3v4M8 3v4M3 10h18"></path>
                        </svg>
                    </span>
                </button>

                <button type="button" class="usage-select">
                    <span><?php echo esc_html__('All Apps', 'chatbudgie'); ?></span>
                    <span class="cb-icon cb-icon--sm" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </span>
                </button>

                <button type="button" class="usage-select">
                    <span><?php echo esc_html__('All Sites', 'chatbudgie'); ?></span>
                    <span class="cb-icon cb-icon--sm" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </span>
                </button>

                <button type="button" class="usage-select">
                    <span><?php echo esc_html__('All Request Types', 'chatbudgie'); ?></span>
                    <span class="cb-icon cb-icon--sm" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </span>
                </button>

                <button type="button" class="cb-btn cb-btn--ghost usage-toolbar__refresh">
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
                        <?php foreach ($usage_rows as $row) : ?>
                            <tr>
                                <td>
                                    <div class="usage-table__time">
                                        <span><?php echo esc_html($row['time']); ?></span>
                                        <span><?php echo esc_html($row['time_detail']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="usage-app">
                                        <span class="usage-app__icon usage-app__icon--<?php echo esc_attr($row['icon']); ?>" aria-hidden="true">
                                            <?php if ($row['icon'] === 'support') : ?>
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                    <path d="M7 14a5 5 0 1 1 10 0v2a2 2 0 0 1-2 2h-1l-2 2-2-2H9a2 2 0 0 1-2-2z"></path>
                                                    <path d="M9.5 12h.01M14.5 12h.01"></path>
                                                </svg>
                                            <?php elseif ($row['icon'] === 'sales') : ?>
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                    <path d="M7 7h10v10H7z"></path>
                                                    <path d="M10 10h4v4h-4z"></path>
                                                </svg>
                                            <?php elseif ($row['icon'] === 'knowledge') : ?>
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                    <path d="M8 5h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"></path>
                                                    <path d="M10 9h4M10 13h4"></path>
                                                </svg>
                                            <?php else : ?>
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                    <path d="M5 19c1.5-4 5-6 7-6s5.5 2 7 6"></path>
                                                    <path d="M8.5 11a3.5 3.5 0 1 1 7 0"></path>
                                                </svg>
                                            <?php endif; ?>
                                        </span>
                                        <span><?php echo esc_html($row['app_name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo esc_html($row['site']); ?></td>
                                <td><?php echo esc_html($row['request_type']); ?></td>
                                <td><?php echo esc_html(number_format_i18n($row['token_cost'])); ?></td>
                                <td><?php echo esc_html($row['time_cost']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="usage-table__footer">
                    <p class="usage-table__results"><?php echo esc_html__('Showing 1 to 10 of 248 results', 'chatbudgie'); ?></p>
                    <div class="usage-pagination">
                        <button type="button" class="usage-pagination__btn" aria-label="<?php echo esc_attr__('Previous page', 'chatbudgie'); ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="m15 18-6-6 6-6"></path>
                            </svg>
                        </button>
                        <button type="button" class="usage-pagination__btn is-active">1</button>
                        <button type="button" class="usage-pagination__btn">2</button>
                        <button type="button" class="usage-pagination__btn">3</button>
                        <span class="usage-pagination__dots">...</span>
                        <button type="button" class="usage-pagination__btn">25</button>
                        <button type="button" class="usage-pagination__btn" aria-label="<?php echo esc_attr__('Next page', 'chatbudgie'); ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="m9 6 6 6-6 6"></path>
                            </svg>
                        </button>
                        <button type="button" class="usage-select usage-select--compact">
                            <span><?php echo esc_html__('10 / page', 'chatbudgie'); ?></span>
                            <span class="cb-icon cb-icon--sm" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="m6 9 6 6 6-6"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <?php include CHATBUDGIE_PLUGIN_DIR . 'templates/admin-support-footer.php'; ?>
    </main>
</div>
