<?php
/**
 * Template for the ChatBudgie admin orders page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="page page--settings">
    <?php include CHATBUDGIE_PLUGIN_DIR . 'templates/admin-header.php'; ?>

    <main class="settings" role="main">
        <section class="settings__hero" aria-labelledby="orders-title">
            <h1 id="orders-title" class="settings__title"><?php echo esc_html__('Orders', 'chatbudgie'); ?></h1>
            <p class="settings__sub"><?php echo esc_html__('Track purchases, invoices, and token top-ups in one place.', 'chatbudgie'); ?></p>
        </section>

        <section class="settings-card" aria-labelledby="orders-coming-soon-title">
            <div class="settings-card__header settings-card__header--stack">
                <div>
                    <h2 id="orders-coming-soon-title" class="settings-card__title"><?php echo esc_html__('Order History', 'chatbudgie'); ?></h2>
                    <p class="settings-card__sub"><?php echo esc_html__('Order management is on the way. This page will soon show purchases, receipts, and billing status.', 'chatbudgie'); ?></p>
                </div>
            </div>
        </section>

        <?php include CHATBUDGIE_PLUGIN_DIR . 'templates/admin-support-footer.php'; ?>
    </main>
</div>
