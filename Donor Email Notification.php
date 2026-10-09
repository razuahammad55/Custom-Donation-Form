/**
 * Plugin / Snippet Name: Custom Donation Donor Email Notification
 * Description: Sends an HTML thank-you receipt email to the donor whenever a new donation is logged.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Hook into our custom action that runs AFTER all post meta is saved
add_action( 'custom_donation_logged', 'custom_send_donor_thankyou_email', 10, 1 );

function custom_send_donor_thankyou_email( $post_id ) {

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
    $serial_number  = get_post_meta( $post_id, '_donation_serial_number', true );
    $transaction_id = get_post_meta( $post_id, '_donation_transaction_id', true );

    // Prevent sending if no donor email exists
    if ( empty( $email ) ) {
        return;
    }

    $site_name   = get_bloginfo( 'name' );
    $admin_email = get_option( 'admin_email' );
    $full_name   = trim( $first_name . ' ' . $last_name );

    // Subject Line: Personalized thank you message
    $subject = sprintf( 'Thank you for your donation, %s! [%s]', $first_name ? $first_name : 'Donor', $site_name );

    // Details Payload for Receipt
    $details = array(
        'Receipt / Serial #'     => '#' . ( $serial_number ? $serial_number : $post_id ),
        'Donor Name'             => $full_name ? $full_name : 'Valued Donor',
        'Email Address'          => $email,
        'Donation Amount'        => '$' . number_format( floatval( $amount ), 2 ),
        'Frequency'              => $frequency ? $frequency : 'One-Time',
        'Payment Method'         => $gateway,
        'Transaction ID'         => $transaction_id,
    );

    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
    </head>
    <body style="font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px;">
        <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; padding: 28px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="color: #0f172a; margin-top: 0; font-size: 22px;">Thank You for Your Generosity!</h2>
                <p style="color: #64748b; font-size: 15px; margin-bottom: 0;">Dear <?php echo esc_html( $first_name ? $first_name : 'Donor' ); ?>, we have successfully received your donation. Below is your official receipt for your records.</p>
            </div>
            
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <?php foreach ( $details as $label => $val ) : ?>
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #e2e8f0; font-weight: bold; color: #475569; width: 45%;"><?php echo esc_html( $label ); ?>:</td>
                        <td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #0f172a;"><?php echo esc_html( $val ? $val : '—' ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <div style="margin-top: 28px; padding-top: 20px; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 13px;">
                <p style="margin: 0;">If you have any questions regarding your contribution, please feel free to reply to this email.</p>
                <p style="margin: 6px 0 0 0;"><strong><?php echo esc_html( $site_name ); ?></strong></p>
            </div>
        </div>
    </body>
    </html>
    <?php
    $message = ob_get_clean();

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <' . $admin_email . '>',
        'Reply-To: ' . $admin_email,
    );

    wp_mail( $email, $subject, $message, $headers );
}
