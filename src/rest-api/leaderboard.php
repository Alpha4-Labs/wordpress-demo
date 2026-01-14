<?php
/**
 * REST API Endpoint for Leaderboard
 * 
 * Provides leaderboard data via WordPress REST API
 */

function loyalteez_register_leaderboard_endpoint() {
    register_rest_route('loyalteez/v1', '/leaderboard', [
        'methods' => 'GET',
        'callback' => 'loyalteez_get_leaderboard',
        'permission_callback' => '__return_true', // Public endpoint
        'args' => [
            'metric' => [
                'default' => 'ltz_earned',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'period' => [
                'default' => 'week',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'limit' => [
                'default' => 10,
                'sanitize_callback' => 'absint',
            ],
        ],
    ]);
}
add_action('rest_api_init', 'loyalteez_register_leaderboard_endpoint');

function loyalteez_get_leaderboard($request) {
    $brand_id = get_option('loyalteez_brand_id', '');
    if (!$brand_id) {
        return new WP_Error('not_configured', 'Loyalteez is not configured', ['status' => 500]);
    }

    $metric = $request->get_param('metric');
    $period = $request->get_param('period');
    $limit = $request->get_param('limit');
    $shared_services_url = get_option('loyalteez_shared_services_url', 'https://services.loyalteez.app');

    $url = add_query_arg([
        'metric' => $metric,
        'period' => $period,
        'platform' => 'wordpress',
        'limit' => $limit
    ], "{$shared_services_url}/leaderboard/{$brand_id}");

    $response = wp_remote_get($url, [
        'headers' => [
            'Content-Type' => 'application/json'
        ]
    ]);

    if (is_wp_error($response)) {
        return new WP_Error('api_error', $response->get_error_message(), ['status' => 500]);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    return rest_ensure_response($data);
}
