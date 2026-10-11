<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'donation_form', function( $atts ) {
    $atts = shortcode_atts( array(
        'type' => 'one_time',
    ), $atts, 'donation_form' );

    $is_monthly   = ( 'monthly' === strtolower( $atts['type'] ) );
    $token_val    = $is_monthly ? 'process_custom_monthly_donation' : 'process_custom_onetime_donation';
    $nonce_action = $is_monthly ? 'execute_monthly_donation_nonce' : 'execute_onetime_donation_nonce';
    $nonce_name   = $is_monthly ? 'custom_monthly_nonce' : 'custom_onetime_nonce';
    $form_id      = $is_monthly ? 'custom-monthly-donation-form' : 'custom-onetime-donation-form';
    $btn_text     = $is_monthly ? 'DONATE MONTHLY' : 'DONATE NOW';

    ob_start();
    ?>
    <div class="styled-donation-container">
        <form method="POST" action="" id="<?php echo esc_attr( $form_id ); ?>">
            <input type="hidden" name="<?php echo $is_monthly ? 'monthly_form_token' : 'onetime_form_token'; ?>" value="<?php echo esc_attr( $token_val ); ?>">
            <?php wp_nonce_field( $nonce_action, $nonce_name ); ?>
            
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
                <input type="number" name="donation_amount" class="donation-amount-input" min="1" step="any" value="100" required>
                <span class="currency-suffix">USD</span>
            </div>

            <div class="form-section-label" style="margin-top: 20px;">Your Information</div>
            <div class="donor-name-row">
                <input type="text" name="donor_first" placeholder="First Name" required>
                <input type="text" name="donor_last" placeholder="Last Name" required>
            </div>
            <div class="donor-full-row">
                <input type="email" name="donor_email" placeholder="Email Address" required>
            </div>

            <div class="form-section-label" style="margin-top: 20px;">Payment Method</div>
            <div class="gateway-selection-block">
                <label class="gateway-row active">
                    <input type="radio" name="payment_method" value="paypal" checked>
                    <span class="gateway-text">PayPal</span>
                </label>
                <label class="gateway-row">
                    <input type="radio" name="payment_method" value="stripe">
                    <span class="gateway-text">Credit Card</span>
                </label>
            </div>

            <button type="submit" class="donation-submit-trigger"><?php echo esc_html( $btn_text ); ?></button>
        </form>
    </div>
    <?php
    return ob_get_clean();
});

add_shortcode( 'donation_receipt', function() {
    if ( ! is_page( 'donation-success' ) ) {
        return '';
    }

    $first_name = 'Donor';
    $last_name  = '';
    $email      = '';
    $amount     = '0.00';
    $frequency  = 'One-Time';

    if ( isset( $_GET['session_id'] ) && ! empty( $_GET['session_id'] ) ) {
        $settings      = get_option( 'cdf_api_settings', array() );
        $stripe_secret = isset( $settings['stripe_secret'] ) ? trim( $settings['stripe_secret'] ) : '';
        $session_id    = sanitize_text_field( wp_unslash( $_GET['session_id'] ) );

        if ( ! empty( $stripe_secret ) ) {
            $response = wp_remote_get( 'https://api.stripe.com/v1/checkout/sessions/' . $session_id, array(
                'headers' => array( 'Authorization' => 'Bearer ' . $stripe_secret ),
            ) );

            if ( ! is_wp_error( $response ) ) {
                $data = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( isset( $data['payment_status'] ) && 'paid' === $data['payment_status'] ) {
                    $first_name = ! empty( $data['metadata']['form_first_name'] ) ? $data['metadata']['form_first_name'] : 'Donor';
                    $last_name  = ! empty( $data['metadata']['form_last_name'] ) ? $data['metadata']['form_last_name'] : '';
                    $email      = ! empty( $data['customer_email'] ) ? $data['customer_email'] : ( $data['customer_details']['email'] ?? '' );
                    $amount     = isset( $data['amount_total'] ) ? number_format( floatval( $data['amount_total'] ) / 100, 2 ) : '0.00';
                    $frequency  = ( isset( $data['metadata']['donation_type'] ) && 'Monthly' === $data['metadata']['donation_type'] ) ? 'Monthly' : 'One-Time';
                }
            }
        }
    } else if ( isset( $_GET['token'] ) || isset( $_GET['subscription_id'] ) ) {
        $first_name = isset( $_GET['fname'] ) ? sanitize_text_field( wp_unslash( $_GET['fname'] ) ) : 'Donor';
        $last_name  = isset( $_GET['lname'] ) ? sanitize_text_field( wp_unslash( $_GET['lname'] ) ) : '';
        $email      = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';
        $amount     = isset( $_GET['amt'] ) ? number_format( floatval( $_GET['amt'] ), 2 ) : '0.00';
        $frequency  = isset( $_GET['subscription_id'] ) ? 'Monthly' : 'One-Time';
    }

    ob_start();
    ?>
    <div class="donation-receipt-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 28px; max-width: 600px; margin: 24px auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="text-align: center; margin-bottom: 20px;">
            <div style="width: 48px; height: 48px; background: #dcfce7; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #16a34a; font-size: 24px; margin-bottom: 12px;">✓</div>
            <h2 style="margin: 0 0 6px 0; color: #0f172a; font-size: 22px; font-weight: 700;">Thank You, <?php echo esc_html( trim( $first_name . ' ' . $last_name ) ); ?>.</h2>
            <p style="margin: 0; color: #64748b; font-size: 14px;">Your donation has been successfully received.</p>
        </div>
        <div style="border-top: 1px dashed #cbd5e1; padding-top: 18px; margin-top: 18px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 15px;">
                <span style="color: #64748b;">Amount:</span>
                <strong style="color: #0f172a;">$<?php echo esc_html( $amount ); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 15px;">
                <span style="color: #64748b;">Frequency:</span>
                <strong style="color: #0f172a;"><?php echo esc_html( $frequency ); ?></strong>
            </div>
            <?php if ( ! empty( $email ) ) : ?>
            <div style="display: flex; justify-content: space-between; font-size: 15px;">
                <span style="color: #64748b;">Email:</span>
                <strong style="color: #0f172a;"><?php echo esc_html( $email ); ?></strong>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
});
