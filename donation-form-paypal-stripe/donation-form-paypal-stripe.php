<?php
/**
 * Plugin Name: Donation Form with PayPal and Stripe
 * Plugin URI:  https://github.com/razu/donation-form-paypal-stripe
 * Description: A secure, modular WordPress donation plugin supporting Stripe Checkout and PayPal REST API for One-Time and Monthly Recurring donations with custom admin management, styling options, and email notifications.
 * Version:     1.1.0
 * Author:      Razu Ahammad
 * Text Domain: donation-form-paypal-stripe
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define Plugin Constants with cdf prefix
define( 'CDF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CDF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Activation Hook: Register CPT, flush rewrites, create default pages, and set default options.
 */
register_activation_hook( __FILE__, 'cdf_plugin_activate' );
function cdf_plugin_activate() {
    require_once CDF_PLUGIN_DIR . 'includes/class-cpt.php';
    if ( function_exists( 'cdf_register_donation_cpt' ) ) {
        cdf_register_donation_cpt();
    }
    flush_rewrite_rules();

    // Automatically Create Success & Cancel Pages if they don't exist
    if ( ! get_page_by_path( 'donation-success' ) ) {
        wp_insert_post( array(
            'post_title'     => 'Donation Success',
            'post_name'      => 'donation-success',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'post_content'   => '[donation_receipt]',
        ) );
    }

    if ( ! get_page_by_path( 'donation-canceled' ) ) {
        wp_insert_post( array(
            'post_title'     => 'Donation Canceled',
            'post_name'      => 'donation-canceled',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'post_content'   => '<div style="max-width:500px;margin:40px auto;text-align:center;padding:30px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;"><h2>Donation Canceled</h2><p>Your donation process was canceled. No charges were made.</p><a href="' . home_url() . '" style="display:inline-block;margin-top:15px;padding:10px 20px;background:#3182ce;color:#fff;text-decoration:none;border-radius:6px;">Return Home</a></div>',
        ) );
    }

    // Initialize Default Settings & Toggles with cdf prefix
    if ( false === get_option( 'cdf_admin_email_enabled' ) ) {
        update_option( 'cdf_admin_email_enabled', '1' );
    }
    if ( false === get_option( 'cdf_donor_email_enabled' ) ) {
        update_option( 'cdf_donor_email_enabled', '1' );
    }
    if ( false === get_option( 'cdf_admin_email_recipients' ) ) {
        update_option( 'cdf_admin_email_recipients', get_option( 'admin_email' ) );
    }
    if ( false === get_option( 'cdf_environment' ) ) {
        update_option( 'cdf_environment', 'sandbox' );
    }
}

/**
 * Deactivation Hook
 */
register_deactivation_hook( __FILE__, 'cdf_plugin_deactivate' );
function cdf_plugin_deactivate() {
    flush_rewrite_rules();
}

/**
 * Load Required Component Classes
 */
require_once CDF_PLUGIN_DIR . 'includes/class-cpt.php';
require_once CDF_PLUGIN_DIR . 'includes/class-logger.php';
require_once CDF_PLUGIN_DIR . 'includes/class-emails.php';
require_once CDF_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once CDF_PLUGIN_DIR . 'includes/class-settings.php';

/**
 * Enqueue Frontend Assets & Dynamic CSS Variables
 */
add_action( 'wp_enqueue_scripts', 'cdf_enqueue_frontend_assets' );
function cdf_enqueue_frontend_assets() {
    wp_enqueue_style( 'cdf-frontend-style', CDF_PLUGIN_URL . 'assets/css/frontend-style.css', array(), '1.1.0' );
    wp_enqueue_script( 'cdf-frontend-script', CDF_PLUGIN_URL . 'assets/js/frontend-script.js', array(), '1.1.0', true );

    $custom_css = cdf_generate_dynamic_css();
    wp_add_inline_style( 'cdf-frontend-style', $custom_css );
}

/**
 * Helper: Generate dynamic CSS root tokens from Style settings
 */
function cdf_generate_dynamic_css() {
    $styles = get_option( 'cdf_style_options', array() );
    
    $amt_bg_normal     = isset( $styles['amt_bg_normal'] ) ? $styles['amt_bg_normal'] : '#f7fafc';
    $amt_text_normal   = isset( $styles['amt_text_normal'] ) ? $styles['amt_text_normal'] : '#2d3748';
    $amt_bg_hover      = isset( $styles['amt_bg_hover'] ) ? $styles['amt_bg_hover'] : '#edf2f7';
    $amt_text_hover    = isset( $styles['amt_text_hover'] ) ? $styles['amt_text_hover'] : '#2d3748';
    $amt_bg_active     = isset( $styles['amt_bg_active'] ) ? $styles['amt_bg_active'] : '#ebf8ff';
    $amt_text_active   = isset( $styles['amt_text_active'] ) ? $styles['amt_text_active'] : '#2b6cb0';
    $amt_border_active = isset( $styles['amt_border_active'] ) ? $styles['amt_border_active'] : '#3182ce';

    $gw_bg_normal      = isset( $styles['gw_bg_normal'] ) ? $styles['gw_bg_normal'] : '#ffffff';
    $gw_text_normal    = isset( $styles['gw_text_normal'] ) ? $styles['gw_text_normal'] : '#2d3748';
    $gw_bg_hover       = isset( $styles['gw_bg_hover'] ) ? $styles['gw_bg_hover'] : '#f7fafc';
    $gw_text_hover     = isset( $styles['gw_text_hover'] ) ? $styles['gw_text_hover'] : '#2d3748';
    $gw_bg_active      = isset( $styles['gw_bg_active'] ) ? $styles['gw_bg_active'] : '#ebf8ff';
    $gw_text_active    = isset( $styles['gw_text_active'] ) ? $styles['gw_text_active'] : '#2b6cb0';
    $gw_border_active  = isset( $styles['gw_border_active'] ) ? $styles['gw_border_active'] : '#3182ce';

    $sub_bg_normal     = isset( $styles['sub_bg_normal'] ) ? $styles['sub_bg_normal'] : '#3182ce';
    $sub_text_normal   = isset( $styles['sub_text_normal'] ) ? $styles['sub_text_normal'] : '#ffffff';
    $sub_bg_hover      = isset( $styles['sub_bg_hover'] ) ? $styles['sub_bg_hover'] : '#2b6cb0';
    $sub_text_hover    = isset( $styles['sub_text_hover'] ) ? $styles['sub_text_hover'] : '#ffffff';

    return "
    :root {
        --amt-btn-bg-normal: {$amt_bg_normal};
        --amt-btn-text-normal: {$amt_text_normal};
        --amt-btn-bg-hover: {$amt_bg_hover};
        --amt-btn-text-hover: {$amt_text_hover};
        --amt-btn-bg-active: {$amt_bg_active};
        --amt-btn-text-active: {$amt_text_active};
        --amt-btn-border-active: {$amt_border_active};
        --gateway-bg-normal: {$gw_bg_normal};
        --gateway-text-normal: {$gw_text_normal};
        --gateway-bg-hover: {$gw_bg_hover};
        --gateway-text-hover: {$gw_text_hover};
        --gateway-bg-active: {$gw_bg_active};
        --gateway-text-active: {$gw_text_active};
        --gateway-border-active: {$gw_border_active};
        --submit-btn-bg-normal: {$sub_bg_normal};
        --submit-btn-text-normal: {$sub_text_normal};
        --submit-btn-bg-hover: {$sub_bg_hover};
        --submit-btn-text-hover: {$sub_text_hover};
    }";
}
