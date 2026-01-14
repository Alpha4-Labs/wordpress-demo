<?php
/**
 * Leaderboard Shortcode
 * 
 * Displays leaderboard rankings
 * 
 * Usage: [loyalteez_leaderboard metric="ltz_earned" period="week" limit="10"]
 */

function loyalteez_leaderboard_shortcode($atts) {
    $atts = shortcode_atts([
        'metric' => 'ltz_earned',
        'period' => 'week',
        'limit' => '10'
    ], $atts);

    $brand_id = get_option('loyalteez_brand_id', '');
    if (!$brand_id) {
        return '<p>Loyalteez is not configured.</p>';
    }

    $shared_services_url = get_option('loyalteez_shared_services_url', 'https://services.loyalteez.app');

    // Enqueue scripts
    $plugin_url = plugin_dir_url(dirname(dirname(__DIR__)) . '/loyalteez-rewards.php');
    wp_enqueue_script('loyalteez-leaderboard', $plugin_url . 'assets/js/leaderboard.js', ['jquery'], '1.0.0', true);
    wp_enqueue_style('loyalteez-dashboard', $plugin_url . 'assets/css/dashboard.css', [], '1.0.0');

    wp_localize_script('loyalteez-leaderboard', 'loyalteezLeaderboardData', [
        'brandId' => $brand_id,
        'sharedServicesUrl' => $shared_services_url,
        'metric' => $atts['metric'],
        'period' => $atts['period'],
        'limit' => intval($atts['limit'])
    ]);

    ob_start();
    ?>
    <div class="loyalteez-leaderboard-widget">
        <div class="leaderboard-filters">
            <select id="loyalteez-leaderboard-metric">
                <option value="ltz_earned" <?php selected($atts['metric'], 'ltz_earned'); ?>>LTZ Earned</option>
                <option value="activity" <?php selected($atts['metric'], 'activity'); ?>>Activity</option>
                <option value="streak" <?php selected($atts['metric'], 'streak'); ?>>Streak</option>
            </select>
            <select id="loyalteez-leaderboard-period">
                <option value="week" <?php selected($atts['period'], 'week'); ?>>This Week</option>
                <option value="month" <?php selected($atts['period'], 'month'); ?>>This Month</option>
                <option value="all" <?php selected($atts['period'], 'all'); ?>>All Time</option>
            </select>
        </div>
        <div id="loyalteez-leaderboard-list">Loading...</div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('loyalteez_leaderboard', 'loyalteez_leaderboard_shortcode');
