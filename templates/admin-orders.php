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
            <p class="settings__sub"><?php echo esc_html__('Track purchases, account balance, and token top-ups in one place.', 'chatbudgie'); ?></p>
            
            <?php if (!empty($user_info)) : ?>
                <?php
                $chatbudgie_balance = isset($user_info['tokenBalance']) ? $user_info['tokenBalance'] : 0;
                ?>
                <div class="account-balance-box">
                    <span class="account-balance-box__label"><?php echo esc_html__('Remaining Tokens:', 'chatbudgie'); ?></span>
                    <strong class="account-balance-box__value"><?php echo esc_html($chatbudgie_balance); ?></strong>
                </div>
            <?php endif; ?>
        </section>

        <section class="settings-card" aria-labelledby="recharge-title">
            <div class="settings-card__header">
                <div>
                    <h2 id="recharge-title" class="settings-card__title"><?php echo esc_html__('Recharge Tokens', 'chatbudgie'); ?></h2>
                    <p class="settings-card__sub"><?php echo esc_html__('Select a token package to recharge your account balance.', 'chatbudgie'); ?></p>
                </div>
            </div>

            <div class="product-grid">
                <!-- 5M Tokens -->
                <article class="product-card">
                    <div class="product-card__tokens">5M Tokens</div>
                    <p class="product-card__sub"><?php echo esc_html__('Basic Recharge', 'chatbudgie'); ?></p>
                    <div class="product-card__price-wrap">
                        <span class="product-card__discount">$5.00</span>
                        <div class="product-card__price">$4.99</div>
                    </div>
                    <div id="paypal-button-5m" class="product-card__button"></div>
                </article>

                <!-- 20M Tokens -->
                <article class="product-card product-card--featured">
                    <div class="product-card__tokens">20M Tokens</div>
                    <p class="product-card__sub"><?php echo esc_html__('Pro Pack - Best Value', 'chatbudgie'); ?></p>
                    <div class="product-card__price-wrap">
                        <span class="product-card__discount">$20.00</span>
                        <div class="product-card__price">$19.50</div>
                    </div>
                    <div id="paypal-button-20m" class="product-card__button"></div>
                </article>

                <!-- 100M Tokens -->
                <article class="product-card">
                    <div class="product-card__tokens">100M Tokens</div>
                    <p class="product-card__sub"><?php echo esc_html__('Enterprise Scale', 'chatbudgie'); ?></p>
                    <div class="product-card__price-wrap">
                        <span class="product-card__discount">$100.00</span>
                        <div class="product-card__price">$95.00</div>
                    </div>
                    <div id="paypal-button-100m" class="product-card__button"></div>
                </article>
            </div>
        </section>

        <section class="settings-card" aria-labelledby="order-history-title">
            <div class="settings-card__header settings-card__header--stack">
                <div>
                    <h2 id="order-history-title" class="settings-card__title"><?php echo esc_html__('Order History', 'chatbudgie'); ?></h2>
                    <p class="settings-card__sub"><?php echo esc_html__('Track your past recharges and billing status.', 'chatbudgie'); ?></p>
                </div>
                <button type="button" class="cb-btn cb-btn--ghost usage-toolbar__refresh js-chatbudgie-orders-refresh">
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
                            <th><?php echo esc_html__('Order ID', 'chatbudgie'); ?></th>
                            <th><?php echo esc_html__('Tokens', 'chatbudgie'); ?></th>
                            <th><?php echo esc_html__('Price', 'chatbudgie'); ?></th>
                            <th><?php echo esc_html__('Channel', 'chatbudgie'); ?></th>
                            <th><?php echo esc_html__('Status', 'chatbudgie'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $chatbudgie_orders = isset($orders_data['content']) ? $orders_data['content'] : [];
                        if (empty($chatbudgie_orders)) : 
                        ?>
                            <tr>
                                <td colspan="6" class="usage-table__empty">
                                    <?php echo esc_html__('No order history found.', 'chatbudgie'); ?>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($chatbudgie_orders as $chatbudgie_order) : ?>
                                <?php
                                $chatbudgie_create_time = wp_date(
                                    get_option('date_format') . ' ' . get_option('time_format'),
                                    strtotime($chatbudgie_order['createTime'])
                                );
                                $chatbudgie_amount_display = number_format($chatbudgie_order['amount'] / 1000000, 1) . 'M';
                                $chatbudgie_price_display = $chatbudgie_order['currency'] . ' ' . number_format($chatbudgie_order['price'], 2);
                                ?>
                                <tr>
                                    <td><?php echo esc_html($chatbudgie_create_time); ?></td>
                                    <td><code class="order-id"><?php echo esc_html($chatbudgie_order['id']); ?></code></td>
                                    <td><?php echo esc_html($chatbudgie_amount_display); ?></td>
                                    <td><?php echo esc_html($chatbudgie_price_display); ?></td>
                                    <td><?php echo esc_html($chatbudgie_order['paymentChannel'] ?: '-'); ?></td>
                                    <td>
                                        <span class="status-badge status-badge--<?php echo esc_attr(strtolower($chatbudgie_order['status'])); ?>">
                                            <?php echo esc_html(ucfirst($chatbudgie_order['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (isset($orders_data['page']['totalPages']) && $orders_data['page']['totalPages'] > 1) : ?>
                <div class="usage-pagination">
                    <?php
                    $chatbudgie_current_page = $orders_data['page']['number'] + 1;
                    $chatbudgie_total_pages = $orders_data['page']['totalPages'];
                    
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

<!-- Payment Success Dialog -->
<div id="chatbudgie-payment-dialog" class="chatbudgie-payment-dialog" hidden>
    <div class="chatbudgie-payment-dialog__overlay"></div>
    <div class="chatbudgie-payment-dialog__content">
        <button type="button" class="chatbudgie-payment-dialog__close" aria-label="<?php echo esc_attr__('Close', 'chatbudgie'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        <div class="chatbudgie-payment-dialog__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <h2 class="chatbudgie-payment-dialog__title"><?php echo esc_html__('Payment Success!', 'chatbudgie'); ?></h2>
        <p class="chatbudgie-payment-dialog__message"><?php echo esc_html__('Your transaction has been successfully completed. The tokens will be recharged to your account shortly.', 'chatbudgie'); ?></p>
        <button type="button" class="chatbudgie-payment-dialog__button cb-btn cb-btn--primary">
            <?php echo esc_html__('Continue', 'chatbudgie'); ?>
        </button>
    </div>
</div>
