<?php
/**
 * Dashboard Shortcode
 * 
 * Displays user's loyalty dashboard with balance, streak, leaderboard, and perks.
 * 
 * Usage: [loyalteez_dashboard]
 */

function loyalteez_dashboard_shortcode($atts) {
    $user = wp_get_current_user();
    if (!$user->ID) {
        return '<p>Please log in to view your dashboard.</p>';
    }

    $brand_id = get_option('loyalteez_brand_id', '');
    if (!$brand_id) {
        return '<p>Loyalteez is not configured. Please contact the administrator.</p>';
    }

    $user_identifier = "wordpress_{$user->ID}@loyalteez.app";
    $shared_services_url = get_option('loyalteez_shared_services_url', 'https://services.loyalteez.app');

    // Enqueue scripts and styles
    $plugin_url = defined('LOYALTEEZ_PLUGIN_DIR') 
        ? plugin_dir_url(LOYALTEEZ_PLUGIN_DIR . 'loyalteez-rewards.php')
        : plugin_dir_url(dirname(dirname(__DIR__)) . '/loyalteez-rewards.php');
    wp_enqueue_script('loyalteez-dashboard', $plugin_url . 'assets/js/dashboard.js', ['jquery'], '1.0.0', true);
    wp_enqueue_style('loyalteez-dashboard', $plugin_url . 'assets/css/dashboard.css', [], '1.0.0');

    // Pass data to JavaScript
    wp_localize_script('loyalteez-dashboard', 'loyalteezData', [
        'brandId' => $brand_id,
        'userIdentifier' => $user_identifier,
        'sharedServicesUrl' => $shared_services_url,
        'apiUrl' => get_option('loyalteez_api_url', 'https://api.loyalteez.app'),
        'nonce' => wp_create_nonce('wp_rest')
    ]);

    ob_start();
    ?>
    <div id="loyalteez-dashboard" class="loyalteez-dashboard">
        <div class="dashboard-header">
            <h2>Loyalteez Dashboard</h2>
            <p>Welcome, <?php echo esc_html($user->display_name); ?>!</p>
        </div>

        <div class="dashboard-tabs">
            <button class="tab-button active" data-tab="balance">💰 Balance</button>
            <button class="tab-button" data-tab="streak">🔥 Streak</button>
            <button class="tab-button" data-tab="leaderboard">🏆 Leaderboard</button>
            <button class="tab-button" data-tab="perks">🎁 Perks</button>
            <button class="tab-button" data-tab="achievements">🏅 Achievements</button>
        </div>

        <div class="dashboard-content">
            <div id="tab-balance" class="tab-content active">
                <div class="balance-display">
                    <div class="balance-amount" id="balance-amount">Loading...</div>
                    <div class="balance-label">LTZ</div>
                </div>
                <p>Earn LTZ by commenting, visiting daily, and engaging with content!</p>
            </div>

            <div id="tab-streak" class="tab-content">
                <div class="streak-display" id="streak-display">Loading...</div>
            </div>

            <div id="tab-leaderboard" class="tab-content">
                <div class="leaderboard-filters">
                    <select id="leaderboard-metric">
                        <option value="ltz_earned">LTZ Earned</option>
                        <option value="activity">Activity</option>
                        <option value="streak">Streak</option>
                    </select>
                    <select id="leaderboard-period">
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="all">All Time</option>
                    </select>
                </div>
                <div id="leaderboard-list">Loading...</div>
            </div>

            <div id="tab-perks" class="tab-content">
                <div id="perks-list">Loading...</div>
            </div>

            <div id="tab-achievements" class="tab-content">
                <div id="achievements-list">Loading...</div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('loyalteez_dashboard', 'loyalteez_dashboard_shortcode');
