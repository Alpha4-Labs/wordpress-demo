# WordPress Loyalty Plugin

Full-featured WordPress plugin for Loyalteez integration.

## Features

- 📊 User dashboard shortcode
- 🔥 Streak tracking
- 🏆 Leaderboards
- 🎁 Perks marketplace
- 🏅 Achievements
- 📝 Comment rewards
- 👤 User registration rewards
- 📅 Daily visit tracking

## Installation

1. Upload the plugin to `/wp-content/plugins/`
2. Activate the plugin
3. Configure in Settings → Loyalteez:
   - Brand ID
   - API URL
   - Shared Services URL

## Shortcodes

- `[loyalteez_dashboard]` - Full user dashboard
- `[loyalteez_balance]` - Balance display
- `[loyalteez_streak]` - Streak status
- `[loyalteez_leaderboard]` - Leaderboard
- `[loyalteez_perks]` - Perks list

## REST API Endpoints

- `GET /wp-json/loyalteez/v1/leaderboard` - Get leaderboard data
- `GET /wp-json/loyalteez/v1/streak` - Get user streak
- `GET /wp-json/loyalteez/v1/balance` - Get user balance

## Hooks

The plugin hooks into WordPress events:
- `comment_post` - Award LTZ for comments
- `user_register` - Welcome bonus
- `wp_login` - Daily visit tracking

## Shared Services Integration

Uses `loyalteez-shared-services` for:
- Streak tracking
- Leaderboard stats
- Achievement progress
- Perks catalog
