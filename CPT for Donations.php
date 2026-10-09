/**
 * Plugin Name: Custom Donation Logger (CPT & Storage)
 * Description: Automatically registers the Donation CPT, verifies gateway payments (Stripe/PayPal), and logs successful transactions with custom admin columns and details metabox.
 * Version: 1.2.0
 * Author: Razu Ahammad
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. AUTOMATICALLY REGISTER CUSTOM POST TYPE (IF NOT EXISTS)
 */
add_action( 'init', function() {
    if ( ! post_type_exists( 'donation' ) ) {
        register_post_type( 'donation', array(
            'labels' => array(
                'name'               => __( 'Donations', 'custom-donation' ),
                'singular_name'      => __( 'Donation', 'custom-donation' ),
                'add_new'            => __( 'Add Donation', 'custom-donation' ),
                'add_new_item'       => __( 'Add New Donation', 'custom-donation' ),
                'edit_item'          => __( 'View Donation', 'custom-donation' ),
                'view_item'          => __( 'View Donation', 'custom-donation' ),
                'search_items'       => __( 'Search Donations', 'custom-donation' ),
                'not_found'          => __( 'No donations found', 'custom-donation' ),
                'not_found_in_trash' => __( 'No donations found in Trash', 'custom-donation' ),
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 25,
            'menu_icon'           => 'dashicons-heart',
            'capability_type'     => 'post',
            'capabilities'        => array( 'create_posts' => false ), // Prevents manual creation without gateway verification
            'map_meta_cap'        => true,
            'supports'            => array( 'title' ),
            'has_archive'         => false,
            'exclude_from_search' => true,
        ) );
    }
});

/**
 * 2. VERIFY PAYMENT STATUS & LOG DONATION ENTRY ON RETURN
 */
add_action( 'template_redirect', function() {

    // Only intercept when user lands on success page
    if ( ! is_page( 'donation-success' ) ) {
        return;
    }

    if ( ! post_type_exists( 'donation' ) ) {
        return;
    }

    $stripe_secret   = defined( 'DONATION_STRIPE_SECRET' ) ? DONATION_STRIPE_SECRET : '';
    $paypal_client   = defined( 'DONATION_PAYPAL_CLIENT_ID' ) ? DONATION_PAYPAL_CLIENT_ID : '';
    $paypal_secret   = defined( 'DONATION_PAYPAL_CLIENT_SECRET' ) ? DONATION_PAYPAL_CLIENT_SECRET : '';
    $is_sandbox_mode = defined( 'DONATION_SANDBOX_MODE' ) ? DONATION_SANDBOX_MODE : false;

    $transaction_data = array();

    // -------------------------------------------------------------------------
    // A. STRIPE SUCCESS PROCESSING (ONE-TIME & SUBSCRIPTION)
    // -------------------------------------------------------------------------
    if ( isset( $_GET['session_id'] ) && ! empty( $_GET['session_id'] ) && ! empty( $stripe_secret ) ) {
        $session_id = sanitize_text_field( wp_unslash( $_GET['session_id'] ) );

        if ( custom_donation_exists_by_txn_id( $session_id ) ) {
            return;
        }

        $response = wp_remote_get( 'https://api.stripe.com/v1/checkout/sessions/' . $session_id, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $stripe_secret,
            ),
        ) );

        if ( ! is_wp_error( $response ) ) {
            $data = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( isset( $data['payment_status'] ) && 'paid' === $data['payment_status'] ) {

                // First attempt: Check metadata for form fields
                $first_name = ! empty( $data['metadata']['form_first_name'] ) ? sanitize_text_field( $data['metadata']['form_first_name'] ) : '';
                $last_name  = ! empty( $data['metadata']['form_last_name'] ) ? sanitize_text_field( $data['metadata']['form_last_name'] ) : '';

                // Second attempt: Fallback to donor_name metadata string
                if ( empty( $first_name ) && ! empty( $data['metadata']['donor_name'] ) ) {
                    $parts      = explode( ' ', sanitize_text_field( $data['metadata']['donor_name'] ), 2 );
                    $first_name = $parts[0];
                    $last_name  = isset( $parts[1] ) ? $parts[1] : '';
                }

                // Third attempt: Fallback to customer details from Stripe API
                if ( empty( $first_name ) && ! empty( $data['customer_details']['name'] ) ) {
                    $parts      = explode( ' ', sanitize_text_field( $data['customer_details']['name'] ), 2 );
                    $first_name = $parts[0];
                    $last_name  = isset( $parts[1] ) ? $parts[1] : '';
                }

                // Default if everything is missing
                if ( empty( $first_name ) ) {
                    $first_name = 'Anonymous';
                    $last_name  = 'Donor';
                }

                $email  = ! empty( $data['customer_email'] ) ? sanitize_email( $data['customer_email'] ) : ( ! empty( $data['customer_details']['email'] ) ? sanitize_email( $data['customer_details']['email'] ) : '' );
                $amount = isset( $data['amount_total'] ) ? ( floatval( $data['amount_total'] ) / 100 ) : 0;
                $mode   = isset( $data['mode'] ) ? $data['mode'] : 'payment';

                $transaction_data = array(
                    'first_name'     => $first_name,
                    'last_name'      => $last_name,
                    'email'          => $email,
                    'amount'         => $amount,
                    'gateway'        => 'Stripe',
                    'frequency'      => ( 'subscription' === $mode || ( isset( $data['metadata']['donation_type'] ) && 'Monthly' === $data['metadata']['donation_type'] ) ) ? 'Monthly' : 'One-Time',
                    'environment'    => $is_sandbox_mode ? 'Test / Sandbox' : 'Live',
                    'transaction_id' => $session_id,
                );
            }
        }
    }

    // -------------------------------------------------------------------------
    // B. PAYPAL SUCCESS PROCESSING (ONE-TIME & SUBSCRIPTION)
    // -------------------------------------------------------------------------
    if ( ( isset( $_GET['token'] ) || isset( $_GET['subscription_id'] ) ) && ! empty( $paypal_client ) && ! empty( $paypal_secret ) ) {
        $paypal_id = isset( $_GET['subscription_id'] ) ? sanitize_text_field( wp_unslash( $_GET['subscription_id'] ) ) : sanitize_text_field( wp_unslash( $_GET['token'] ) );

        if ( custom_donation_exists_by_txn_id( $paypal_id ) ) {
            return;
        }

        // Get Form input parameters if passed via URL
        $form_first_name = isset( $_GET['fname'] ) ? sanitize_text_field( wp_unslash( $_GET['fname'] ) ) : '';
        $form_last_name  = isset( $_GET['lname'] ) ? sanitize_text_field( wp_unslash( $_GET['lname'] ) ) : '';
        $form_email      = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';
        $form_amount     = isset( $_GET['amt'] ) ? floatval( $_GET['amt'] ) : 0;

        $api_base = $is_sandbox_mode ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

        $auth_response = wp_remote_post( $api_base . '/v1/oauth2/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $paypal_client . ':' . $paypal_secret ),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body' => 'grant_type=client_credentials',
        ) );

        if ( ! is_wp_error( $auth_response ) ) {
            $auth_data    = json_decode( wp_remote_retrieve_body( $auth_response ), true );
            $access_token = isset( $auth_data['access_token'] ) ? $auth_data['access_token'] : '';

            if ( ! empty( $access_token ) ) {

                // PayPal Subscription Verification
                if ( isset( $_GET['subscription_id'] ) ) {
                    $sub_id   = sanitize_text_field( wp_unslash( $_GET['subscription_id'] ) );
                    $sub_resp = wp_remote_get( $api_base . '/v1/billing/subscriptions/' . $sub_id, array(
                        'headers' => array( 'Authorization' => 'Bearer ' . $access_token ),
                    ) );

                    if ( ! is_wp_error( $sub_resp ) ) {
                        $sub_data = json_decode( wp_remote_retrieve_body( $sub_resp ), true );

                        if ( isset( $sub_data['status'] ) && in_array( $sub_data['status'], array( 'ACTIVE', 'APPROVED' ), true ) ) {
                            $first_name = ! empty( $form_first_name ) ? $form_first_name : ( isset( $sub_data['subscriber']['name']['given_name'] ) ? sanitize_text_field( $sub_data['subscriber']['name']['given_name'] ) : 'Donor' );
                            $last_name  = ! empty( $form_last_name ) ? $form_last_name : ( isset( $sub_data['subscriber']['name']['surname'] ) ? sanitize_text_field( $sub_data['subscriber']['name']['surname'] ) : '' );
                            $email      = ! empty( $form_email ) ? $form_email : ( isset( $sub_data['subscriber']['email_address'] ) ? sanitize_email( $sub_data['subscriber']['email_address'] ) : '' );
                            $amount     = ( $form_amount > 0 ) ? $form_amount : ( isset( $sub_data['quantity'] ) ? floatval( $sub_data['quantity'] ) : 0 );

                            $transaction_data = array(
                                'first_name'     => $first_name,
                                'last_name'      => $last_name,
                                'email'          => $email,
                                'amount'         => $amount,
                                'gateway'        => 'PayPal',
                                'frequency'      => 'Monthly',
                                'environment'    => $is_sandbox_mode ? 'Test / Sandbox' : 'Live',
                                'transaction_id' => $sub_id,
                            );
                        }
                    }
                } 
                // PayPal One-Time Order Verification
                else if ( isset( $_GET['token'] ) ) {
                    $order_id   = sanitize_text_field( wp_unslash( $_GET['token'] ) );
                    $order_resp = wp_remote_get( $api_base . '/v2/checkout/orders/' . $order_id, array(
                        'headers' => array( 'Authorization' => 'Bearer ' . $access_token ),
                    ) );

                    if ( ! is_wp_error( $order_resp ) ) {
                        $order_data = json_decode( wp_remote_retrieve_body( $order_resp ), true );

                        if ( isset( $order_data['status'] ) && in_array( $order_data['status'], array( 'COMPLETED', 'APPROVED' ), true ) ) {
                            $first_name = ! empty( $form_first_name ) ? $form_first_name : ( isset( $order_data['payer']['name']['given_name'] ) ? sanitize_text_field( $order_data['payer']['name']['given_name'] ) : 'Donor' );
                            $last_name  = ! empty( $form_last_name ) ? $form_last_name : ( isset( $order_data['payer']['name']['surname'] ) ? sanitize_text_field( $order_data['payer']['name']['surname'] ) : '' );
                            $email      = ! empty( $form_email ) ? $form_email : ( isset( $order_data['payer']['email_address'] ) ? sanitize_email( $order_data['payer']['email_address'] ) : '' );
                            $amount     = ( $form_amount > 0 ) ? $form_amount : ( isset( $order_data['purchase_units'][0]['amount']['value'] ) ? floatval( $order_data['purchase_units'][0]['amount']['value'] ) : 0 );

                            $transaction_data = array(
                                'first_name'     => $first_name,
                                'last_name'      => $last_name,
                                'email'          => $email,
                                'amount'         => $amount,
                                'gateway'        => 'PayPal',
                                'frequency'      => 'One-Time',
                                'environment'    => $is_sandbox_mode ? 'Test / Sandbox' : 'Live',
                                'transaction_id' => $order_id,
                            );
                        }
                    }
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // C. SAVE ENTRY TO CPT WITH AUTO-INCREMENT SERIAL NUMBER
    // -------------------------------------------------------------------------
    if ( ! empty( $transaction_data ) ) {
        
        // 1. Check total count of existing donations
        $existing_donations = wp_count_posts( 'donation' );
        $published_count    = isset( $existing_donations->publish ) ? intval( $existing_donations->publish ) : 0;

        // 2. Reset serial counter if no published donations exist
        if ( 0 === $published_count ) {
            update_option( 'custom_donation_serial_count', 0 );
        }

        // 3. Get current serial counter and increment by 1
        $current_serial = intval( get_option( 'custom_donation_serial_count', 0 ) ) + 1;

        // 4. Insert post first
        $post_id = wp_insert_post( array(
            'post_type'   => 'donation',
            'post_title'  => 'Donation Record',
            'post_status' => 'publish',
        ) );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            
            // 5. Save serial counter update to database
            update_option( 'custom_donation_serial_count', $current_serial );

            // 6. Save meta key data + serial number
            $transaction_data['serial_number'] = $current_serial;
            
            foreach ( $transaction_data as $key => $val ) {
                update_post_meta( $post_id, '_donation_' . $key, $val );
            }

            // 7. Format Clean Title: "Donation #1 – First Last ($25.00)"
            $clean_title = sprintf(
                'Donation #%d – %s %s ($%s)',
                $current_serial,
                $transaction_data['first_name'],
                $transaction_data['last_name'],
                number_format( floatval( $transaction_data['amount'] ), 2 )
            );

            wp_update_post( array(
                'ID'         => $post_id,
                'post_title' => trim( $clean_title ),
            ) );
			
			
			// ADD THIS LINE HERE: Fires email only AFTER all meta fields exist in DB
            do_action( 'custom_donation_logged', $post_id );
			
			
        }
    }
    
});

/**
 * Helper: Check if transaction has already been logged
 */
function custom_donation_exists_by_txn_id( $txn_id ) {
    $existing = get_posts( array(
        'post_type'      => 'donation',
        'meta_key'       => '_donation_transaction_id',
        'meta_value'     => $txn_id,
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'post_status'    => 'any',
    ) );
    return ! empty( $existing );
}

/**
 * 3. CUSTOM ADMIN LIST COLUMNS
 * 
        'title'       => __( 'Donation Record', 'custom-donation' ),
		

 */
add_filter( 'manage_donation_posts_columns', function( $columns ) {
    return array(
        'cb'          => '<input type="checkbox" />',
        'first_name'  => __( 'First Name', 'custom-donation' ),
        'email'       => __( 'Email', 'custom-donation' ),
        'amount'      => __( 'Amount', 'custom-donation' ),
        'frequency'   => __( 'Frequency', 'custom-donation' ),
        'gateway'     => __( 'Gateway', 'custom-donation' ),
        'environment' => __( 'Mode', 'custom-donation' ),
        'date'        => __( 'Date', 'custom-donation' ),
    );
});

add_action( 'manage_donation_posts_custom_column', function( $column, $post_id ) {
    switch ( $column ) {
        case 'first_name':
            $first_name = get_post_meta( $post_id, '_donation_first_name', true );
            $edit_link  = get_edit_post_link( $post_id );
            echo '<a class="row-title" href="' . esc_url( $edit_link ) . '">' . esc_html( $first_name ? $first_name : 'N/A' ) . '</a>';
            break;

        case 'email':
            $email = get_post_meta( $post_id, '_donation_email', true );
            echo esc_html( $email ? $email : '—' );
            break;

        case 'amount':
            $amount = get_post_meta( $post_id, '_donation_amount', true );
            echo esc_html( '$' . number_format( floatval( $amount ), 2 ) );
            break;

        case 'frequency':
            echo esc_html( get_post_meta( $post_id, '_donation_frequency', true ) );
            break;

        case 'gateway':
            echo esc_html( get_post_meta( $post_id, '_donation_gateway', true ) );
            break;

        case 'environment':
            $env = get_post_meta( $post_id, '_donation_environment', true );
            $badge_style = ( 'Test / Sandbox' === $env ) ? 'background:#e74c3c;color:#fff;' : 'background:#2ecc71;color:#fff;';
            echo '<span style="padding: 2px 8px; border-radius: 3px; font-weight: 600; font-size: 11px; ' . esc_attr( $badge_style ) . '">' . esc_html( $env ) . '</span>';
            break;
    }
}, 10, 2 );

/**
 * 4. DETAILED METABOX ON EDIT / VIEW SCREEN
 */
add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'donation_details_metabox',
        __( 'Donation & Payment Details', 'custom-donation' ),
        'custom_render_donation_details_metabox',
        'donation',
        'normal',
        'high'
    );
});

function custom_render_donation_details_metabox( $post ) {
    $first_name     = get_post_meta( $post->ID, '_donation_first_name', true );
    $last_name      = get_post_meta( $post->ID, '_donation_last_name', true );
    $email          = get_post_meta( $post->ID, '_donation_email', true );
    $amount         = get_post_meta( $post->ID, '_donation_amount', true );
    $gateway        = get_post_meta( $post->ID, '_donation_gateway', true );
    $frequency      = get_post_meta( $post->ID, '_donation_frequency', true );
    $environment    = get_post_meta( $post->ID, '_donation_environment', true );
    $transaction_id = get_post_meta( $post->ID, '_donation_transaction_id', true );
    ?>
    <style>
        .donation-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px; }
        .donation-detail-card { background: #f9f9f9; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 6px; }
        .donation-detail-card strong { display: block; font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 4px; }
        .donation-detail-card span { font-size: 15px; font-weight: 600; color: #1e293b; }
    </style>
    <div class="donation-details-grid">
        <div class="donation-detail-card">
            <strong>First Name</strong>
            <span><?php echo esc_html( $first_name ? $first_name : '—' ); ?></span>
        </div>
        <div class="donation-detail-card">
            <strong>Last Name</strong>
            <span><?php echo esc_html( $last_name ? $last_name : '—' ); ?></span>
        </div>
        <div class="donation-detail-card">
            <strong>Email Address</strong>
            <span><?php echo esc_html( $email ? $email : '—' ); ?></span>
        </div>
        <div class="donation-detail-card">
            <strong>Donation Amount</strong>
            <span><?php echo esc_html( '$' . number_format( floatval( $amount ), 2 ) ); ?></span>
        </div>
        <div class="donation-detail-card">
            <strong>Payment Gateway</strong>
            <span><?php echo esc_html( $gateway ? $gateway : '—' ); ?></span>
        </div>
        <div class="donation-detail-card">
            <strong>Frequency</strong>
            <span><?php echo esc_html( $frequency ? $frequency : '—' ); ?></span>
        </div>
        <div class="donation-detail-card">
            <strong>Environment</strong>
            <span><?php echo esc_html( $environment ? $environment : '—' ); ?></span>
        </div>
        <div class="donation-detail-card">
            <strong>Transaction / Order ID</strong>
            <span style="font-family: monospace; font-size: 13px;"><?php echo esc_html( $transaction_id ? $transaction_id : '—' ); ?></span>
        </div>
    </div>
    <?php
}
