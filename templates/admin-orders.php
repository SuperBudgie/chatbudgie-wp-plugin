<?php

/**
 * Template for the ChatBudgie admin orders page
 */

if (!defined('ABSPATH')) {
    exit;
}

// $user_info is passed from the controller (ChatBudgie class)
$currency = 'USD';
?>

<script
    src="https://www.paypal.com/sdk/js?client-id=<?php echo esc_attr(CHATBUDGIE_PAYPAL_CLIENT_ID); ?>&currency=<?php echo esc_attr($currency); ?>&components=buttons&disable-funding=venmo">
</script>

<div class="page page--settings">
    <?php include CHATBUDGIE_PLUGIN_DIR . 'templates/admin-header.php'; ?>

    <main class="settings" role="main">
        <section class="settings__hero" aria-labelledby="orders-title">
            <h1 id="orders-title" class="settings__title"><?php echo esc_html__('Orders', 'chatbudgie'); ?></h1>
            <p class="settings__sub"><?php echo esc_html__('Track purchases, account balance, and token top-ups in one place.', 'chatbudgie'); ?></p>
            
            <?php if (!empty($user_info)) : ?>
                <?php
                $summary_balance = isset($user_info['tokenBalance']) ? $user_info['tokenBalance'] : 0;
                ?>
                <div class="account-balance-box" style="margin-top: 15px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; display: inline-block;">
                    <span style="font-size: 14px; color: #666;"><?php echo esc_html__('Remaining Tokens:', 'chatbudgie'); ?></span>
                    <strong style="font-size: 16px; margin-left: 5px;"><?php echo esc_html($summary_balance); ?></strong>
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
                            <th><?php echo esc_html__('Order ID', 'chatbudgie'); ?></th>
                            <th><?php echo esc_html__('Tokens', 'chatbudgie'); ?></th>
                            <th><?php echo esc_html__('Price', 'chatbudgie'); ?></th>
                            <th><?php echo esc_html__('Channel', 'chatbudgie'); ?></th>
                            <th><?php echo esc_html__('Status', 'chatbudgie'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $orders = isset($orders_data['content']) ? $orders_data['content'] : [];
                        if (empty($orders)) : 
                        ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <?php echo esc_html__('No order history found.', 'chatbudgie'); ?>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($orders as $order) : ?>
                                <?php
                                $create_time = wp_date(
                                    get_option('date_format') . ' ' . get_option('time_format'),
                                    strtotime($order['createTime'])
                                );
                                $amount_display = number_format($order['amount'] / 1000000, 1) . 'M';
                                $price_display = $order['currency'] . ' ' . number_format($order['price'], 2);
                                ?>
                                <tr>
                                    <td><?php echo esc_html($create_time); ?></td>
                                    <td><code class="order-id"><?php echo esc_html($order['id']); ?></code></td>
                                    <td><?php echo esc_html($amount_display); ?></td>
                                    <td><?php echo esc_html($price_display); ?></td>
                                    <td><?php echo esc_html($order['paymentChannel'] ?: '-'); ?></td>
                                    <td>
                                        <span class="status-badge status-badge--<?php echo esc_attr(strtolower($order['status'])); ?>">
                                            <?php echo esc_html(ucfirst($order['status'])); ?>
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
                    $current_page = $orders_data['page']['number'] + 1;
                    $total_pages = $orders_data['page']['totalPages'];
                    
                    $prev_url = add_query_arg('paged', max(1, $current_page - 1));
                    $next_url = add_query_arg('paged', min($total_pages, $current_page + 1));
                    
                    $has_prev = $current_page > 1;
                    $has_next = $current_page < $total_pages;
                    ?>
                    
                    <a href="<?php echo $has_prev ? esc_url($prev_url) : 'javascript:void(0);'; ?>" 
                       class="cb-btn cb-btn--ghost cb-btn--sm <?php echo esc_attr($has_prev ? '' : 'is-disabled'); ?>"
                       aria-label="<?php echo esc_attr__('Previous Page', 'chatbudgie'); ?>">
                        <?php echo esc_html__('&laquo; Previous', 'chatbudgie'); ?>
                    </a>

                    <span class="pagination-info">
                        <?php 
                        /* translators: 1: current page number, 2: total number of pages */
                        printf(
                            esc_html__('Page %1$d of %2$d', 'chatbudgie'), 
                            absint($current_page), 
                            absint($total_pages)
                        ); 
                        ?>
                    </span>

                    <a href="<?php echo $has_next ? esc_url($next_url) : 'javascript:void(0);'; ?>" 
                       class="cb-btn cb-btn--ghost cb-btn--sm <?php echo esc_attr($has_next ? '' : 'is-disabled'); ?>"
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
<div id="chatbudgie-payment-dialog" class="chatbudgie-payment-dialog" style="display: none;">
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

<style>
    .chatbudgie-payment-dialog {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 100000;
    }
    
    .chatbudgie-payment-dialog__overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        animation: fadeIn 0.3s ease-in-out;
    }
    
    .chatbudgie-payment-dialog__content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        padding: 40px;
        text-align: center;
        max-width: 400px;
        width: 90%;
        animation: slideUp 0.3s ease-in-out;
    }
    
    .chatbudgie-payment-dialog__close {
        position: absolute;
        top: 16px;
        right: 16px;
        background: none;
        border: none;
        width: 32px;
        height: 32px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #666;
        transition: color 0.2s;
    }
    
    .chatbudgie-payment-dialog__close:hover {
        color: #333;
    }
    
    .chatbudgie-payment-dialog__close svg {
        width: 20px;
        height: 20px;
    }
    
    .chatbudgie-payment-dialog__icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 24px;
        background: linear-gradient(135deg, #5fb878, #4da361);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        animation: scaleIn 0.4s ease-out;
    }
    
    .chatbudgie-payment-dialog__icon svg {
        width: 40px;
        height: 40px;
    }
    
    .chatbudgie-payment-dialog__title {
        font-size: 24px;
        font-weight: 600;
        color: #333;
        margin: 0 0 12px 0;
    }
    
    .chatbudgie-payment-dialog__message {
        font-size: 14px;
        color: #666;
        line-height: 1.6;
        margin: 0 0 24px 0;
    }
    
    .chatbudgie-payment-dialog__button {
        min-width: 200px;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translate(-50%, -45%);
        }
        to {
            opacity: 1;
            transform: translate(-50%, -50%);
        }
    }
    
    @keyframes scaleIn {
        from {
            transform: scale(0);
        }
        to {
            transform: scale(1);
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ajaxUrl = chatbudgie_admin_params.ajax_url;
        const nonce = chatbudgie_admin_params.nonce;

        const packages = [{
                id: '5m',
                amount: 5,
                showPrice: '$4.99'
            },
            {
                id: '20m',
                amount: 20,
                showPrice: '$19.50'
            },
            {
                id: '100m',
                amount: 100,
                showPrice: '$95.00'
            }
        ];

        packages.forEach(pkg => {
            paypal.Buttons({
                style: {
                    layout: 'vertical',
                    color: pkg.id === '20m' ? 'gold' : 'blue',
                    shape: 'pill',
                    borderRadius: 14,
                    height: 50,
                    label: 'buynow'
                },
                createOrder: function(data, actions) {
                    return fetch(ajaxUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                action: 'chatbudgie_create_paypal_order',
                                _ajax_nonce: nonce,
                                package: pkg.id,
                                amount: pkg.amount,
                                currency: "<?php echo esc_js($currency); ?>",
                                show_price: pkg.showPrice
                            })
                        })
                        .then(response => response.json())
                        .then(res => {
                            if (res.success) {
                                return res.data.id;
                            } else {
                                throw new Error(res.data.message || 'Failed to create order');
                            }
                        });
                },
                onApprove: function(data, actions) {
                    return fetch(ajaxUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                action: 'chatbudgie_capture_paypal_order',
                                _ajax_nonce: nonce,
                                order_id: data.orderID
                            })
                        })
                        .then(response => response.json())
                        .then(res => {
                            if (res.success) {
                                showPaymentSuccessDialog();
                            } else {
                                throw new Error(res.data.message || 'Failed to capture payment');
                            }
                        });
                },
                onError: function(err) {
                    console.error('PayPal Error:', err);
                    alert('An error occurred during the transaction: ' + err.message);
                }
            }).render('#paypal-button-' + pkg.id);
        });

        // Payment dialog handlers
        function showPaymentSuccessDialog() {
            const dialog = document.getElementById('chatbudgie-payment-dialog');
            const closeBtn = dialog.querySelector('.chatbudgie-payment-dialog__close');
            const continueBtn = dialog.querySelector('.chatbudgie-payment-dialog__button');
            const overlay = dialog.querySelector('.chatbudgie-payment-dialog__overlay');
            
            dialog.style.display = 'block';
            
            function closeDialog() {
                dialog.style.display = 'none';
                window.location.href = 'admin.php?page=chatbudgie';
            }
            
            closeBtn.addEventListener('click', closeDialog);
            continueBtn.addEventListener('click', closeDialog);
            overlay.addEventListener('click', closeDialog);
            
            // Auto-redirect after 10 seconds
            setTimeout(closeDialog, 10000);
        }
    });
</script>
