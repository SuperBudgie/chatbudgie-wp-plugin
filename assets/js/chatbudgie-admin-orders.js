document.addEventListener('DOMContentLoaded', function () {
    const params = window.chatbudgie_orders_params;

    if (!params) {
        return;
    }

    const refreshButton = document.querySelector('.js-chatbudgie-orders-refresh');
    if (refreshButton) {
        refreshButton.addEventListener('click', function () {
            window.location.reload();
        });
    }

    if (typeof window.paypal === 'undefined' || !Array.isArray(params.packages)) {
        return;
    }

    const dialog = document.getElementById('chatbudgie-payment-dialog');
    let closeTimeoutId = null;

    const closeDialog = function () {
        if (!dialog) {
            window.location.href = params.redirect_url;
            return;
        }

        dialog.hidden = true;

        if (closeTimeoutId) {
            window.clearTimeout(closeTimeoutId);
        }

        window.location.href = params.redirect_url;
    };

    const showPaymentSuccessDialog = function () {
        if (!dialog) {
            window.location.href = params.redirect_url;
            return;
        }

        const closeBtn = dialog.querySelector('.chatbudgie-payment-dialog__close');
        const continueBtn = dialog.querySelector('.chatbudgie-payment-dialog__button');
        const overlay = dialog.querySelector('.chatbudgie-payment-dialog__overlay');

        dialog.hidden = false;

        if (closeBtn) {
            closeBtn.onclick = closeDialog;
        }

        if (continueBtn) {
            continueBtn.onclick = closeDialog;
        }

        if (overlay) {
            overlay.onclick = closeDialog;
        }

        closeTimeoutId = window.setTimeout(closeDialog, 10000);
    };

    params.packages.forEach(function (pkg) {
        window.paypal.Buttons({
            style: {
                layout: 'vertical',
                color: pkg.id === '20m' ? 'gold' : 'blue',
                shape: 'pill',
                borderRadius: 14,
                height: 50,
                label: 'buynow'
            },
            createOrder: function () {
                return fetch(params.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'chatbudgie_create_paypal_order',
                        _ajax_nonce: params.nonce,
                        package: pkg.id,
                        amount: pkg.amount,
                        currency: params.currency,
                        show_price: pkg.showPrice
                    })
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (res) {
                        if (res.success) {
                            return res.data.id;
                        }

                        throw new Error(res.data.message || params.strings.create_order_error);
                    });
            },
            onApprove: function (data) {
                return fetch(params.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'chatbudgie_capture_paypal_order',
                        _ajax_nonce: params.nonce,
                        order_id: data.orderID
                    })
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (res) {
                        if (res.success) {
                            showPaymentSuccessDialog();
                            return;
                        }

                        throw new Error(res.data.message || params.strings.capture_order_error);
                    });
            },
            onError: function (err) {
                console.error('PayPal Error:', err);
                window.alert(params.strings.transaction_error + ' ' + err.message);
            }
        }).render('#paypal-button-' + pkg.id);
    });
});
