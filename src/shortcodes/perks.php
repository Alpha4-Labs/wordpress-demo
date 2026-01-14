<?php
/**
 * Perks Shortcode
 * 
 * Displays available perks for redemption
 * 
 * Usage: [loyalteez_perks category="all"]
 */

function loyalteez_perks_shortcode($atts) {
    $user = wp_get_current_user();
    if (!$user->ID) {
        return '<p>Please log in to view perks.</p>';
    }

    $atts = shortcode_atts([
        'category' => 'all'
    ], $atts);

    $brand_id = get_option('loyalteez_brand_id', '');
    if (!$brand_id) {
        return '<p>Loyalteez is not configured.</p>';
    }

    $user_identifier = "wordpress_{$user->ID}@loyalteez.app";
    $shared_services_url = get_option('loyalteez_shared_services_url', 'https://services.loyalteez.app');
    $api_url = get_option('loyalteez_api_url', 'https://api.loyalteez.app');

    // Enqueue scripts
    $plugin_url = plugin_dir_url(dirname(dirname(__DIR__)) . '/loyalteez-rewards.php');
    wp_enqueue_script('loyalteez-perks', $plugin_url . 'assets/js/perks.js', ['jquery'], '1.0.0', true);
    wp_enqueue_style('loyalteez-dashboard', $plugin_url . 'assets/css/dashboard.css', [], '1.0.0');

    wp_localize_script('loyalteez-perks', 'loyalteezPerksData', [
        'brandId' => $brand_id,
        'userIdentifier' => $user_identifier,
        'sharedServicesUrl' => $shared_services_url,
        'apiUrl' => $api_url,
        'nonce' => wp_create_nonce('wp_rest'),
        'category' => $atts['category']
    ]);

    ob_start();
    ?>
    <div class="loyalteez-perks-widget">
        <div id="loyalteez-perks-list">Loading...</div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('loyalteez_perks', 'loyalteez_perks_shortcode');
