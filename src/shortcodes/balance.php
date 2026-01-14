<?php
/**
 * Balance Shortcode
 * 
 * Displays user's current LTZ balance
 * 
 * Usage: [loyalteez_balance]
 */

function loyalteez_balance_shortcode($atts) {
    $user = wp_get_current_user();
    if (!$user->ID) {
        return '<p>Please log in to view your balance.</p>';
    }

    $brand_id = get_option('loyalteez_brand_id', '');
    if (!$brand_id) {
        return '<p>Loyalteez is not configured.</p>';
    }

    $user_identifier = "wordpress_{$user->ID}@loyalteez.app";
    $api_url = get_option('loyalteez_api_url', 'https://api.loyalteez.app');

    // Enqueue scripts
    $plugin_url = plugin_dir_url(dirname(dirname(__DIR__)) . '/loyalteez-rewards.php');
    wp_enqueue_script('loyalteez-balance', $plugin_url . 'assets/js/balance.js', ['jquery'], '1.0.0', true);
    wp_enqueue_style('loyalteez-dashboard', $plugin_url . 'assets/css/dashboard.css', [], '1.0.0');

    wp_localize_script('loyalteez-balance', 'loyalteezBalanceData', [
        'brandId' => $brand_id,
        'userIdentifier' => $user_identifier,
        'apiUrl' => $api_url
    ]);

    ob_start();
    ?>
    <div class="loyalteez-balance-widget">
        <div class="balance-display">
            <div class="balance-amount" id="loyalteez-balance-amount">Loading...</div>
            <div class="balance-label">LTZ</div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('loyalteez_balance', 'loyalteez_balance_shortcode');
