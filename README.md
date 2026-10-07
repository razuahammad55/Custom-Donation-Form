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

The form and its interactive JavaScript logic are rendered using the custom shortcode below:

```text
[custom_monthly_donation_form]
```
---

## Configuration (`wp-config.php`)

To keep sensitive API keys secure and avoid hardcoding values inside your code snippets, define the following constants inside your site's `wp-config.php` file:

```php
// =========================================================================
// CUSTOM MONTHLY DONATION FORM GATEWAY CREDENTIALS
// =========================================================================

// Stripe API Credentials
define( 'DONATION_STRIPE_SECRET', 'sk_live_XXXXXXXXXXXXXXXXXXXXXXXX' );
define( 'DONATION_STRIPE_PUBLIC', 'pk_live_XXXXXXXXXXXXXXXXXXXXXXXX' );
define( 'DONATION_SANDBOX_MODE', false );

// PayPal REST API Credentials
define( 'DONATION_PAYPAL_CLIENT_ID', 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX' );
define( 'DONATION_PAYPAL_CLIENT_SECRET', 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX' );
define( 'DONATION_PAYPAL_PLAN_ID', 'P-XXXXXXXXXXXXXXXXXXX' );

define( 'DONATION_PAYPAL_EMAIL', 'hello@example.com' );

```

