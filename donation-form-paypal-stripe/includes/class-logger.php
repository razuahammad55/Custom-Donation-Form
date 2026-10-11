<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'template_redirect', function() {
    if ( isset( $_POST['onetime_form_token'] ) && 'process_custom_onetime_donation' === $_POST['onetime_form_token'] ) {
        cdf_process_gateway_request( 'one_time' );
    }

    if ( isset( $_POST['monthly_form_token'] ) && 'process_custom_monthly_donation' === $_POST['monthly_form_token'] ) {
        cdf_process_gateway_request( 'monthly' );
    }
});

function cdf_process_gateway_request( $type ) {
    $chosen_gateway = isset( $_POST['payment_method'] ) ? sanitize_text_field( $_POST['payment_method'] ) : 'paypal';
    $custom_amount  = isset( $_POST['donation_amount'] ) ? floatval( $_POST['donation_amount'] ) : 0;
    $email          = isset( $_POST['donor_email'] ) ? sanitize_email( $_POST['donor_email'] ) : '';
    $first_name     = isset( $_POST['donor_first'] ) ? sanitize_text_field( $_POST['donor_first'] ) : '';
    $last_name      = isset( $_POST['donor_last'] ) ? sanitize_text_field( $_POST['donor_last'] ) : '';

    if ( $custom_amount <= 0 ) {
        wp_die( 'Please enter a valid donation amount.', 'Invalid Amount', array( 'back_link' => true ) );
    }

    $settings      = get_option( 'cdf_api_settings', array() );
    $is_sandbox    = ( 'sandbox' === get_option( 'cdf_environment', 'sandbox' ) );
    
    $stripe_secret = isset( $settings['stripe_secret'] ) ? trim( $settings['stripe_secret'] ) : '';
    $paypal_client = isset( $settings['paypal_client'] ) ? trim( $settings['paypal_client'] ) : '';
    $paypal_secret = isset( $settings['paypal_secret'] ) ? trim( $settings['paypal_secret'] ) : '';
    $paypal_plan   = isset( $settings['paypal_plan_id'] ) ? trim( $settings['paypal_plan_id'] ) : '';

    if ( 'stripe' === $chosen_gateway ) {
        if ( empty( $stripe_secret ) ) {
            wp_die( 'Stripe Configuration Error: Secret key is missing in plugin settings.' );
        }

        if ( 'one_time' === $type ) {
            $session_response = wp_remote_post( 'https://api.stripe.com/v1/checkout/sessions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $stripe_secret,
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'payment_method_types[0]'                       => 'card',
                    'mode'                                          => 'payment',
                    'customer_email'                                => $email,
                    'line_items[0][price_data][currency]'           => 'usd',
                    'line_items[0][price_data][unit_amount]'        => round( $custom_amount * 100 ),
                    'line_items[0][price_data][product_data][name]' => 'One-Time Donation',
                    'line_items[0][quantity]'                       => 1,
                    'success_url'                                   => home_url( '/donation-success/?session_id={CHECKOUT_SESSION_ID}' ),
                    'cancel_url'                                    => esc_url_raw( home_url( '/donation-canceled/' ) ),
                    'metadata[form_first_name]'                     => $first_name,
                    'metadata[form_last_name]'                      => $last_name,
                    'metadata[donation_type]'                       => 'One-Time',
                ],
            ]);
        } else {
            $price_response = wp_remote_post( 'https://api.stripe.com/v1/prices', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $stripe_secret,
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
                wp_die( 'Stripe Price Error.' );
            }

            $price_data = json_decode( wp_remote_retrieve_body( $price_response ), true );
            $price_id   = isset( $price_data['id'] ) ? $price_data['id'] : '';

            $session_response = wp_remote_post( 'https://api.stripe.com/v1/checkout/sessions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $stripe_secret,
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'payment_method_types[0]'   => 'card',
                    'mode'                      => 'subscription',
                    'customer_email'            => $email,
                    'line_items[0][price]'      => $price_id,
                    'line_items[0][quantity]'   => 1,
                    'success_url'               => home_url( '/donation-success/?session_id={CHECKOUT_SESSION_ID}' ),
                    'cancel_url'                => esc_url_raw( home_url( '/donation-canceled/' ) ),
                    'metadata[form_first_name]' => $first_name,
                    'metadata[form_last_name]'  => $last_name,
                    'metadata[donation_type]'   => 'Monthly',
                ],
            ]);
        }

        if ( is_wp_error( $session_response ) ) {
            wp_die( 'Stripe Checkout Error.' );
        }

        $session_data = json_decode( wp_remote_retrieve_body( $session_response ), true );
        if ( ! empty( $session_data['url'] ) ) {
            wp_redirect( esc_url_raw( $session_data['url'] ) );
            exit;
        }
        wp_die( 'Stripe failed to return checkout URL.' );
    }

    if ( 'paypal' === $chosen_gateway ) {
        if ( empty( $paypal_client ) || empty( $paypal_secret ) ) {
            wp_die( 'PayPal API Configuration Error: Client ID or Secret is missing in plugin settings.' );
        }

        $api_base = $is_sandbox ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

        $auth_response = wp_remote_post( $api_base . '/v1/oauth2/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $paypal_client . ':' . $paypal_secret ),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body' => 'grant_type=client_credentials',
        ));

        if ( is_wp_error( $auth_response ) ) {
            wp_die( 'PayPal Authentication Error.' );
        }

        $auth_data    = json_decode( wp_remote_retrieve_body( $auth_response ), true );
        $access_token = isset( $auth_data['access_token'] ) ? $auth_data['access_token'] : '';

        if ( empty( $access_token ) ) {
            wp_die( 'Failed to retrieve PayPal access token.' );
        }

        $return_url = add_query_arg( array(
            'fname' => urlencode( $first_name ),
            'lname' => urlencode( $last_name ),
            'email' => urlencode( $email ),
            'amt'   => urlencode( number_format( $custom_amount, 2, '.', '' ) ),
        ), home_url( '/donation-success/' ) );

        if ( 'one_time' === $type ) {
            $order_payload = array(
                'intent' => 'CAPTURE',
                'purchase_units' => array(
                    array(
                        'amount' => array( 'currency_code' => 'USD', 'value' => number_format( $custom_amount, 2, '.', '' ) ),
                        'description' => 'One-Time Donation',
                    ),
                ),
                'payer' => array(
                    'email_address' => $email,
                    'name' => array( 'given_name' => $first_name, 'surname' => $last_name ),
                ),
                'application_context' => array(
                    'brand_name'  => get_bloginfo( 'name' ),
                    'user_action' => 'PAY_NOW',
                    'return_url'  => esc_url_raw( $return_url ),
                    'cancel_url'  => esc_url_raw( home_url( '/donation-canceled/' ) ),
                ),
            );
            $endpoint = $api_base . '/v2/checkout/orders';
        } else {
            $quantity = max( 1, (int) round( $custom_amount ) );
            $order_payload = array(
                'plan_id'    => $paypal_plan,
                'quantity'   => (string) $quantity,
                'subscriber' => array(
                    'email_address' => $email,
                    'name' => array( 'given_name' => $first_name, 'surname' => $last_name ),
                ),
                'application_context' => array(
                    'brand_name'  => get_bloginfo( 'name' ),
                    'user_action' => 'SUBSCRIBE_NOW',
                    'return_url'  => esc_url_raw( $return_url ),
                    'cancel_url'  => esc_url_raw( home_url( '/donation-canceled/' ) ),
                ),
            );
            $endpoint = $api_base . '/v1/billing/subscriptions';
        }

        $response = wp_remote_post( $endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
            'body' => json_encode( $order_payload ),
        ));

        if ( is_wp_error( $response ) ) {
            wp_die( 'PayPal API Connection Error.' );
        }

        $resp_data   = json_decode( wp_remote_retrieve_body( $response ), true );
        $approve_url = '';

        if ( ! empty( $resp_data['links'] ) ) {
            foreach ( $resp_data['links'] as $link ) {
                if ( isset( $link['rel'] ) && ( 'approve' === $link['rel'] || 'payer-action' === $link['rel'] ) ) {
                    $approve_url = $link['href'];
                    break;
                }
            }
        }

        if ( ! empty( $approve_url ) ) {
            wp_redirect( esc_url_raw( $approve_url ) );
            exit;
        }
        wp_die( 'PayPal failed to return approval link.' );
    }
}

add_action( 'template_redirect', function() {
    if ( ! is_page( 'donation-success' ) ) {
        return;
    }

    $settings      = get_option( 'cdf_api_settings', array() );
    $is_sandbox    = ( 'sandbox' === get_option( 'cdf_environment', 'sandbox' ) );
    $stripe_secret = isset( $settings['stripe_secret'] ) ? trim( $settings['stripe_secret'] ) : '';
    $paypal_client = isset( $settings['paypal_client'] ) ? trim( $settings['paypal_client'] ) : '';
    $paypal_secret = isset( $settings['paypal_secret'] ) ? trim( $settings['paypal_secret'] ) : '';

    $transaction_data = array();

    if ( isset( $_GET['session_id'] ) && ! empty( $_GET['session_id'] ) && ! empty( $stripe_secret ) ) {
        $session_id = sanitize_text_field( wp_unslash( $_GET['session_id'] ) );

        if ( cdf_donation_exists_by_txn_id( $session_id ) ) {
            return;
        }

        $response = wp_remote_get( 'https://api.stripe.com/v1/checkout/sessions/' . $session_id, array(
            'headers' => array( 'Authorization' => 'Bearer ' . $stripe_secret ),
        ) );

        if ( ! is_wp_error( $response ) ) {
            $data = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( isset( $data['payment_status'] ) && 'paid' === $data['payment_status'] ) {
                $first_name = ! empty( $data['metadata']['form_first_name'] ) ? sanitize_text_field( $data['metadata']['form_first_name'] ) : ( ! empty( $data['customer_details']['name'] ) ? explode( ' ', $data['customer_details']['name'] )[0] : 'Donor' );
                $last_name  = ! empty( $data['metadata']['form_last_name'] ) ? sanitize_text_field( $data['metadata']['form_last_name'] ) : '';
                $email      = ! empty( $data['customer_email'] ) ? sanitize_email( $data['customer_email'] ) : ( ! empty( $data['customer_details']['email'] ) ? sanitize_email( $data['customer_details']['email'] ) : '' );
                $amount     = isset( $data['amount_total'] ) ? ( floatval( $data['amount_total'] ) / 100 ) : 0;
                $mode       = isset( $data['mode'] ) ? $data['mode'] : 'payment';

                $transaction_data = array(
                    'first_name'     => $first_name,
                    'last_name'      => $last_name,
                    'email'          => $email,
                    'amount'         => $amount,
                    'gateway'        => 'Stripe',
                    'frequency'      => ( 'subscription' === $mode || ( isset( $data['metadata']['donation_type'] ) && 'Monthly' === $data['metadata']['donation_type'] ) ) ? 'Monthly' : 'One-Time',
                    'environment'    => $is_sandbox ? 'Test / Sandbox' : 'Live',
                    'transaction_id' => $session_id,
                );
            }
        }
    }

    if ( ( isset( $_GET['token'] ) || isset( $_GET['subscription_id'] ) ) && ! empty( $paypal_client ) && ! empty( $paypal_secret ) ) {
        $paypal_id = isset( $_GET['subscription_id'] ) ? sanitize_text_field( wp_unslash( $_GET['subscription_id'] ) ) : sanitize_text_field( wp_unslash( $_GET['token'] ) );

        if ( cdf_donation_exists_by_txn_id( $paypal_id ) ) {
            return;
        }

        $form_first_name = isset( $_GET['fname'] ) ? sanitize_text_field( wp_unslash( $_GET['fname'] ) ) : '';
        $form_last_name  = isset( $_GET['lname'] ) ? sanitize_text_field( wp_unslash( $_GET['lname'] ) ) : '';
        $form_email      = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';
        $form_amount     = isset( $_GET['amt'] ) ? floatval( $_GET['amt'] ) : 0;

        $api_base = $is_sandbox ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

        $auth_resp = wp_remote_post( $api_base . '/v1/oauth2/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $paypal_client . ':' . $paypal_secret ),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body' => 'grant_type=client_credentials',
        ) );

        if ( ! is_wp_error( $auth_resp ) ) {
            $auth_data    = json_decode( wp_remote_retrieve_body( $auth_resp ), true );
            $access_token = isset( $auth_data['access_token'] ) ? $auth_data['access_token'] : '';

            if ( ! empty( $access_token ) ) {
                if ( isset( $_GET['subscription_id'] ) ) {
                    $sub_resp = wp_remote_get( $api_base . '/v1/billing/subscriptions/' . $paypal_id, array(
                        'headers' => array( 'Authorization' => 'Bearer ' . $access_token ),
                    ) );
                    if ( ! is_wp_error( $sub_resp ) ) {
                        $sub_data = json_decode( wp_remote_retrieve_body( $sub_resp ), true );
                        if ( isset( $sub_data['status'] ) ) {
                            $transaction_data = array(
                                'first_name'     => ! empty( $form_first_name ) ? $form_first_name : 'Donor',
                                'last_name'      => $form_last_name,
                                'email'          => $form_email,
                                'amount'         => $form_amount,
                                'gateway'        => 'PayPal',
                                'frequency'      => 'Monthly',
                                'environment'    => $is_sandbox ? 'Test / Sandbox' : 'Live',
                                'transaction_id' => $paypal_id,
                            );
                        }
                    }
                } else {
                    $order_resp = wp_remote_get( $api_base . '/v2/checkout/orders/' . $paypal_id, array(
                        'headers' => array( 'Authorization' => 'Bearer ' . $access_token ),
                    ) );
                    if ( ! is_wp_error( $order_resp ) ) {
                        $order_data = json_decode( wp_remote_retrieve_body( $order_resp ), true );
                        if ( isset( $order_data['status'] ) ) {
                            $transaction_data = array(
                                'first_name'     => ! empty( $form_first_name ) ? $form_first_name : 'Donor',
                                'last_name'      => $form_last_name,
                                'email'          => $form_email,
                                'amount'         => $form_amount,
                                'gateway'        => 'PayPal',
                                'frequency'      => 'One-Time',
                                'environment'    => $is_sandbox ? 'Test / Sandbox' : 'Live',
                                'transaction_id' => $paypal_id,
                            );
                        }
                    }
                }
            }
        }
    }

    if ( ! empty( $transaction_data ) ) {
        $existing_donations = wp_count_posts( 'donation' );
        $published_count    = isset( $existing_donations->publish ) ? intval( $existing_donations->publish ) : 0;

        if ( 0 === $published_count ) {
            update_option( 'cdf_serial_count', 0 );
        }

        $current_serial = intval( get_option( 'cdf_serial_count', 0 ) ) + 1;

        $post_id = wp_insert_post( array(
            'post_type'   => 'donation',
            'post_title'  => 'Donation Record',
            'post_status' => 'publish',
        ) );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            update_option( 'cdf_serial_count', $current_serial );
            $transaction_data['serial_number'] = $current_serial;

            foreach ( $transaction_data as $key => $val ) {
                update_post_meta( $post_id, '_donation_' . $key, $val );
            }

            $clean_title = sprintf(
                'Donation #%d – %s %s ($%s)',
                $current_serial,
                $transaction_data['first_name'],
                $transaction_data['last_name'],
                number_format( floatval( $transaction_data['amount'] ), 2 )
            );

            wp_update_post( array( 'ID' => $post_id, 'post_title' => trim( $clean_title ) ) );

            do_action( 'cdf_donation_logged', $post_id );
        }
    }
});

function cdf_donation_exists_by_txn_id( $txn_id ) {
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
