<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Sub-Menus under 'Donations' CPT (Order: Forms -> Analytics -> Style -> Email -> Settings)
 */
add_action( 'admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=donation',
        __( 'Donation Forms & Instructions', 'donation-form-paypal-stripe' ),
        __( 'Forms', 'donation-form-paypal-stripe' ),
        'manage_options',
        'cdf-forms',
        'cdf_render_forms_page'
    );

    add_submenu_page(
        'edit.php?post_type=donation',
        __( 'Donation Analytics & Revenue', 'donation-form-paypal-stripe' ),
        __( 'Analytics', 'donation-form-paypal-stripe' ),
        'manage_options',
        'cdf-analytics',
        'cdf_render_analytics_page'
    );

    add_submenu_page(
        'edit.php?post_type=donation',
        __( 'Donation Form Styles', 'donation-form-paypal-stripe' ),
        __( 'Style', 'donation-form-paypal-stripe' ),
        'manage_options',
        'cdf-style',
        'cdf_render_style_page'
    );

    add_submenu_page(
        'edit.php?post_type=donation',
        __( 'Email Notifications', 'donation-form-paypal-stripe' ),
        __( 'Email', 'donation-form-paypal-stripe' ),
        'manage_options',
        'cdf-email',
        'cdf_render_email_page'
    );

    add_submenu_page(
        'edit.php?post_type=donation',
        __( 'Gateway API Settings', 'donation-form-paypal-stripe' ),
        __( 'Settings', 'donation-form-paypal-stripe' ),
        'manage_options',
        'cdf-settings',
        'cdf_render_settings_page'
    );
});

add_action( 'admin_init', function() {
    register_setting( 'cdf_api_settings_group', 'cdf_api_settings' );
    register_setting( 'cdf_email_settings_group', 'cdf_admin_email_enabled' );
    register_setting( 'cdf_email_settings_group', 'cdf_donor_email_enabled' );
    register_setting( 'cdf_email_settings_group', 'cdf_admin_email_recipients' );
    register_setting( 'cdf_settings_group', 'cdf_environment' );
    register_setting( 'cdf_style_options_group', 'cdf_style_options' );
});

/**
 * 1. FORMS PAGE RENDER (Includes Overview & API Key Setup Guide)
 */
function cdf_render_forms_page() {
    ?>
    <div class="wrap cdf-admin-wrap">
        <h1><?php esc_html_e( 'Donation Forms & Setup Guide', 'donation-form-paypal-stripe' ); ?></h1>
        <p style="font-size: 15px; color: #475569;">Welcome to your Custom Donation Engine. Below is an overview of your plugin management panels and shortcode instructions.</p>
        
        <!-- Panel Overview -->
        <h2 style="margin-top: 25px;">Plugin Management Overview</h2>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 10px;">
            <div class="cdf-card">
                <h3>Donations</h3>
                <p>View all logged transactions, download records, and examine individual donor payment metas.</p>
            </div>
            <div class="cdf-card">
                <h3>Analytics</h3>
                <p>Track total revenue, gateway comparisons (Stripe vs PayPal), and recurring subscription metrics.</p>
            </div>
            <div class="cdf-card">
                <h3>Style</h3>
                <p>Customize button colors, hover states, and active gateway styling using Elementor-like controls.</p>
            </div>
            <div class="cdf-card">
                <h3>Email</h3>
                <p>Configure admin notification recipients and custom HTML thank-you receipt emails for donors.</p>
            </div>
            <div class="cdf-card">
                <h3>Settings</h3>
                <p>Manage API keys for Stripe and PayPal and toggle between Sandbox test mode and Live production.</p>
            </div>
            <div class="cdf-card">
                <h3>Forms</h3>
                <p>Access shortcodes and integration guidelines for embedding donation forms across your site.</p>
            </div>
        </div>

        <!-- Shortcodes Section -->
        <h2 style="margin-top: 35px;">Frontend Form Shortcodes</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 10px;">
            <div class="cdf-card">
                <h3 style="margin-top: 0; color: #1e293b;">One-Time Donation Form</h3>
                <p style="color: #64748b; font-size: 14px;">Renders a compact, high-converting one-time donation form supporting Stripe and PayPal.</p>
                <div style="background: #f1f5f9; padding: 10px 14px; font-family: monospace; font-weight: bold; border-radius: 4px; margin-bottom: 12px;">[donation_form type="one_time"]</div>
                <p style="font-size: 13px; color: #64748b;"><em>Note: You can also simply use <code>[donation_form]</code> since One-Time is the default type.</em></p>
            </div>

            <div class="cdf-card">
                <h3 style="margin-top: 0; color: #1e293b;">Monthly Recurring Donation Form</h3>
                <p style="color: #64748b; font-size: 14px;">Renders the monthly subscription donation form supporting Stripe Subscriptions and PayPal Plans.</p>
                <div style="background: #f1f5f9; padding: 10px 14px; font-family: monospace; font-weight: bold; border-radius: 4px; margin-bottom: 12px;">[donation_form type="monthly"]</div>
                <p style="font-size: 13px; color: #64748b;"><em>Ensure your PayPal Monthly Plan ID and Stripe Secret Key are configured under Settings.</em></p>
            </div>
        </div>

        <!-- API Key Setup Guide -->
        <h2 style="margin-top: 35px;">Step-by-Step API Key Setup Guide</h2>
        <div class="cdf-card" style="margin-top: 10px; line-height: 1.6;">
            <ol style="margin: 0; padding-left: 20px;">
                <li><strong>Navigate to Settings:</strong> Go to <strong>Donations > Settings</strong> in your WordPress sidebar.</li>
                <li><strong>Choose Environment:</strong> Select <strong>Test / Sandbox</strong> while testing your checkout flow, or switch to <strong>Live / Production</strong> when ready to accept real donations.</li>
                <li><strong>Stripe Setup:</strong> Log into your Stripe Dashboard, go to <em>Developers > API Keys</em>, and copy your <strong>Publishable Key</strong> (starts with <code>pk_</code>) and <strong>Secret Key</strong> (starts with <code>sk_</code>). Paste them into the corresponding fields in Settings.</li>
                <li><strong>PayPal Setup:</strong> Log into the <a href="https://developer.paypal.com/" target="_blank">PayPal Developer Portal</a>, create a REST API app under your Apps & Credentials, and copy your <strong>Client ID</strong> and <strong>Secret Key</strong>.</li>
                <li><strong>PayPal Monthly Plan ID:</strong> To accept recurring subscriptions via PayPal, create a billing plan in your PayPal dashboard and paste the resulting Plan ID into the <strong>PayPal Monthly Plan ID</strong> field.</li>
            </ol>
        </div>
    </div>
    <?php
}

/**
 * 2. ANALYTICS PAGE RENDER (Revenue metrics dashboard)
 */
function cdf_render_analytics_page() {
    $donations = get_posts( array(
        'post_type'      => 'donation',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ) );

    $total_revenue  = 0;
    $stripe_rev     = 0;
    $paypal_rev     = 0;
    $onetime_rev    = 0;
    $monthly_rev    = 0;
    $total_count    = count( $donations );

    foreach ( $donations as $post ) {
        $amount    = floatval( get_post_meta( $post->ID, '_donation_amount', true ) );
        $gateway   = get_post_meta( $post->ID, '_donation_gateway', true );
        $frequency = get_post_meta( $post->ID, '_donation_frequency', true );

        $total_revenue += $amount;

        if ( 'Stripe' === $gateway ) {
            $stripe_rev += $amount;
        } else if ( 'PayPal' === $gateway ) {
            $paypal_rev += $amount;
        }

        if ( 'Monthly' === $frequency ) {
            $monthly_rev += $amount;
        } else {
            $onetime_rev += $amount;
        }
    }
    ?>
    <div class="wrap cdf-admin-wrap">
        <h1><?php esc_html_e( 'Donation Analytics & Revenue Reports', 'donation-form-paypal-stripe' ); ?></h1>
        <p style="font-size: 15px; color: #475569;">Overview of financial performance and transaction volume across all payment gateways.</p>

        <!-- Top Row: Total Revenue & Total Donations -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            <div class="cdf-metric-box" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: #fff;">
                <span style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9;">Total Revenue Generated</span>
                <h2 style="font-size: 36px; margin: 8px 0 0 0; color: #fff;">$<?php echo number_format( $total_revenue, 2 ); ?></h2>
            </div>
            <div class="cdf-metric-box" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%); color: #fff;">
                <span style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9;">Total Completed Donations</span>
                <h2 style="font-size: 36px; margin: 8px 0 0 0; color: #fff;"><?php echo intval( $total_count ); ?></h2>
            </div>
        </div>

        <!-- Middle Row: Gateway Breakdown (Stripe vs PayPal) -->
        <h2 style="margin-top: 35px;">Gateway Breakdown</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 10px;">
            <div class="cdf-card">
                <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b;">Stripe Revenue</span>
                <h3 style="font-size: 28px; margin: 6px 0 0 0; color: #1e293b;">$<?php echo number_format( $stripe_rev, 2 ); ?></h3>
            </div>
            <div class="cdf-card">
                <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b;">PayPal Revenue</span>
                <h3 style="font-size: 28px; margin: 6px 0 0 0; color: #1e293b;">$<?php echo number_format( $paypal_rev, 2 ); ?></h3>
            </div>
        </div>

        <!-- Bottom Row: Frequency Breakdown (One-Time vs Monthly) -->
        <h2 style="margin-top: 35px;">Frequency Breakdown</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 10px;">
            <div class="cdf-card">
                <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b;">One-Time Donations</span>
                <h3 style="font-size: 28px; margin: 6px 0 0 0; color: #1e293b;">$<?php echo number_format( $onetime_rev, 2 ); ?></h3>
            </div>
            <div class="cdf-card">
                <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b;">Monthly Recurring Subscriptions</span>
                <h3 style="font-size: 28px; margin: 6px 0 0 0; color: #1e293b;">$<?php echo number_format( $monthly_rev, 2 ); ?></h3>
            </div>
        </div>
    </div>
    <?php
}

/**
 * 3. STYLE PAGE RENDER (Elementor-Style Tabbed Control Panel)
 */
function cdf_render_style_page() {
    if ( isset( $_POST['cdf_save_styles'] ) && check_admin_referer( 'cdf_style_nonce' ) ) {
        $styles = isset( $_POST['cdf_style_options'] ) ? array_map( 'sanitize_text_field', $_POST['cdf_style_options'] ) : array();
        update_option( 'cdf_style_options', $styles );
        echo '<div class="notice notice-success is-dismissible"><p>Style settings saved successfully!</p></div>';
    }

    $styles = get_option( 'cdf_style_options', array() );
    $s = function( $key, $default ) use ( $styles ) {
        return isset( $styles[$key] ) ? $styles[$key] : $default;
    };
    ?>
    <div class="wrap cdf-admin-wrap">
        <h1><?php esc_html_e( 'Donation Form Custom Styling', 'donation-form-paypal-stripe' ); ?></h1>
        <form method="POST" action="">
            <?php wp_nonce_field( 'cdf_style_nonce' ); ?>
            
            <div class="cdf-card" style="margin-top: 20px;">
                <h2 style="margin-top: 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">Elementor-Style Color Controls</h2>
                
                <!-- Elementor-style Tabbed State Control -->
                <div class="cdf-style-tabs">
                    <button type="button" class="cdf-tab-btn active" data-tab="normal">Normal State</button>
                    <button type="button" class="cdf-tab-btn" data-tab="hover">Hover State</button>
                    <button type="button" class="cdf-tab-btn" data-tab="active">Active / Selected State</button>
                </div>

                <!-- Tab 1: Normal State -->
                <div class="cdf-tab-pane active" id="tab-normal">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Amount Button Background</th>
                            <td><input type="color" name="cdf_style_options[amt_bg_normal]" value="<?php echo esc_attr( $s('amt_bg_normal', '#f7fafc') ); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row">Amount Button Text Color</th>
                            <td><input type="color" name="cdf_style_options[amt_text_normal]" value="<?php echo esc_attr( $s('amt_text_normal', '#2d3748') ); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row">Gateway Card Background</th>
                            <td><input type="color" name="cdf_style_options[gw_bg_normal]" value="<?php echo esc_attr( $s('gw_bg_normal', '#ffffff') ); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row">Gateway Card Text Color</th>
                            <td><input type="color" name="cdf_style_options[gw_text_normal]" value="<?php echo esc_attr( $s('gw_text_normal', '#2d3748') ); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row">Submit Button Background</th>
                            <td><input type="color" name="cdf_style_options[sub_bg_normal]" value="<?php echo esc_attr( $s('sub_bg_normal', '#3182ce') ); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row">Submit Button Text Color</th>
                            <td><input type="color" name="cdf_style_options[sub_text_normal]" value="<?php echo esc_attr( $s('sub_text_normal', '#ffffff') ); ?>"></td>
                        </tr>
                    </table>
                </div>

                <!-- Tab 2: Hover State -->
                <div class="cdf-tab-pane" id="tab-hover" style="display: none;">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Amount Button Background (Hover)</th>
                            <td><input type="color" name="cdf_style_options[amt_bg_hover]" value="<?php echo esc_attr( $s('amt_bg_hover', '#edf2f7') ); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row">Amount Button Text Color (Hover)</th>
                            <td><input type="color" name="cdf_style_options[amt_text_hover]" value="<?php echo esc_attr( $s('amt_text_hover', '#2d3748') ); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row">Gateway Card Background (Hover)</th>
                            <td><input type="color" name="cdf_style_options[gw_bg_hover]" value="<?php echo esc_attr( $s('gw_bg_hover', '#f7fafc') ); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row">Gateway Card Text Color (Hover)</th>
                            <td><input type="color" name="cdf_style_options[gw_text_hover]" value="<?php echo esc_attr( $s('gw_text_hover', '#2d3748') ); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row">Submit Button Background (Hover)</th>
                            <td><input type="color" name="cdf_style_options[sub_bg_hover]" value="<?php echo esc_attr( $s('sub_bg_hover', '#2b6cb0') ); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row">Submit Button Text Color (Hover)</th>
                            <td><input type="color" name="cdf_style_options[sub_text_hover]" value="<?php echo esc_attr( $s('sub_text_hover', '#ffffff') ); ?>"></td>
                        </tr>
                    </table>
                </div>

                <!-- Tab 3: Active State -->
                <div class="cdf-tab-pane" id="tab-active" style="display: none;">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Amount Button Background (Active)</th>
                            <td><input type="color" name="cdf_style_options[amt_bg_active]" value="<?php echo esc_attr( $s('amt_bg_active', '#ebf8ff') ); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row">Amount Button Text & Border (Active)</th>
                            <td>
                                <input type="color" name="cdf_style_options[amt_text_active]" value="<?php echo esc_attr( $s('amt_text_active', '#2b6cb0') ); ?>">
                                <input type="hidden" name="cdf_style_options[amt_border_active]" value="#3182ce">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Gateway Card Background (Active)</th>
                            <td><input type="color" name="cdf_style_options[gw_bg_active]" value="<?php echo esc_attr( $s('gw_bg_active', '#ebf8ff') ); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row">Gateway Card Text Color (Active)</th>
                            <td>
                                <input type="color" name="cdf_style_options[gw_text_active]" value="<?php echo esc_attr( $s('gw_text_active', '#2b6cb0') ); ?>">
                                <input type="hidden" name="cdf_style_options[gw_border_active]" value="#3182ce">
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <?php submit_button( 'Save Style Settings', 'primary', 'cdf_save_styles' ); ?>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabBtns = document.querySelectorAll('.cdf-tab-btn');
        const tabPanes = document.querySelectorAll('.cdf-tab-pane');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                tabBtns.forEach(b => b.classList.remove('active'));
                tabPanes.forEach(p => p.style.display = 'none');

                this.classList.add('active');
                const target = document.getElementById('tab-' + this.getAttribute('data-tab'));
                if (target) {
                    target.style.display = 'block';
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * 4. EMAIL PAGE RENDER
 */
function cdf_render_email_page() {
    if ( isset( $_POST['cdf_save_emails'] ) && check_admin_referer( 'cdf_email_nonce' ) ) {
        update_option( 'cdf_admin_email_enabled', isset( $_POST['cdf_admin_email_enabled'] ) ? '1' : '0' );
        update_option( 'cdf_donor_email_enabled', isset( $_POST['cdf_donor_email_enabled'] ) ? '1' : '0' );
        update_option( 'cdf_admin_email_recipients', sanitize_text_field( $_POST['cdf_admin_email_recipients'] ) );
        echo '<div class="notice notice-success is-dismissible"><p>Email settings saved successfully!</p></div>';
    }

    $admin_enabled = get_option( 'cdf_admin_email_enabled', '1' );
    $donor_enabled = get_option( 'cdf_donor_email_enabled', '1' );
    $recipients    = get_option( 'cdf_admin_email_recipients', get_option( 'admin_email' ) );
    ?>
    <div class="wrap cdf-admin-wrap">
        <h1><?php esc_html_e( 'Email Notification Settings', 'donation-form-paypal-stripe' ); ?></h1>
        <form method="POST" action="">
            <?php wp_nonce_field( 'cdf_email_nonce' ); ?>
            <div class="cdf-card" style="margin-top: 20px;">
                <table class="form-table">
                    <tr>
                        <th scope="row">Admin Email Notification</th>
                        <td>
                            <label>
                                <input type="checkbox" id="cdf_admin_toggle" name="cdf_admin_email_enabled" value="1" <?php checked( $admin_enabled, '1' ); ?> onchange="document.getElementById('admin_email_box').style.display = this.checked ? 'block' : 'none';">
                                Enable admin notification email when a donation is received
                            </label>
                            <div id="admin_email_box" style="margin-top: 12px; display: <?php echo ( '1' === $admin_enabled ) ? 'block' : 'none'; ?>;">
                                <label style="display:block; font-weight:600; margin-bottom:4px;">Recipient Email Address(es)</label>
                                <input type="text" name="cdf_admin_email_recipients" value="<?php echo esc_attr( $recipients ); ?>" class="regular-text">
                                <p class="description">Default is your WordPress admin email. Separate multiple email addresses with commas (e.g. admin@site.com, finance@site.com).</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Donor Receipt Email</th>
                        <td>
                            <label>
                                <input type="checkbox" name="cdf_donor_email_enabled" value="1" <?php checked( $donor_enabled, '1' ); ?>>
                                Enable automated thank-you receipt email sent directly to the donor
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
            <?php submit_button( 'Save Email Settings', 'primary', 'cdf_save_emails' ); ?>
        </form>
    </div>
    <?php
}

/**
 * 5. SETTINGS PAGE RENDER (Includes Stripe Publishable & Secret Keys, PayPal Credentials, & Plan ID)
 */
function cdf_render_settings_page() {
    if ( isset( $_POST['cdf_save_settings'] ) && check_admin_referer( 'cdf_settings_nonce' ) ) {
        update_option( 'cdf_environment', sanitize_text_field( $_POST['cdf_environment'] ) );
        
        $api_settings = array(
            'stripe_publishable' => sanitize_text_field( $_POST['cdf_stripe_publishable'] ),
            'stripe_secret'      => sanitize_text_field( $_POST['cdf_stripe_secret'] ),
            'paypal_client'      => sanitize_text_field( $_POST['cdf_paypal_client'] ),
            'paypal_secret'      => sanitize_text_field( $_POST['cdf_paypal_secret'] ),
            'paypal_plan_id'     => sanitize_text_field( $_POST['cdf_paypal_plan_id'] ),
        );
        update_option( 'cdf_api_settings', $api_settings );
        echo '<div class="notice notice-success is-dismissible"><p>Gateway settings saved successfully!</p></div>';
    }

    $env      = get_option( 'cdf_environment', 'sandbox' );
    $settings = get_option( 'cdf_api_settings', array() );
    $stripe_pub = isset( $settings['stripe_publishable'] ) ? $settings['stripe_publishable'] : '';
    $stripe_sec = isset( $settings['stripe_secret'] ) ? $settings['stripe_secret'] : '';
    $paypal_client = isset( $settings['paypal_client'] ) ? $settings['paypal_client'] : '';
    $paypal_sec = isset( $settings['paypal_secret'] ) ? $settings['paypal_secret'] : '';
    $paypal_plan = isset( $settings['paypal_plan_id'] ) ? $settings['paypal_plan_id'] : '';
    ?>
    <div class="wrap cdf-admin-wrap">
        <h1><?php esc_html_e( 'Gateway API Settings', 'donation-form-paypal-stripe' ); ?></h1>
        <form method="POST" action="">
            <?php wp_nonce_field( 'cdf_settings_nonce' ); ?>
            <div class="cdf-card" style="margin-top: 20px;">
                <table class="form-table">
                    <tr>
                        <th scope="row">Environment Mode</th>
                        <td>
                            <select name="cdf_environment" class="regular-text">
                                <option value="sandbox" <?php selected( $env, 'sandbox' ); ?>>Test / Sandbox (API Test Mode)</option>
                                <option value="live" <?php selected( $env, 'live' ); ?>>Live / Production</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Stripe Publishable Key</th>
                        <td>
                            <input type="text" name="cdf_stripe_publishable" value="<?php echo esc_attr( $stripe_pub ); ?>" class="regular-text">
                            <p class="description">Enter your Stripe Publishable API Key (starts with pk_test_ or pk_live_).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Stripe Secret Key</th>
                        <td>
                            <input type="password" name="cdf_stripe_secret" value="<?php echo esc_attr( $stripe_sec ); ?>" class="regular-text" autocomplete="new-password">
                            <p class="description">Enter your Stripe Secret API Key (starts with sk_test_ or sk_live_).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">PayPal Client ID</th>
                        <td>
                            <input type="text" name="cdf_paypal_client" value="<?php echo esc_attr( $paypal_client ); ?>" class="regular-text">
                            <p class="description">Enter your PayPal REST API Client ID.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">PayPal Secret Key</th>
                        <td>
                            <input type="password" name="cdf_paypal_secret" value="<?php echo esc_attr( $paypal_sec ); ?>" class="regular-text" autocomplete="new-password">
                            <p class="description">Enter your PayPal REST API Secret Key.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">PayPal Monthly Plan ID</th>
                        <td>
                            <input type="text" name="cdf_paypal_plan_id" value="<?php echo esc_attr( $paypal_plan ); ?>" class="regular-text">
                            <p class="description">Enter your PayPal Billing Plan ID for monthly subscriptions. Note: This utilizes quantity-based pricing ($1 per unit multiplier).</p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php submit_button( 'Save Gateway Settings', 'primary', 'cdf_save_settings' ); ?>
        </form>
    </div>
    <?php
}
