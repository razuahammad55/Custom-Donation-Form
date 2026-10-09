/**
 * Shortcode to display donor summary on the Donation Success page
 * Usage: [donation_receipt]
 */
add_shortcode( 'donation_receipt', function() {
    if ( ! is_page( 'donation-success' ) ) {
        return '';
    }

    $first_name = '';
    $last_name  = '';
    $email      = '';
    $amount     = '0.00';
    $frequency  = 'One-Time';

    // -------------------------------------------------------------------------
    // 1. STRIPE RETURN DATA
    // -------------------------------------------------------------------------
    if ( isset( $_GET['session_id'] ) && ! empty( $_GET['session_id'] ) ) {
        $stripe_secret = defined( 'DONATION_STRIPE_SECRET' ) ? DONATION_STRIPE_SECRET : '';
        $session_id    = sanitize_text_field( wp_unslash( $_GET['session_id'] ) );

        if ( ! empty( $stripe_secret ) ) {
            $response = wp_remote_get( 'https://api.stripe.com/v1/checkout/sessions/' . $session_id, array(
                'headers' => array( 'Authorization' => 'Bearer ' . $stripe_secret ),
            ) );

            if ( ! is_wp_error( $response ) ) {
                $data = json_decode( wp_remote_retrieve_body( $response ), true );

                if ( isset( $data['payment_status'] ) && 'paid' === $data['payment_status'] ) {
                    $first_name = ! empty( $data['metadata']['form_first_name'] ) ? sanitize_text_field( $data['metadata']['form_first_name'] ) : '';
                    $last_name  = ! empty( $data['metadata']['form_last_name'] ) ? sanitize_text_field( $data['metadata']['form_last_name'] ) : '';

                    if ( empty( $first_name ) && ! empty( $data['metadata']['donor_name'] ) ) {
                        $parts      = explode( ' ', sanitize_text_field( $data['metadata']['donor_name'] ), 2 );
                        $first_name = $parts[0];
                        $last_name  = isset( $parts[1] ) ? $parts[1] : '';
                    }

                    if ( empty( $first_name ) && ! empty( $data['customer_details']['name'] ) ) {
                        $parts      = explode( ' ', sanitize_text_field( $data['customer_details']['name'] ), 2 );
                        $first_name = $parts[0];
                        $last_name  = isset( $parts[1] ) ? $parts[1] : '';
                    }

                    $email     = ! empty( $data['customer_email'] ) ? sanitize_email( $data['customer_email'] ) : ( ! empty( $data['customer_details']['email'] ) ? sanitize_email( $data['customer_details']['email'] ) : '' );
                    $amount    = isset( $data['amount_total'] ) ? number_format( floatval( $data['amount_total'] ) / 100, 2 ) : '0.00';
                    $mode      = isset( $data['mode'] ) ? $data['mode'] : 'payment';
                    $frequency = ( 'subscription' === $mode || ( isset( $data['metadata']['donation_type'] ) && 'Monthly' === $data['metadata']['donation_type'] ) ) ? 'Monthly' : 'One-Time';
                }
            }
        }
    } 
    // -------------------------------------------------------------------------
    // 2. PAYPAL RETURN DATA
    // -------------------------------------------------------------------------
    else if ( isset( $_GET['token'] ) || isset( $_GET['subscription_id'] ) ) {
        $first_name = isset( $_GET['fname'] ) ? sanitize_text_field( wp_unslash( $_GET['fname'] ) ) : 'Donor';
        $last_name  = isset( $_GET['lname'] ) ? sanitize_text_field( wp_unslash( $_GET['lname'] ) ) : '';
        $email      = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';
        $amount     = isset( $_GET['amt'] ) ? number_format( floatval( $_GET['amt'] ), 2 ) : '0.00';
        $frequency  = isset( $_GET['subscription_id'] ) ? 'Monthly' : 'One-Time';
    }

    // Default fallback if parameters aren't present
    if ( empty( $first_name ) ) {
        $first_name = 'Donor';
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
