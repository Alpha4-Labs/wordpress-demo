# Implementation Plan: WordPress Loyalty Plugin

## 1. Goal
Create a WordPress plugin that integrates with Loyalteez to reward users for:
- Newsletter Signups
- Post Comments
- Post Shares (Conceptual/JS tracked)
- Daily Visits (Conceptual/Monthly Active)

## 2. Architecture
Follows the "WordPress Plugin" section of `demo_builds_conceptual_plans.md`.

### File Structure
```
wordpress-loyalty-plugin/
├── loyalteez-rewards.php        # Main entry point
├── includes/
│   ├── class-api.php            # Handles API calls to Loyalteez
│   ├── class-admin.php          # Admin menu and settings registration
│   ├── class-hooks.php          # WordPress action hooks (comments, etc.)
│   └── class-utils.php          # Helper functions (logging, etc.)
├── admin/
│   └── settings-page.php        # HTML for settings page
├── assets/
│   ├── js/
│   │   └── loyalteez-public.js  # Frontend tracking (shares)
│   └── css/
│       └── loyalteez.css        # Styles
├── README.md
└── RECIPES.md
```

## 3. Components

### Main File (`loyalteez-rewards.php`)
- Defines constants (VERSION, PATH).
- Instantiates the main classes.
- Activation/Deactivation hooks (optional, mainly for clearing options).

### API Wrapper (`includes/class-api.php`)
- `send_event($event_type, $email, $metadata)`
- Uses `wp_remote_post` for HTTP calls.
- Endpoint: `https://api.loyalteez.app/loyalteez-api/manual-event`
- Authentication: Uses `brandId` from options.

### Admin Settings (`includes/class-admin.php`)
- Adds "Loyalteez" to WP Admin Menu.
- Fields:
    - Brand ID (Required)
    - Enable/Disable specific rewards (Comment, Signup, etc.)
    - Debug Mode (Log to `debug.log`)

### Hooks Logic (`includes/class-hooks.php`)
1.  **Comments**: Hook `comment_post`. Check if approved (or handle `wp_set_comment_status`).
    -   Get email from comment author.
    -   Call API `post_comment`.
2.  **Registration**: Hook `user_register`.
    -   Call API `user_register`.
3.  **Login/Daily Visit**: Hook `wp_login` or `init` (for logged in users).
    -   Check transient `ltz_daily_visit_{user_id}`.
    -   If expired, call API `daily_visit`, set transient for 24h.

### Frontend (`assets/js/loyalteez-public.js`)
- "Share" button listener (conceptual demo).
- On click -> AJAX to WP -> WP calls Loyalteez API `content_share`.

## 4. Cloudflare/Hosting
- This is a PHP plugin, but documentation will mention it can run on any WP host.
- No specific Cloudflare Worker needed unless we want to proxy requests (skipped for simplicity as per pattern).

## 5. Validation
- User Email is key.
- Brand ID must be configured.
- Error handling: `try/catch` around API calls, `error_log` if debug enabled.


