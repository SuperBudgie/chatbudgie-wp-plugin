<?php
/**
 * Shared support footer for ChatBudgie admin pages
 */

if (!defined('ABSPATH')) {
    exit;
}

$support_title = isset($support_title) ? $support_title : __('Need help or have a question?', 'chatbudgie');
$support_subtitle = isset($support_subtitle) ? $support_subtitle : __('SuperBudgie team is here to assist you.', 'chatbudgie');
$support_email = isset($support_email) ? $support_email : 'support@superbudgie.com';
?>

<section class="settings-card usage-support" aria-labelledby="usage-support-title">
    <div class="usage-support__body">
        <span class="usage-support__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
                <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>
            </svg>
        </span>
        <div>
            <h2 id="usage-support-title" class="settings-card__title"><?php echo esc_html($support_title); ?></h2>
            <p class="settings-card__sub"><?php echo esc_html($support_subtitle); ?></p>
        </div>
    </div>

    <a class="cb-btn cb-btn--ghost" href="mailto:<?php echo esc_attr($support_email); ?>">
        <span class="cb-icon cb-icon--sm" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                <path d="m4 7 8 6 8-6"></path>
            </svg>
        </span>
        <?php echo esc_html($support_email); ?>
    </a>
</section>
