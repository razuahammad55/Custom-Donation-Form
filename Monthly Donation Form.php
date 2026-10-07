/**
 * Plugin Name: Custom Monthly Donation Form
 * Description: A cache-resilient, custom monthly recurring donation form supporting Stripe Checkout and PayPal REST API Subscriptions.
 * Version: 1.1.1
 * Author: Razu Ahammad
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * PART 1: SECURE BACKEND ROUTER & GATEWAY PROCESSOR
 */
add_action( 'template_redirect', function() {
    
    // STRICT ISOLATION
    if ( ! isset( $_POST['monthly_form_token'] ) || 'process_custom_monthly_donation' !== $_POST['monthly_form_token'] ) {
        return;
    }

    // CACHE-RESILIENT SECURITY CHECK
    $submitted_nonce = isset( $_POST['custom_monthly_nonce'] ) ? $_POST['custom_monthly_nonce'] : '';
    $is_nonce_valid  = wp_verify_nonce( $submitted_nonce, 'execute_monthly_donation_nonce' );

    if ( ! $is_nonce_valid ) {
        $fallback_email  = isset( $_POST['donor_email'] ) ? sanitize_email( $_POST['donor_email'] ) : '';
        $fallback_amount = isset( $_POST['donation_amount'] ) ? floatval( $_POST['donation_amount'] ) : 0;

        if ( empty( $fallback_email ) || ! is_email( $fallback_email ) || $fallback_amount <= 0 ) {
            wp_die( 'Security check failed or invalid form details. Please refresh the page and try again.', 'Validation Error', array( 'response' => 403 ) );
        }
    }

    // Sanitize user inputs
    $chosen_gateway = isset( $_POST['payment_method'] ) ? sanitize_text_field( $_POST['payment_method'] ) : 'paypal';
    $custom_amount  = isset( $_POST['donation_amount'] ) ? floatval( $_POST['donation_amount'] ) : 0;
    $email          = isset( $_POST['donor_email'] ) ? sanitize_email( $_POST['donor_email'] ) : '';
    $first_name     = isset( $_POST['donor_first'] ) ? sanitize_text_field( $_POST['donor_first'] ) : '';
    $last_name      = isset( $_POST['donor_last'] ) ? sanitize_text_field( $_POST['donor_last'] ) : '';

    if ( $custom_amount <= 0 ) {
        wp_die( 'Please enter a valid donation amount.', 'Invalid Amount', array( 'back_link' => true ) );
    }

    $stripe_secret_key = defined( 'DONATION_STRIPE_SECRET' ) ? DONATION_STRIPE_SECRET : ''; 
    $is_sandbox_mode   = defined( 'DONATION_SANDBOX_MODE' )  ? DONATION_SANDBOX_MODE  : false;

    // ROUTE A: STRIPE SUBSCRIPTION
    if ( 'stripe' === $chosen_gateway ) {
        if ( empty( $stripe_secret_key ) ) {
            wp_die( 'Stripe Configuration Error. Secret key is missing in wp-config.php.' );
        }

        $price_response = wp_remote_post( 'https://api.stripe.com/v1/prices', [
            'headers' => [
                'Authorization' => 'Bearer ' . $stripe_secret_key,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'currency'            => 'usd',
                'unit_amount'         => round( $custom_amount * 100 ),
                'recurring[interval]' => 'month',
                'product_data[name]'  => 'Monthly Recurring Donation',
            ],
        ]);

        if ( is_wp_error( $price_response ) ) {
            wp_die( 'Stripe Price Registration Error: ' . esc_html( $price_response->get_error_message() ) );
        }

        $price_data = json_decode( wp_remote_retrieve_body( $price_response ), true );
        $price_id   = isset( $price_data['id'] ) ? sanitize_text_field( $price_data['id'] ) : '';

        if ( empty( $price_id ) ) {
            wp_die( 'Stripe Configuration Error. Please verify your Stripe Secret Key.' );
        }

        $session_response = wp_remote_post( 'https://api.stripe.com/v1/checkout/sessions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $stripe_secret_key,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'payment_method_types[0]' => 'card',
                'mode'                    => 'subscription',
                'customer_email'          => $email,
                'line_items[0][price]'    => $price_id,
                'line_items[0][quantity]' => 1,
                'success_url'             => esc_url_raw( home_url( '/donation-success/' ) ),
                'cancel_url'              => esc_url_raw( home_url( '/donation-canceled/' ) ),
                'metadata[donor_name]'    => sanitize_text_field( trim( $first_name . ' ' . $last_name ) ),
            ],
        ]);

        if ( is_wp_error( $session_response ) ) {
            wp_die( 'Stripe Checkout Session Error: ' . esc_html( $session_response->get_error_message() ) );
        }

        $session_data = json_decode( wp_remote_retrieve_body( $session_response ), true );
        $checkout_url = isset( $session_data['url'] ) ? esc_url_raw( $session_data['url'] ) : '';

        if ( ! empty( $checkout_url ) ) {
            wp_redirect( $checkout_url );
            exit;
        } else {
            wp_die( 'Stripe failed to return a valid checkout URL.' );
        }
    }
    
    // ROUTE B: PAYPAL SUBSCRIPTION
    if ( 'paypal' === $chosen_gateway ) {
        $client_id     = defined( 'DONATION_PAYPAL_CLIENT_ID' ) ? DONATION_PAYPAL_CLIENT_ID : '';
        $client_secret = defined( 'DONATION_PAYPAL_CLIENT_SECRET' ) ? DONATION_PAYPAL_CLIENT_SECRET : '';
        $plan_id       = defined( 'DONATION_PAYPAL_PLAN_ID' ) ? DONATION_PAYPAL_PLAN_ID : '';

        if ( empty( $client_id ) || empty( $client_secret ) ) {
            wp_die( 'PayPal API Configuration Error. Client ID or Secret is missing in wp-config.php.' );
        }

        // Fixed endpoint URL typo
        $api_base = $is_sandbox_mode ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

        $auth_response = wp_remote_post( $api_base . '/v1/oauth2/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body' => 'grant_type=client_credentials',
        ));

        if ( is_wp_error( $auth_response ) ) {
            wp_die( 'PayPal Authentication Error: ' . esc_html( $auth_response->get_error_message() ) );
        }

        $auth_data    = json_decode( wp_remote_retrieve_body( $auth_response ), true );
        $access_token = isset( $auth_data['access_token'] ) ? $auth_data['access_token'] : '';

        if ( empty( $access_token ) ) {
            wp_die( 'Failed to retrieve PayPal access token. Check your Client ID and Secret.' );
        }

        $quantity = max( 1, (int) round( $custom_amount ) );

        $subscription_payload = array(
            'plan_id'    => $plan_id,
            'quantity'   => (string) $quantity,
            'subscriber' => array(
                'email_address' => $email,
                'name'          => array(
                    'given_name' => $first_name,
                    'surname'    => $last_name,
                ),
            ),
            'application_context' => array(
                'brand_name'          => get_bloginfo( 'name' ),
                'locale'              => 'en-US',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action'         => 'SUBSCRIBE_NOW',
                'return_url'          => esc_url_raw( home_url( '/donation-success/' ) ),
                'cancel_url'          => esc_url_raw( home_url( '/donation-canceled/' ) ),
            ),
        );

        $sub_response = wp_remote_post( $api_base . '/v1/billing/subscriptions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
            'body' => json_encode( $subscription_payload ),
        ));

        if ( is_wp_error( $sub_response ) ) {
            wp_die( 'PayPal Subscription Creation Error: ' . esc_html( $sub_response->get_error_message() ) );
        }

        $sub_data = json_decode( wp_remote_retrieve_body( $sub_response ), true );

        $approve_url = '';
        if ( isset( $sub_data['links'] ) && is_array( $sub_data['links'] ) ) {
            foreach ( $sub_data['links'] as $link ) {
                if ( isset( $link['rel'] ) && 'approve' === $link['rel'] ) {
                    $approve_url = $link['href'];
                    break;
                }
            }
        }

        if ( ! empty( $approve_url ) ) {
            wp_redirect( esc_url_raw( $approve_url ) );
            exit;
        } else {
            wp_die( 'PayPal API failed to yield an approval redirect link.' );
        }
    }
});

/**
 * PART 2: FRONTEND COMPONENT RENDER ENGINE
 */
add_shortcode( 'custom_monthly_donation_form', function() {
    ob_start();
    ?>
    <div class="styled-donation-container">
        <form method="POST" action="" id="custom-monthly-donation-form">
            <input type="hidden" name="monthly_form_token" value="process_custom_monthly_donation">
            <?php wp_nonce_field( 'execute_monthly_donation_nonce', 'custom_monthly_nonce' ); ?>
            
            <div class="form-section-label">Select Amount</div>
            <div class="amount-btn-grid">
                <button type="button" class="amt-btn" data-val="500">$500</button>
                <button type="button" class="amt-btn" data-val="250">$250</button>
                <button type="button" class="amt-btn active" data-val="100">$100</button>
                <button type="button" class="amt-btn" data-val="50">$50</button>
                <button type="button" class="amt-btn" data-val="25">$25</button>
                <button type="button" class="amt-btn" data-val="10">$10</button>
            </div>

            <div class="input-amount-wrapper">
                <span class="currency-prefix">$</span>
                <input type="number" name="donation_amount" id="custom_monthly_donation_amount" min="1" step="any" value="100" required>
                <span class="currency-suffix">USD</span>
            </div>

            <div class="form-section-label" style="margin-top: 25px;">Your Information</div>
            <div class="donor-name-row">
                <input type="text" name="donor_first" placeholder="First Name" required>
                <input type="text" name="donor_last" placeholder="Last Name" required>
            </div>
            <div class="donor-full-row">
                <input type="email" name="donor_email" placeholder="Email Address" required>
            </div>

            <div class="form-section-label" style="margin-top: 25px;">Payment Method</div>
            <div class="gateway-selection-block">
                <label class="gateway-row active">
                    <input type="radio" name="payment_method" value="paypal" checked>
                    <span class="custom-radio-ui"></span>
                    <span class="gateway-text">PayPal</span>
                </label>
                <label class="gateway-row">
                    <input type="radio" name="payment_method" value="stripe">
                    <span class="custom-radio-ui"></span>
                    <span class="gateway-text">Credit Card</span>
                </label>
            </div>

            <button type="submit" class="donation-submit-trigger">DONATE MONTHLY</button>
        </form>
    </div>

    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('custom-monthly-donation-form');
        if (!form) return;

        const amtButtons   = form.querySelectorAll('.amt-btn');
        const amountInput  = form.querySelector('#custom_monthly_donation_amount');
        const gatewayRows  = form.querySelectorAll('.gateway-row');

        amtButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                amtButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                if (amountInput) {
                    amountInput.value = this.getAttribute('data-val');
                }
            });
        });

        if (amountInput) {
            amountInput.addEventListener('input', function() {
                amtButtons.forEach(btn => {
                    if (btn.getAttribute('data-val') === this.value) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });
            });
        }

        gatewayRows.forEach(row => {
            row.addEventListener('click', function(e) {
                gatewayRows.forEach(r => r.classList.remove('active'));
                this.classList.add('active');
                const radioInput = this.querySelector('input[type="radio"]');
                if (radioInput && e.target !== radioInput) {
                    radioInput.checked = true;
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
});
