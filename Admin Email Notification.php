/**
 * Plugin / Snippet Name: Custom Donation Admin Email Notification
 * Description: Sends an HTML email to the site admin whenever a new Donation CPT record is fully logged.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Hook into our custom action that runs AFTER all post meta is saved
add_action( 'custom_donation_logged', 'custom_send_admin_donation_email', 10, 1 );

function custom_send_admin_donation_email( $post_id ) {

    if ( 'donation' !== get_post_type( $post_id ) ) {
        return;
    }

    // Fetch saved meta details
    $first_name     = get_post_meta( $post_id, '_donation_first_name', true );
    $last_name      = get_post_meta( $post_id, '_donation_last_name', true );
    $email          = get_post_meta( $post_id, '_donation_email', true );
    $amount         = get_post_meta( $post_id, '_donation_amount', true );
    $gateway        = get_post_meta( $post_id, '_donation_gateway', true );
    $frequency      = get_post_meta( $post_id, '_donation_frequency', true );
    $environment    = get_post_meta( $post_id, '_donation_environment', true );
    $transaction_id = get_post_meta( $post_id, '_donation_transaction_id', true );
    $serial_number  = get_post_meta( $post_id, '_donation_serial_number', true );

    // Admin Email Configuration
    $admin_email = get_option( 'admin_email' );
    $site_name   = get_bloginfo( 'name' );
    $subject     = sprintf( 'Someone just donated on your site [%s]', $site_name );

    // Details Payload
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
    <head>
        <meta charset="UTF-8">
    </head>
    <body style="font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px;">
        <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <h2 style="color: #1e293b; margin-top: 0;">New Donation Received!</h2>
            <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">A new donation has been successfully processed and logged into your site.</p>
            
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <?php foreach ( $details as $label => $val ) : ?>
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #e2e8f0; font-weight: bold; color: #475569; width: 40%;"><?php echo esc_html( $label ); ?>:</td>
                        <td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #0f172a;"><?php echo esc_html( $val ? $val : '—' ); ?></td>
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

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <' . $admin_email . '>',
    );

    wp_mail( $admin_email, $subject, $message, $headers );
}
