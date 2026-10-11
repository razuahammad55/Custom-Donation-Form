<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'cdf_donation_logged', 'cdf_send_notification_emails', 10, 1 );

function cdf_send_notification_emails( $post_id ) {
    if ( 'donation' !== get_post_type( $post_id ) ) {
        return;
    }

    $first_name     = get_post_meta( $post_id, '_donation_first_name', true );
    $last_name      = get_post_meta( $post_id, '_donation_last_name', true );
    $email          = get_post_meta( $post_id, '_donation_email', true );
    $amount         = get_post_meta( $post_id, '_donation_amount', true );
    $gateway        = get_post_meta( $post_id, '_donation_gateway', true );
    $frequency      = get_post_meta( $post_id, '_donation_frequency', true );
    $environment    = get_post_meta( $post_id, '_donation_environment', true );
    $transaction_id = get_post_meta( $post_id, '_donation_transaction_id', true );
    $serial_number  = get_post_meta( $post_id, '_donation_serial_number', true );

    $site_name   = get_bloginfo( 'name' );
    $admin_email = get_option( 'admin_email' );

    $admin_enabled = get_option( 'cdf_admin_email_enabled', '1' );
    if ( '1' === $admin_enabled ) {
        $recipients_str = get_option( 'cdf_admin_email_recipients', $admin_email );
        $recipients     = array_map( 'trim', explode( ',', $recipients_str ) );
        $subject        = sprintf( 'Someone just donated on your site [%s]', $site_name );

        $details = array(
            'Serial Number'          => '#' . ( $serial_number ? $serial_number : $post_id ),
            'First Name'             => $first_name,
            'Last Name'              => $last_name,
            'Email Address'          => $email,
            'Donation Amount'        => '$' . number_format( floatval( $amount ), 2 ),
            'Payment Gateway'        => $gateway,
            'Frequency'              => $frequency,
            'Environment'            => $environment,
            'Transaction / Order ID' => $transaction_id,
        );

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"></head>
        <body style="font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px;">
            <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; padding: 24px;">
                <h2 style="color: #1e293b; margin-top: 0;">New Donation Received!</h2>
                <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">A new donation has been successfully processed and logged into your site.</p>
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    <?php foreach ( $details as $label => $val ) : ?>
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #e2e8f0; font-weight: bold; color: #475569; width: 40%;"><?php echo esc_html( $label ); ?>:</td>
                            <td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #0f172a;"><?php echo esc_html( $val ?: '—' ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <div style="margin-top: 24px; text-align: center;">
                    <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $post_id . '&action=edit' ) ); ?>" style="background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 12px 20px; border-radius: 6px; font-weight: bold; display: inline-block;">View Donation in Admin</a>
                </div>
            </div>
        </body>
        </html>
        <?php
        $message = ob_get_clean();
        $headers = array( 'Content-Type: text/html; charset=UTF-8', 'From: ' . $site_name . ' <' . $admin_email . '>' );

        foreach ( $recipients as $recipient ) {
            if ( is_email( $recipient ) ) {
                wp_mail( $recipient, $subject, $message, $headers );
            }
        }
    }

    $donor_enabled = get_option( 'cdf_donor_email_enabled', '1' );
    if ( '1' === $donor_enabled && ! empty( $email ) ) {
        $full_name = trim( $first_name . ' ' . $last_name );
        $subject   = sprintf( 'Thank you for your donation, %s! [%s]', $first_name ? $first_name : 'Donor', $site_name );

        $donor_details = array(
            'Receipt / Serial #'     => '#' . ( $serial_number ? $serial_number : $post_id ),
            'Donor Name'             => $full_name ?: 'Valued Donor',
            'Email Address'          => $email,
            'Donation Amount'        => '$' . number_format( floatval( $amount ), 2 ),
            'Frequency'              => $frequency ?: 'One-Time',
            'Payment Method'         => $gateway,
            'Transaction ID'         => $transaction_id,
        );

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"></head>
        <body style="font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px;">
            <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; padding: 28px;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <h2 style="color: #0f172a; margin-top: 0; font-size: 22px;">Thank You for Your Generosity!</h2>
                    <p style="color: #64748b; font-size: 15px; margin-bottom: 0;">Dear <?php echo esc_html( $first_name ?: 'Donor' ); ?>, we have successfully received your donation. Below is your official receipt.</p>
                </div>
                <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                    <?php foreach ( $donor_details as $label => $val ) : ?>
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #e2e8f0; font-weight: bold; color: #475569; width: 45%;"><?php echo esc_html( $label ); ?>:</td>
                            <td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #0f172a;"><?php echo esc_html( $val ?: '—' ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <div style="margin-top: 28px; padding-top: 20px; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 13px;">
                    <p style="margin: 0;">If you have any questions, please feel free to reply to this email.</p>
                    <p style="margin: 6px 0 0 0;"><strong><?php echo esc_html( $site_name ); ?></strong></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        $donor_message = ob_get_clean();
        $donor_headers = array( 'Content-Type: text/html; charset=UTF-8', 'From: ' . $site_name . ' <' . $admin_email . '>', 'Reply-To: ' . $admin_email );

        wp_mail( $email, $subject, $donor_message, $donor_headers );
    }
}
