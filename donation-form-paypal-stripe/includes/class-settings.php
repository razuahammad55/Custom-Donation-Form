<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Sub-Menus under 'Donations' CPT
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
 * 1. FORMS PAGE RENDER
 */
function cdf_render_forms_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Donation Forms & Shortcodes', 'donation-form-paypal-stripe' ); ?></h1>
        <p style="font-size: 15px; color: #475569;">Copy and paste these shortcodes onto any page or Elementor widget to display your donation forms.</p>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            <div style="background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="margin-top: 0; color: #1e293b;">One-Time Donation Form</h3>
                <p style="color: #64748b; font-size: 14px;">Renders a compact, high-converting one-time donation form supporting Stripe and PayPal.</p>
                <div style="background: #f1f5f9; padding: 10px 14px; font-family: monospace; font-weight: bold; border-radius: 4px; margin-bottom: 12px;">[donation_form type="one_time"]</div>
                <p style="font-size: 13px; color: #64748b;"><em>Note: You can also simply use <code>[donation_form]</code> since One-Time is the default type.</em></p>
            </div>

            <div style="background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="margin-top: 0; color: #1e293b;">Monthly Recurring Donation Form</h3>
                <p style="color: #64748b; font-size: 14px;">Renders the monthly subscription donation form supporting Stripe Subscriptions and PayPal Plans.</p>
                <div style="background: #f1f5f9; padding: 10px 14px; font-family: monospace; font-weight: bold; border-radius: 4px; margin-bottom: 12px;">[donation_form type="monthly"]</div>
                <p style="font-size: 13px; color: #64748b;"><em>Ensure your PayPal Monthly Plan ID and Stripe Secret Key are configured under Settings.</em></p>
            </div>
        </div>

        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 16px; margin-top: 24px; color: #92400e;">
            <strong>Setup Guide & Success Pages:</strong> Upon plugin activation, automatic pages for <code>/donation-success/</code> and <code>/donation-canceled/</code> were created. If you customize or delete these pages, ensure your new custom success page contains the <code>[donation_receipt]</code> shortcode so transactions log properly!
        </div>
    </div>
    <?php
}

/**
 * 2. STYLE PAGE RENDER
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
    <div class="wrap">
        <h1><?php esc_html_e( 'Donation Form Custom Styling', 'donation-form-paypal-stripe' ); ?></h1>
        <form method="POST" action="">
            <?php wp_nonce_field( 'cdf_style_nonce' ); ?>
            <table class="form-table" style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-radius:6px; margin-top:15px;">
                <tr>
                    <th scope="row">Amount Buttons (.amt-btn)</th>
                    <td>
                        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px; max-width:700px;">
                            <div>
                                <label style="display:block; font-weight:600; margin-bottom:4px;">Normal Background</label>
                                <input type="color" name="cdf_style_options[amt_bg_normal]" value="<?php echo esc_attr( $s('amt_bg_normal', '#f7fafc') ); ?>">
                            </div>
                            <div>
                                <label style="display:block; font-weight:600; margin-bottom:4px;">Hover Background</label>
                                <input type="color" name="cdf_style_options[amt_bg_hover]" value="<?php echo esc_attr( $s('amt_bg_hover', '#edf2f7') ); ?>">
                            </div>
                            <div>
                                <label style="display:block; font-weight:600; margin-bottom:4px;">Active Background</label>
                                <input type="color" name="cdf_style_options[amt_bg_active]" value="<?php echo esc_attr( $s('amt_bg_active', '#ebf8ff') ); ?>">
                            </div>
                            <div>
                                <label style="display:block; font-weight:600; margin-bottom:4px;">Normal Text Color</label>
                                <input type="color" name="cdf_style_options[amt_text_normal]" value="<?php echo esc_attr( $s('amt_text_normal', '#2d3748') ); ?>">
                            </div>
                            <div>
                                <label style="display:block; font-weight:600; margin-bottom:4px;">Hover Text Color</label>
                                <input type="color" name="cdf_style_options[amt_text_hover]" value="<?php echo esc_attr( $s('amt_text_hover', '#2d3748') ); ?>">
                            </div>
                            <div>
                                <label style="display:block; font-weight:600; margin-bottom:4px;">Active Text & Border</label>
                                <input type="color" name="cdf_style_options[amt_text_active]" value="<?php echo esc_attr( $s('amt_text_active', '#2b6cb0') ); ?>">
                                <input type="hidden" name="cdf_style_options[amt_border_active]" value="#3182ce">
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Payment Gateways (.gateway-row)</th>
                    <td>
                        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px; max-width:700px;">
                            <div>
                                <label style="display:block; font-weight:600; margin-bottom:4px;">Normal Background</label>
                                <input type="color" name="cdf_style_options[gw_bg_normal]" value="<?php echo esc_attr( $s('gw_bg_normal', '#ffffff') ); ?>">
                            </div>
                            <div>
                                <label style="display:block; font-weight:600; margin-bottom:4px;">Hover Background</label>
                                <input type="color" name="cdf_style_options[gw_bg_hover]" value="<?php echo esc_attr( $s('gw_bg_hover', '#f7fafc') ); ?>">
                            </div>
                            <div>
                                <label style="display:block; font-weight:600; margin-bottom:4px;">Active Background</label>
                                <input type="color" name="cdf_style_options[gw_bg_active]" value="<?php echo esc_attr( $s('gw_bg_active', '#ebf8ff') ); ?>">
                            </div>
                            <div>
                                <label style="display:block; font-weight:600; margin-bottom:4px;">Normal Text Color</label>
                                <input type="color" name="cdf_style_options[gw_text_normal]" value="<?php echo esc_attr( $s('gw_text_normal', '#2d3748') ); ?>">
                            </div>
                            <div>
                                <label style="display:block; font-weight:600; margin-bottom:4px;">Hover Text Color</label>
                                <input type="color" name="cdf_style_options[gw_text_hover]" value="<?php echo esc_attr( $s('gw_text_hover', '#2d3748') ); ?>">
                            </div>
                            <div>
                                <label style="display:block; font-weight:600; margin-bottom:4px;">Active Text Color</label>
                                <input type="color" name="cdf_style_options[gw_text_active]" value="<?php echo esc_attr( $s('gw_text_active', '#2b6cb0') ); ?>">
                                <input type="hidden" name="cdf_style_options[gw_border_active]" value="#3182ce">
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Submit Button</th>
                    <td>
                        <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:15px; max-width:500px;">
                            <div>
                                <label style="display:block; font-weight:600; margin-bottom:4px;">Background (Normal / Hover)</label>
                                <input type="color" name="cdf_style_options[sub_bg_normal]" value="<?php echo esc_attr( $s('sub_bg_normal', '#3182ce') ); ?>" style="margin-right:10px;">
                                <input type="color" name="cdf_style_options[sub_bg_hover]" value="<?php echo esc_attr( $s('sub_bg_hover', '#2b6cb0') ); ?>">
                            </div>
                            <div>
                                <label style="display:block; font-weight:600; margin-bottom:4px;">Text Color (Normal / Hover)</label>
                                <input type="color" name="cdf_style_options[sub_text_normal]" value="<?php echo esc_attr( $s('sub_text_normal', '#ffffff') ); ?>" style="margin-right:10px;">
                                <input type="color" name="cdf_style_options[sub_text_hover]" value="<?php echo esc_attr( $s('sub_text_hover', '#ffffff') ); ?>">
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Save Style Settings', 'primary', 'cdf_save_styles' ); ?>
        </form>
    </div>
    <?php
}

/**
 * 3. EMAIL PAGE RENDER
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
    <div class="wrap">
        <h1><?php esc_html_e( 'Email Notification Settings', 'donation-form-paypal-stripe' ); ?></h1>
        <form method="POST" action="">
            <?php wp_nonce_field( 'cdf_email_nonce' ); ?>
            <table class="form-table" style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-radius:6px; margin-top:15px;">
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
            <?php submit_button( 'Save Email Settings', 'primary', 'cdf_save_emails' ); ?>
        </form>
    </div>
    <?php
}

/**
 * 4. SETTINGS PAGE RENDER
 */
function cdf_render_settings_page() {
    if ( isset( $_POST['cdf_save_settings'] ) && check_admin_referer( 'cdf_settings_nonce' ) ) {
        update_option( 'cdf_environment', sanitize_text_field( $_POST['cdf_environment'] ) );
        
        $api_settings = array(
            'stripe_secret'  => sanitize_text_field( $_POST['cdf_stripe_secret'] ),
            'paypal_client'  => sanitize_text_field( $_POST['cdf_paypal_client'] ),
            'paypal_secret'  => sanitize_text_field( $_POST['cdf_paypal_secret'] ),
            'paypal_plan_id' => sanitize_text_field( $_POST['cdf_paypal_plan_id'] ),
        );
        update_option( 'cdf_api_settings', $api_settings );
        echo '<div class="notice notice-success is-dismissible"><p>Gateway settings saved successfully!</p></div>';
    }

    $env      = get_option( 'cdf_environment', 'sandbox' );
    $settings = get_option( 'cdf_api_settings', array() );
    $stripe_secret = isset( $settings['stripe_secret'] ) ? $settings['stripe_secret'] : '';
    $paypal_client = isset( $settings['paypal_client'] ) ? $settings['paypal_client'] : '';
    $paypal_secret = isset( $settings['paypal_secret'] ) ? $settings['paypal_secret'] : '';
    $paypal_plan   = isset( $settings['paypal_plan_id'] ) ? $settings['paypal_plan_id'] : '';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Gateway API Settings', 'donation-form-paypal-stripe' ); ?></h1>
        <form method="POST" action="">
            <?php wp_nonce_field( 'cdf_settings_nonce' ); ?>
            <table class="form-table" style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-radius:6px; margin-top:15px;">
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
                    <th scope="row">Stripe Secret Key</th>
                    <td>
                        <input type="password" name="cdf_stripe_secret" value="<?php echo esc_attr( $stripe_secret ); ?>" class="regular-text" autocomplete="new-password">
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
                        <input type="password" name="cdf_paypal_secret" value="<?php echo esc_attr( $paypal_secret ); ?>" class="regular-text" autocomplete="new-password">
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
            <?php submit_button( 'Save Gateway Settings', 'primary', 'cdf_save_settings' ); ?>
        </form>
    </div>
    <?php
}
