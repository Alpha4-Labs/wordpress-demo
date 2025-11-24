# Loyalteez WordPress Plugin

[![Try Live Demo](https://img.shields.io/badge/Try_Live_Demo-WordPress_Playground-blue?style=for-the-badge&logo=wordpress)](https://playground.wordpress.net/?plugin=https://api.loyalteez.app/loyalteez-api/wordpress-plugin.zip&url=/wp-admin/options-general.php?page=loyalteez-rewards)

Integrate your WordPress site with the Loyalteez Ecosystem. Automatically reward users with LTZ tokens for engaging with your content.

## Features

- **Dynamic Event System**: Add unlimited custom events - map any WordPress hook to any Loyalteez event name
- **Comment Rewards**: Reward users for approved comments (configurable event name)
- **Signup Rewards**: Reward new user registrations (configurable event name)
- **Daily Visit Rewards**: Reward logged-in users for their first visit of the day (configurable event name)
- **Social Sharing**: Frontend AJAX implementation for rewarding content shares
- **Flexible Hook Support**: Works with any WordPress action hook (`comment_post`, `user_register`, `init`, `wp_login`, `publish_post`, WooCommerce hooks, etc.)
- **Admin Dashboard**: Easy configuration with test button to verify API connection
- **Debug Mode**: Comprehensive logging for troubleshooting

## ⚡ Quick Test

You can try this plugin instantly in your browser without installing anything.

**[Click here to launch the Live Demo](https://playground.wordpress.net/?plugin=https://api.loyalteez.app/loyalteez-api/wordpress-plugin.zip&url=/wp-admin/options-general.php?page=loyalteez-rewards)**

See [TESTING.md](TESTING.md) for details on how to use the playground environment.

## Installation

1.  Download this repository as a `.zip` file or clone it:
    ```bash
    git clone https://github.com/Alpha4-Labs/wordpress-demo.git
    cd wordpress-demo
    ```
2.  Go to your WordPress Admin > Plugins > Add New > Upload Plugin.
3.  Upload the zip file (or the entire `wordpress-loyalty-plugin` folder) and activate.
4.  Go to Settings > Loyalteez Rewards.
5.  Enter your **Brand ID** (Wallet Address) from the [Loyalteez Partner Portal](https://partner.loyalteez.app).
6.  Add and enable events you want to track (see Configuration below).

## Configuration

### Getting your Brand ID
1.  Log in to [partner.loyalteez.app](https://partner.loyalteez.app).
2.  Copy your Brand Wallet Address from the dashboard.
3.  Paste it into the plugin settings.

### Event Configuration

The plugin uses a **dynamic event system** - you can add unlimited events and map them to your Loyalteez event names.

1. **Add Event**: Click the "Add Event" button in the settings page
2. **WordPress Hook**: Enter the WordPress action/hook name (e.g., `comment_post`, `user_register`, `init`)
3. **Loyalteez Event Name**: Enter the exact event name from your Partner Portal (e.g., `send_comment`, `user_registration`)
4. **Enable**: Check the box to activate the event
5. **Save Changes**

**Common WordPress Hooks:**
- `comment_post` - When a comment is posted
- `user_register` - When a new user registers
- `init` - On every page load (useful for daily visit tracking)
- `wp_login` - When a user logs in
- `publish_post` - When a post is published
- `woocommerce_order_status_completed` - WooCommerce order completion

**Important**: Event names must match **exactly** what you've configured in your Partner Portal → Settings → LTZ Distribution.

### Test API Connection

Use the "Test API Connection" section at the bottom of the settings page to:
- Send a test event manually
- Verify your Brand ID and domain are configured correctly
- See detailed error messages if something isn't working

### Debug Mode
Enable "Debug Mode" in settings to log API requests and responses to your `wp-content/debug.log` file. Useful for troubleshooting connection issues.

**Note**: Debug logging requires `WP_DEBUG` to be enabled in your `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Developer Notes

### Programmatic Event Sending

You can send events programmatically from your theme or other plugins:

```php
// Load the API class
require_once WP_PLUGIN_DIR . '/loyalteez-rewards/includes/class-api.php';

// Send a custom event
$api = new Loyalteez_API();
$result = $api->send_event('custom_event_name', 'user@example.com', [
    'custom_data' => 'value',
    'source' => 'my_custom_function'
]);

if (is_wp_error($result)) {
    error_log('Loyalteez Error: ' . $result->get_error_message());
}
```

### Adding Custom WordPress Hooks

The plugin automatically handles common hooks, but you can add any WordPress action hook through the admin interface. The plugin will attempt to extract the user's email from the hook context automatically.

For hooks that don't provide email context, you may need to send events programmatically (see above).

### Frontend Sharing
To use the share reward, add the class `loyalteez-share` to any button/link:

```html
<button class="loyalteez-share" data-url="https://mysite.com/awesome-post">Share this!</button>
```

The plugin handles the click, prompts for email (if not logged in), and triggers the reward.

## Domain Configuration

**Important**: For the plugin to work, you must configure your website domain in the Partner Portal:

1. Go to [Partner Portal](https://partner.loyalteez.app) → Settings → Profile
2. Under "Authentication Methods" → "Domain", enter your website URL
3. Example: `https://yourbrand.com` or `https://www.yourbrand.com`

The plugin sends your site's domain with each event for authentication. Make sure it matches exactly what you configured in the Partner Portal.

## Requirements
- WordPress 5.0+
- PHP 7.4+
- A Loyalteez Partner Account with Brand ID configured
- Domain configured in Partner Portal for authentication

## Troubleshooting

### Events Not Triggering
1. Check that events are enabled in plugin settings
2. Verify event names match exactly with Partner Portal configuration
3. Enable Debug Mode and check `wp-content/debug.log`
4. Use the "Test API Connection" button to verify API access

### API Errors
- **403 Forbidden**: Domain not configured in Partner Portal → Settings → Profile → Domain
- **400 Bad Request**: Event name doesn't exist in Partner Portal → Settings → LTZ Distribution
- **Network Errors**: Check your server can reach `api.loyalteez.app`

### CORS Issues
The plugin makes **server-side** API calls (no browser CORS). If you see CORS errors, they're likely from:
- WordPress Playground trying to load the plugin zip (use the proxy URL)
- Frontend JavaScript (check browser console for specific errors)

## Support

- **Documentation**: [Developer Docs](https://docs.loyalteez.app)
- **Issues**: [GitHub Issues](https://github.com/Alpha4-Labs/wordpress-demo/issues)
- **Partner Portal**: [partner.loyalteez.app](https://partner.loyalteez.app)
