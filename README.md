# Loyalteez WordPress Plugin

[![Try Live Demo](https://img.shields.io/badge/Try_Live_Demo-WordPress_Playground-blue?style=for-the-badge&logo=wordpress)](https://playground.wordpress.net/?plugin=https://github.com/Alpha4-Labs/wordpress-demo/archive/refs/heads/main.zip&url=/wp-admin/options-general.php?page=loyalteez-rewards)

Integrate your WordPress site with the Loyalteez Ecosystem. Automatically reward users with LTZ tokens for engaging with your content.

## Features

- **Comment Rewards**: Reward users for approved comments.
- **Signup Rewards**: Reward new user registrations.
- **Daily Visit Rewards**: Reward logged-in users for their first visit of the day.
- **Social Sharing**: Conceptual implementation for rewarding content shares (via AJAX).
- **Custom Event Mapping**: Map WordPress actions to your specific Loyalteez Event Names.
- **Admin Dashboard**: Configure your Brand ID and toggle rewards easily.

## ⚡ Quick Test

You can try this plugin instantly in your browser without installing anything.

**[Click here to launch the Live Demo](https://playground.wordpress.net/?plugin=https://github.com/Alpha4-Labs/wordpress-demo/archive/refs/heads/main.zip&url=/wp-admin/options-general.php?page=loyalteez-rewards)**

See [TESTING.md](TESTING.md) for details on how to use the playground environment.

## Installation

1.  Download this folder as a `.zip` file (e.g., `loyalteez-rewards.zip`).
2.  Go to your WordPress Admin > Plugins > Add New > Upload Plugin.
3.  Upload the zip file and activate.
4.  Go to Settings > Loyalteez Rewards.
5.  Enter your **Brand ID** (Wallet Address) from the Loyalteez Partner Portal.
6.  Enable the rewards you want to activate.

## Configuration

### Getting your Brand ID
1.  Log in to [partner.loyalteez.app](https://partner.loyalteez.app).
2.  Copy your Brand Wallet Address from the dashboard.
3.  Paste it into the plugin settings.

### Event Mapping
You can customize the Event Name sent to Loyalteez for each action. This allows you to match the event names defined in your Partner Portal.

*   **Comments**: Default `post_comment`
*   **Signups**: Default `user_registration`
*   **Daily Visit**: Default `daily_visit`

### Debug Mode
Enable "Debug Mode" in settings to log API responses to your `wp-content/debug.log` file. Useful for troubleshooting connection issues.

## Developer Notes

### Adding Custom Events
You can easily add custom events in your theme's `functions.php` by instantiating the API class:

```php
$api = new Loyalteez_API();
$api->send_event('custom_action', $user_email, ['key' => 'value']);
```

(Note: You'll need to ensure the class is loaded or accessible).

### Frontend Sharing
To use the share reward, add the class `loyalteez-share` to any button/link:

```html
<button class="loyalteez-share" data-url="https://mysite.com/awesome-post">Share this!</button>
```

The plugin handles the click, prompts for email (if not logged in), and triggers the reward.

## Requirements
- WordPress 5.0+
- PHP 7.4+
