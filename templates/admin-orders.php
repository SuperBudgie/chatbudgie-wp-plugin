<?php

/**
 * Template for the ChatBudgie admin orders page
 */

if (!defined('ABSPATH')) {
    exit;
}

// $user_info is passed from the controller (ChatBudgie class)
$user_id = !empty($user_info['id']) ? $user_info['id'] : 'unknown';
$currency = 'USD';
$paypal_client_id = 'AekooxzVQrv7o8r58pnHigf0owNuUr0i8rXBQemNt1ADaCom1v-63rNhrxy48zYhNQBKbqttnm1yUpTE'; // Sandbox Client ID
?>

<script
    src="https://www.paypal.com/sdk/js?client-id=<?php echo esc_attr($paypal_client_id); ?>&currency=<?php echo esc_attr($currency); ?>&components=buttons&disable-funding=venmo">
</script>

<div class="page page--settings">
    <?php include CHATBUDGIE_PLUGIN_DIR . 'templates/admin-header.php'; ?>

    <main class="settings" role="main">
        <section class="settings__hero" aria-labelledby="orders-title">
            <h1 id="orders-title" class="settings__title"><?php echo esc_html__('Orders', 'chatbudgie'); ?></h1>
            <p class="settings__sub"><?php echo esc_html__('Track purchases, invoices, and token top-ups in one place.', 'chatbudgie'); ?></p>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const userId = '<?php echo esc_js($user_id); ?>';
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
                    color: pkg.id === '20m' ? 'blue' : 'gold',
                    shape: 'rect',
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
                                console.log('Transaction completed! Your tokens have been recharged.');
                                window.location.href = 'admin.php?page=chatbudgie';
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
    });
</script>