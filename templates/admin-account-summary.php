<?php

/**
 * Shared account summary component for ChatBudgie admin pages
 */

if (!defined('ABSPATH')) {
    exit;
}

// $user_info is passed from the controller (ChatBudgie class)
$summary_avatar_url = !empty($user_info['avatarUrl']) ? $user_info['avatarUrl'] : CHATBUDGIE_PLUGIN_URL . 'assets/images/budgie-avatar.png';
$summary_account_name = !empty($user_info['name']) ? $user_info['name'] : __('ChatBudgie Workspace', 'chatbudgie');
$summary_account_email = !empty($user_info['email']) ? $user_info['email'] : __('Connected via your WordPress admin account', 'chatbudgie');
$summary_balance = isset($user_info['tokenBalance']) ? $user_info['tokenBalance'] : 0;

// Parse balance: remove 'k' and convert to number
$numeric_balance = (float) str_replace(['k', 'K'], '', $summary_balance);
if (stripos($summary_balance, 'k') !== false) {
    $numeric_balance *= 1000;
}
$is_low_balance = $numeric_balance < 50000;
?>

<section class="settings-card settings-card--summary" aria-labelledby="account-summary-title">
    <div class="settings-card__header">
        <h2 id="account-summary-title" class="settings-card__title"><?php echo esc_html(__('Account Summary', 'chatbudgie')); ?></h2>
    </div>
    <div class="summary-grid">
        <div class="summary-profile">
            <div class="summary-profile__avatar">
                <img src="<?php echo esc_url($summary_avatar_url); ?>" alt="<?php echo esc_attr($summary_account_name); ?>" />
            </div>
            <div class="summary-profile__body">
                <h3 class="summary-profile__name"><?php echo esc_html($summary_account_name); ?></h3>
                <p class="summary-profile__meta"><?php echo esc_html($summary_account_email); ?></p>
            </div>
        </div>

        <div class="summary-balance">
            <p class="summary-balance__label"><?php echo esc_html__('Remaining Tokens', 'chatbudgie'); ?></p>
            <p class="summary-balance__value" id="chatbudgie-token-display"><?php echo esc_html($summary_balance); ?></p>
            <p class="summary-balance__note">
                <?php if ($is_low_balance) : ?>
                    <span class="status-dot status-dot--warning" title="<?php echo esc_attr__('Low balance: please recharge.', 'chatbudgie'); ?>"></span>
                    <?php echo esc_html__('Low balance: please recharge.', 'chatbudgie'); ?>
                <?php else : ?>
                    <span class="status-dot status-dot--success" aria-hidden="true"></span>
                    <?php echo esc_html__('ChatBudgie uses tokens to power its features.', 'chatbudgie'); ?>
                <?php endif; ?>
            </p>
        </div>

        <div class="summary-actions">
            <a href="admin.php?page=chatbudgie-orders#recharge-title">
                <button type="button" class="cb-btn cb-btn--primary" id="chatbudgie-buy-tokens">
                    <span class="cb-icon cb-icon--sm" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="9" cy="20" r="1"></circle>
                            <circle cx="18" cy="20" r="1"></circle>
                            <path d="M3 4h2l2.4 10.2a1 1 0 0 0 1 .8h9.7a1 1 0 0 0 1-.7L21 7H7"></path>
                        </svg>
                    </span>
                    <?php echo esc_html__('Buy Tokens', 'chatbudgie'); ?>
                </button>
            </a>
        </div>
    </div>
</section>