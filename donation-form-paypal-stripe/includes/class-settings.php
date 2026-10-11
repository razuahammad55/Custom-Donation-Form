<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
 * 1. FORMS PAGE RENDER (Exact Grid Layout + API Key Guide)
 */
function cdf_render_forms_page() {
    ?>
    <div class="wrap" style="margin: 20px 20px 0 0;">
        <h1 style="font-size: 23px; font-weight: 600; color: #1e293b; margin-bottom: 8px;"><?php esc_html_e( 'Donation Forms & Shortcodes', 'donation-form-paypal-stripe' ); ?></h1>
        <p style="font-size: 14px; color: #64748b; margin-bottom: 20px;">Copy and paste these shortcodes onto any page or Elementor widget to display your donation forms.</p>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                <h3 style="margin-top: 0; color: #1e293b; font-size: 16px;">One-Time Donation Form</h3>
                <p style="color: #64748b; font-size: 14px;">Renders a compact, high-converting one-time donation form supporting Stripe and PayPal.</p>
                <div style="background: #f1f5f9; padding: 10px 14px; font-family: monospace; font-weight: bold; border-radius: 4px; margin-bottom: 12px; color: #0f172a;">[donation_form type="one_time"]</div>
                <p style="font-size: 13px; color: #64748b; margin-bottom: 0;">Note: You can also simply use <code>[donation_form]</code> since One-Time is the default type.</p>
            </div>

            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                <h3 style="margin-top: 0; color: #1e293b; font-size: 16px;">Monthly Recurring Donation Form</h3>
                <p style="color: #64748b; font-size: 14px;">Renders the monthly subscription donation form supporting Stripe Subscriptions and PayPal Plans.</p>
                <div style="background: #f1f5f9; padding: 10px 14px; font-family: monospace; font-weight: bold; border-radius: 4px; margin-bottom: 12px; color: #0f172a;">[donation_form type="monthly"]</div>
                <p style="font-size: 13px; color: #64748b; margin-bottom: 0;">Ensure your PayPal Monthly Plan ID and Stripe Secret Key are configured under Settings.</p>
            </div>
        </div>

        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 16px; margin-bottom: 25px; color: #92400e;">
            <strong>Setup Guide & Success Pages:</strong> Upon plugin activation, automatic pages for <code>/donation-success/</code> and <code>/donation-canceled/</code> were created. If you customize or delete these pages, ensure your new custom success page contains the <code>[donation_receipt]</code> shortcode so transactions log properly!
        </div>

        <!-- API Key Setup Instructions -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <h2 style="margin-top: 0; font-size: 18px; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">Step-by-Step API Key Configuration Guide</h2>
            <ol style="margin: 15px 0 0 20px; color: #334155; line-height: 1.6;">
                <li><strong>Access Settings:</strong> Go to <strong>Donations > Settings</strong> in your WordPress dashboard sidebar.</li>
                <li><strong>Environment Mode:</strong> Select <strong>Test / Sandbox</strong> while testing transactions, or switch to <strong>Live / Production</strong> for real donor payments.</li>
                <li><strong>Stripe Credentials:</strong> Log into your Stripe Dashboard, navigate to <em>Developers > API Keys</em>, and copy both your <strong>Publishable Key</strong> (<code>pk_...</code>) and <strong>Secret Key</strong> (<code>sk_...</code>). Paste them into the corresponding fields in Settings.</li>
                <li><strong>PayPal Credentials:</strong> Access the <a href="https://developer.paypal.com/" target="_blank">PayPal Developer Dashboard</a>, create a REST API app under Apps & Credentials, and copy your <strong>Client ID</strong> and <strong>Secret Key</strong>.</li>
                <li><strong>PayPal Monthly Plan ID:</strong> To enable recurring monthly subscriptions via PayPal, create a billing plan in your PayPal account and paste the resulting Plan ID into the <strong>PayPal Monthly Plan ID</strong> field.</li>
            </ol>
        </div>
    </div>
    <?php
}

/**
 * 2. ANALYTICS PAGE RENDER (Stylish Spaced Table Layout)
 */
function cdf_render_analytics_page() {
    $donations = get_posts( array(
        'post_type'      => 'donation',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ) );

    $total_revenue  = 0;
    $stripe_rev     = 0;
    $stripe_onetime = 0;
    $stripe_monthly = 0;
    $paypal_rev     = 0;
    $paypal_onetime = 0;
    $paypal_monthly = 0;
    $total_count    = count( $donations );

    foreach ( $donations as $post ) {
        $amount    = floatval( get_post_meta( $post->ID, '_donation_amount', true ) );
        $gateway   = get_post_meta( $post->ID, '_donation_gateway', true );
        $frequency = get_post_meta( $post->ID, '_donation_frequency', true );

        $total_revenue += $amount;

        if ( 'Stripe' === $gateway ) {
            $stripe_rev += $amount;
            if ( 'Monthly' === $frequency ) {
                $stripe_monthly += $amount;
            } else {
                $stripe_onetime += $amount;
            }
        } else if ( 'PayPal' === $gateway ) {
            $paypal_rev += $amount;
            if ( 'Monthly' === $frequency ) {
                $paypal_monthly += $amount;
            } else {
                $paypal_onetime += $amount;
            }
        }
    }
    ?>
    <div class="wrap" style="margin: 20px 20px 0 0;">
        <h1 style="font-size: 23px; font-weight: 600; color: #1e293b; margin-bottom: 8px;"><?php esc_html_e( 'Donation Analytics & Revenue Reports', 'donation-form-paypal-stripe' ); ?></h1>
        <p style="font-size: 14px; color: #64748b; margin-bottom: 25px;">Comprehensive financial overview and transaction metrics.</p>

        <!-- Total Completed Donations Banner -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px 24px; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 15px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">Total Completed Donations</span>
            <span style="font-size: 26px; font-weight: 700; color: #10b981;"><?php echo intval( $total_count ); ?></span>
        </div>

        <!-- Stylish Table Card -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            
            <!-- Row 1: Total Revenue Generated -->
            <div style="text-align: center; padding-bottom: 28px; border-bottom: 1px solid #e2e8f0; margin-bottom: 28px;">
                <span style="font-size: 13px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; display: block; margin-bottom: 6px;">Total Revenue Generated</span>
                <h2 style="font-size: 38px; font-weight: 800; color: #1e293b; margin: 0;">$<?php echo number_format( $total_revenue, 2 ); ?></h2>
            </div>

            <!-- Header: Gateway Breakdown -->
            <div style="margin-bottom: 20px;">
                <h3 style="font-size: 16px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.5px; margin: 0;">Gateway Breakdown</h3>
            </div>

            <!-- Two Columns Below: Stripe vs PayPal -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                
                <!-- Stripe Column -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
                        <span style="font-weight: 700; color: #1e293b; font-size: 16px;">Stripe Revenue</span>
                        <strong style="font-size: 20px; color: #3182ce;">$<?php echo number_format( $stripe_rev, 2 ); ?></strong>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; text-align: center;">
                        <div style="background: #fff; padding: 12px; border: 1px solid #e2e8f0; border-radius: 6px;">
                            <span style="display: block; font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">One-Time</span>
                            <strong style="color: #0f172a; font-size: 15px;">$<?php echo number_format( $stripe_onetime, 2 ); ?></strong>
                        </div>
                        <div style="background: #fff; padding: 12px; border: 1px solid #e2e8f0; border-radius: 6px;">
                            <span style="display: block; font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Monthly</span>
                            <strong style="color: #0f172a; font-size: 15px;">$<?php echo number_format( $stripe_monthly, 2 ); ?></strong>
                        </div>
                    </div>
                </div>

                <!-- PayPal Column -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
                        <span style="font-weight: 700; color: #1e293b; font-size: 16px;">PayPal Revenue</span>
                        <strong style="font-size: 20px; color: #0070ba;">$<?php echo number_format( $paypal_rev, 2 ); ?></strong>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; text-align: center;">
                        <div style="background: #fff; padding: 12px; border: 1px solid #e2e8f0; border-radius: 6px;">
                            <span style="display: block; font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">One-Time</span>
                            <strong style="color: #0f172a; font-size: 15px;">$<?php echo number_format( $paypal_onetime, 2 ); ?></strong>
                        </div>
                        <div style="background: #fff; padding: 12px; border: 1px solid #e2e8f0; border-radius: 6px;">
                            <span style="display: block; font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Monthly</span>
                            <strong style="color: #0f172a; font-size: 15px;">$<?php echo number_format( $paypal_monthly, 2 ); ?></strong>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
    <?php
}

/**
 * 3. STYLE PAGE RENDER (Modern, Clean, 3 Sections with Hex Input & Border Radius)
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
    <div class="wrap" style="margin: 20px 20px 0 0;">
        <h1 style="font-size: 23px; font-weight: 600; color: #1e293b; margin-bottom: 8px;"><?php esc_html_e( 'Donation Form Custom Styling', 'donation-form-paypal-stripe' ); ?></h1>
        <p style="font-size: 14px; color: #64748b; margin-bottom: 20px;">Customize button colors, hover/active states, and geometric border radius.</p>
        
        <form method="POST" action="">
            <?php wp_nonce_field( 'cdf_style_nonce' ); ?>
            
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                
                <!-- Global Geometry: Border Radius -->
                <div style="margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0;">
                    <label style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px;">Global Border Radius (px)</label>
                    <input type="number" name="cdf_style_options[border_radius]" value="<?php echo esc_attr( $s('border_radius', '8') ); ?>" min="0" max="30" style="width: 100px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 4px;">
                    <span style="font-size: 13px; color: #64748b; margin-left: 10px;">Applies rounded corners to buttons, input fields, and gateway cards.</span>
                </div>

                <!-- Elementor-Style Tabbed State Control -->
                <div style="display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 14px;">
                    <button type="button" class="cdf-tab-btn active" data-tab="normal" style="background: #3182ce; color: #fff; border: none; padding: 10px 20px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all 0.2s;">Normal State</button>
                    <button type="button" class="cdf-tab-btn" data-tab="hover" style="background: #f1f5f9; color: #475569; border: none; padding: 10px 20px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all 0.2s;">Hover State</button>
                    <button type="button" class="cdf-tab-btn" data-tab="active" style="background: #f1f5f9; color: #475569; border: none; padding: 10px 20px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all 0.2s;">Active State</button>
                </div>

                <!-- Tab 1: Normal State -->
                <div class="cdf-tab-pane" id="tab-normal">
                    <h3 style="font-size: 16px; color: #1e293b; margin-top: 0;">1. Amount Button</h3>
                    <table class="form-table" style="margin-bottom: 20px;">
                        <tr>
                            <th scope="row">Background Color</th>
                            <td><?php cdf_render_color_picker( 'cdf_style_options[amt_bg_normal]', $s('amt_bg_normal', '#f7fafc') ); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Text Color</th>
                            <td><?php cdf_render_color_picker( 'cdf_style_options[amt_text_normal]', $s('amt_text_normal', '#2d3748') ); ?></td>
                        </tr>
                    </table>

                    <h3 style="font-size: 16px; color: #1e293b;">2. Gateway Card</h3>
                    <table class="form-table" style="margin-bottom: 20px;">
                        <tr>
                            <th scope="row">Background Color</th>
                            <td><?php cdf_render_color_picker( 'cdf_style_options[gw_bg_normal]', $s('gw_bg_normal', '#ffffff') ); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Text Color</th>
                            <td><?php cdf_render_color_picker( 'cdf_style_options[gw_text_normal]', $s('gw_text_normal', '#2d3748') ); ?></td>
                        </tr>
                    </table>

                    <h3 style="font-size: 16px; color: #1e293b;">3. Submit Button</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Background Color</th>
                            <td><?php cdf_render_color_picker( 'cdf_style_options[sub_bg_normal]', $s('sub_bg_normal', '#3182ce') ); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Text Color</th>
                            <td><?php cdf_render_color_picker( 'cdf_style_options[sub_text_normal]', $s('sub_text_normal', '#ffffff') ); ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Tab 2: Hover State -->
                <div class="cdf-tab-pane" id="tab-hover" style="display: none;">
                    <h3 style="font-size: 16px; color: #1e293b; margin-top: 0;">1. Amount Button</h3>
                    <table class="form-table" style="margin-bottom: 20px;">
                        <tr>
                            <th scope="row">Background Color (Hover)</th>
                            <td><?php cdf_render_color_picker( 'cdf_style_options[amt_bg_hover]', $s('amt_bg_hover', '#edf2f7') ); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Text Color (Hover)</th>
                            <td><?php cdf_render_color_picker( 'cdf_style_options[amt_text_hover]', $s('amt_text_hover', '#2d3748') ); ?></td>
                        </tr>
                    </table>

                    <h3 style="font-size: 16px; color: #1e293b;">2. Gateway Card</h3>
                    <table class="form-table" style="margin-bottom: 20px;">
                        <tr>
                            <th scope="row">Background Color (Hover)</th>
                            <td><?php cdf_render_color_picker( 'cdf_style_options[gw_bg_hover]', $s('gw_bg_hover', '#f7fafc') ); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Text Color (Hover)</th>
                            <td><?php cdf_render_color_picker( 'cdf_style_options[gw_text_hover]', $s('gw_text_hover', '#2d3748') ); ?></td>
                        </tr>
                    </table>

                    <h3 style="font-size: 16px; color: #1e293b;">3. Submit Button</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Background Color (Hover)</th>
                            <td><?php cdf_render_color_picker( 'cdf_style_options[sub_bg_hover]', $s('sub_bg_hover', '#2b6cb0') ); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Text Color (Hover)</th>
                            <td><?php cdf_render_color_picker( 'cdf_style_options[sub_text_hover]', $s('sub_text_hover', '#ffffff') ); ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Tab 3: Active State -->
                <div class="cdf-tab-pane" id="tab-active" style="display: none;">
                    <h3 style="font-size: 16px; color: #1e293b; margin-top: 0;">1. Amount Button</h3>
                    <table class="form-table" style="margin-bottom: 20px;">
                        <tr>
                            <th scope="row">Background Color (Active)</th>
                            <td><?php cdf_render_color_picker( 'cdf_style_options[amt_bg_active]', $s('amt_bg_active', '#ebf8ff') ); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Text Color (Active)</th>
                            <td><?php cdf_render_color_picker( 'cdf_style_options[amt_text_active]', $s('amt_text_active', '#2b6cb0') ); ?></td>
                        </tr>
                    </table>

                    <h3 style="font-size: 16px; color: #1e293b;">2. Gateway Card</h3>
                    <table class="form-table" style="margin-bottom: 20px;">
                        <tr>
                            <th scope="row">Background Color (Active)</th>
                            <td><?php cdf_render_color_picker( 'cdf_style_options[gw_bg_active]', $s('gw_bg_active', '#ebf8ff') ); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Text Color (Active)</th>
                            <td><?php cdf_render_color_picker( 'cdf_style_options[gw_text_active]', $s('gw_text_active', '#2b6cb0') ); ?></td>
                        </tr>
                    </table>

                    <h3 style="font-size: 16px; color: #1e293b;">3. Submit Button</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Background Color (Active)</th>
                            <td><?php cdf_render_color_picker( 'cdf_style_options[sub_bg_active]', $s('sub_bg_active', '#2b6cb0') ); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Text Color (Active)</th>
                            <td><?php cdf_render_color_picker( 'cdf_style_options[sub_text_active]', $s('sub_text_active', '#ffffff') ); ?></td>
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
                tabBtns.forEach(b => {
                    b.classList.remove('active');
                    b.style.background = '#f1f5f9';
                    b.style.color = '#475569';
                });
                tabPanes.forEach(p => p.style.display = 'none');

                this.classList.add('active');
                this.style.background = '#3182ce';
                this.style.color = '#ffffff';

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
 * Helper: Render Color Picker with Hex Code Support
 */
function cdf_render_color_picker( $name, $value ) {
    ?>
    <div style="display: flex; align-items: center; gap: 10px;">
        <input type="color" value="<?php echo esc_attr( $value ); ?>" oninput="this.nextElementSibling.value = this.value" style="width: 50px; height: 35px; border: 1px solid #cbd5e1; border-radius: 4px; cursor: pointer; background: #fff; padding: 2px;">
        <input type="text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" style="width: 120px; font-family: monospace;" oninput="this.previousElementSibling.value = this.value">
    </div>
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
    <div class="wrap" style="margin: 20px 20px 0 0;">
        <h1 style="font-size: 23px; font-weight: 600; color: #1e293b; margin-bottom: 8px;"><?php esc_html_e( 'Email Notification Settings', 'donation-form-paypal-stripe' ); ?></h1>
        <form method="POST" action="">
            <?php wp_nonce_field( 'cdf_email_nonce' ); ?>
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
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
                                <input type="text" name="cdf_admin_email_recipients" value="<?php echo esc_attr( $recipients ); ?>" class="regular-text" style="width: 100%; max-width: 450px;">
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
 * 5. SETTINGS PAGE RENDER
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
    <div class="wrap" style="margin: 20px 20px 0 0;">
        <h1 style="font-size: 23px; font-weight: 600; color: #1e293b; margin-bottom: 8px;"><?php esc_html_e( 'Gateway API Settings', 'donation-form-paypal-stripe' ); ?></h1>
        <form method="POST" action="">
            <?php wp_nonce_field( 'cdf_settings_nonce' ); ?>
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                <table class="form-table">
                    <tr>
                        <th scope="row">Environment Mode</th>
                        <td>
                            <select name="cdf_environment" class="regular-text" style="width: 100%; max-width: 450px;">
                                <option value="sandbox" <?php selected( $env, 'sandbox' ); ?>>Test / Sandbox (API Test Mode)</option>
                                <option value="live" <?php selected( $env, 'live' ); ?>>Live / Production</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Stripe Publishable Key</th>
                        <td>
                            <input type="text" name="cdf_stripe_publishable" value="<?php echo esc_attr( $stripe_pub ); ?>" class="regular-text" style="width: 100%; max-width: 450px;">
                            <p class="description">Enter your Stripe Publishable API Key (starts with pk_test_ or pk_live_).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Stripe Secret Key</th>
                        <td>
                            <input type="password" name="cdf_stripe_secret" value="<?php echo esc_attr( $stripe_sec ); ?>" class="regular-text" autocomplete="new-password" style="width: 100%; max-width: 450px;">
                            <p class="description">Enter your Stripe Secret API Key (starts with sk_test_ or sk_live_).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">PayPal Client ID</th>
                        <td>
                            <input type="text" name="cdf_paypal_client" value="<?php echo esc_attr( $paypal_client ); ?>" class="regular-text" style="width: 100%; max-width: 450px;">
                            <p class="description">Enter your PayPal REST API Client ID.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">PayPal Secret Key</th>
                        <td>
                            <input type="password" name="cdf_paypal_secret" value="<?php echo esc_attr( $paypal_sec ); ?>" class="regular-text" autocomplete="new-password" style="width: 100%; max-width: 450px;">
                            <p class="description">Enter your PayPal REST API Secret Key.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">PayPal Monthly Plan ID</th>
                        <td>
                            <input type="text" name="cdf_paypal_plan_id" value="<?php echo esc_attr( $paypal_plan ); ?>" class="regular-text" style="width: 100%; max-width: 450px;">
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
