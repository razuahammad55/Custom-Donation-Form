# Custom-Donation-Form
Custom Donation Form with Paypal &amp; Stripe Payment Gateway Support


A lightweight, cache-resilient custom PHP recurring donation form snippet for WordPress. Built to co-exist safely on pages alongside JetFormBuilder or GiveWP forms without hook or state collisions.

## Key Features

- **Dual Payment Processing:** Supports Stripe Checkout Hosted Sessions and PayPal REST API Recurring Subscriptions.
- **Cache-Resilient Nonce Check:** Automatically bypasses `wp_verify_nonce` failures caused by aggressive server-side caching (Varnish, Cloudflare Edge, GoDaddy Managed WordPress) while keeping inputs sanitized.
- **Isolated Hook Logic:** Hooks directly into `template_redirect` using a unique post token (`monthly_form_token`), leaving other post handlers untouched.
- **Responsive UI Engine:** Includes dynamic preset amount buttons and gateway selector UI state toggles via lightweight Vanilla JS.

---


## 4. How to Display the Form on the Frontend

The forms and their interactive JavaScript logic are rendered using custom shortcodes. You can place these shortcodes anywhere in WordPress—such as inside an Elementor Shortcode widget, a Gutenberg Shortcode block, the Classic Editor, or directly within theme PHP templates.

### Shortcodes

* **One-Time Donation Form:**
  ```text
  [custom_onetime_donation_form]
```

  * **One-Time Donation Form:**
  ```text
  [custom_onetime_donation_form]
---

## Configuration (`wp-config.php`)

To keep sensitive API keys secure and avoid hardcoding values inside your code snippets, define the following constants inside your site's `wp-config.php` file:

```php

// =========================================================================
// CUSTOM MONTHLY DONATION FORM GATEWAY CREDENTIALS (TEST / SANDBOX MODE)
// =========================================================================

// Enable Sandbox Mode
define( 'DONATION_SANDBOX_MODE', true );

// Stripe Test API Credentials (starts with sk_test_ and pk_test_)
define( 'DONATION_STRIPE_SECRET', 'sk_test_XXXXXXXXXXXXXXXXXXXXXXXX' );
define( 'DONATION_STRIPE_PUBLIC', 'pk_test_XXXXXXXXXXXXXXXXXXXXXXXX' );

// PayPal Sandbox REST API Credentials
define( 'DONATION_PAYPAL_CLIENT_ID', 'sb-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX' );
define( 'DONATION_PAYPAL_CLIENT_SECRET', 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX' );

// PayPal Sandbox Subscription Plan ID (Created in Sandbox Developer Dashboard)
define( 'DONATION_PAYPAL_PLAN_ID', 'P-SANDBOX_XXXXXXXXXXXXX' );

// PayPal Sandbox Business Account Email
define( 'DONATION_PAYPAL_EMAIL', 'sandbox-business@example.com' );

```

