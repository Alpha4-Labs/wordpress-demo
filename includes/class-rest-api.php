<?php
/**
 * REST API Endpoints for Loyalteez WordPress Plugin
 * 
 * Provides REST API endpoints for dashboard interactions:
 * - /wp-json/loyalteez/v1/checkin - Daily check-in
 * - /wp-json/loyalteez/v1/redeem - Perk redemption
 */

class Loyalteez_REST_API {

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Daily check-in endpoint
        register_rest_route('loyalteez/v1', '/checkin', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_checkin'],
            'permission_callback' => 'is_user_logged_in'
        ]);

        // Perk redemption endpoint
        register_rest_route('loyalteez/v1', '/redeem', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_redeem'],
            'permission_callback' => 'is_user_logged_in',
            'args' => [
                'perkId' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // Claim milestone endpoint
        register_rest_route('loyalteez/v1', '/claim-milestone', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_claim_milestone'],
            'permission_callback' => 'is_user_logged_in',
            'args' => [
                'milestoneDays' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);
    }

    /**
     * Handle daily check-in
     */
    public function handle_checkin($request) {
        $user = wp_get_current_user();
        if (!$user->ID) {
            return new WP_Error('not_logged_in', 'User must be logged in', ['status' => 401]);
        }

        $brand_id = get_option('loyalteez_brand_id', '');
        if (!$brand_id) {
            return new WP_Error('not_configured', 'Loyalteez is not configured', ['status' => 500]);
        }

        $user_identifier = "wordpress_{$user->ID}@loyalteez.app";
        $shared_services_url = get_option('loyalteez_shared_services_url', 'https://services.loyalteez.app');

        // Record activity via shared services
        $response = wp_remote_post("{$shared_services_url}/streak/record-activity", [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'brandId' => $brand_id,
                'userIdentifier' => $user_identifier,
                'platform' => 'wordpress',
                'streakType' => 'daily'
            ]),
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('api_error', $response->get_error_message(), ['status' => 500]);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !$data['success']) {
            return new WP_Error('checkin_failed', $data['error'] ?? 'Failed to check in', ['status' => 400]);
        }

        // If new activity, send reward event
        if ($data['isNewActivity']) {
            $api = new Loyalteez_API();
            $base_reward = 10; // Default daily reward
            $total_reward = floor($base_reward * ($data['multiplier'] ?? 1.0));
            
            $api->send_event('wordpress_daily_checkin', $user->user_email, [
                'streak_day' => $data['currentStreak'],
                'multiplier' => $data['multiplier'],
                'total_reward' => $total_reward
            ]);
        }

        return rest_ensure_response([
            'success' => true,
            'streak' => [
                'currentStreak' => $data['currentStreak'],
                'multiplier' => $data['multiplier'],
                'nextMilestone' => $data['unclaimedMilestones'][0] ?? null,
                'unclaimedMilestones' => $data['unclaimedMilestones'] ?? []
            ],
            'reward' => $data['isNewActivity'] ? floor(10 * ($data['multiplier'] ?? 1.0)) : 0,
            'alreadyClaimed' => !$data['isNewActivity']
        ]);
    }

    /**
     * Handle perk redemption
     */
    public function handle_redeem($request) {
        $user = wp_get_current_user();
        if (!$user->ID) {
            return new WP_Error('not_logged_in', 'User must be logged in', ['status' => 401]);
        }

        $brand_id = get_option('loyalteez_brand_id', '');
        if (!$brand_id) {
            return new WP_Error('not_configured', 'Loyalteez is not configured', ['status' => 500]);
        }

        $perk_id = $request->get_param('perkId');
        if (!$perk_id) {
            return new WP_Error('missing_perk_id', 'Perk ID is required', ['status' => 400]);
        }

        $user_identifier = "wordpress_{$user->ID}@loyalteez.app";
        $shared_services_url = get_option('loyalteez_shared_services_url', 'https://services.loyalteez.app');

        // Redeem perk via shared services
        $response = wp_remote_post("{$shared_services_url}/perks/redeem", [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'brandId' => $brand_id,
                'userIdentifier' => $user_identifier,
                'platform' => 'wordpress',
                'perkId' => $perk_id
            ]),
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('api_error', $response->get_error_message(), ['status' => 500]);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !$data['success']) {
            return new WP_Error('redeem_failed', $data['error'] ?? 'Failed to redeem perk', ['status' => 400]);
        }

        return rest_ensure_response([
            'success' => true,
            'confirmationCode' => $data['confirmationCode'] ?? null,
            'perk' => $data['perk'] ?? null
        ]);
    }

    /**
     * Handle milestone claim
     */
    public function handle_claim_milestone($request) {
        $user = wp_get_current_user();
        if (!$user->ID) {
            return new WP_Error('not_logged_in', 'User must be logged in', ['status' => 401]);
        }

        $brand_id = get_option('loyalteez_brand_id', '');
        if (!$brand_id) {
            return new WP_Error('not_configured', 'Loyalteez is not configured', ['status' => 500]);
        }

        $milestone_days = $request->get_param('milestoneDays');
        if (!$milestone_days) {
            return new WP_Error('missing_milestone', 'Milestone days is required', ['status' => 400]);
        }

        $user_identifier = "wordpress_{$user->ID}@loyalteez.app";
        $shared_services_url = get_option('loyalteez_shared_services_url', 'https://services.loyalteez.app');

        // Claim milestone via shared services
        $response = wp_remote_post("{$shared_services_url}/streak/claim-milestone", [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'brandId' => $brand_id,
                'userIdentifier' => $user_identifier,
                'platform' => 'wordpress',
                'milestoneDays' => $milestone_days,
                'streakType' => 'daily'
            ]),
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('api_error', $response->get_error_message(), ['status' => 500]);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !$data['success']) {
            return new WP_Error('claim_failed', $data['error'] ?? $data['reason'] ?? 'Failed to claim milestone', ['status' => 400]);
        }

        // Send reward event for milestone bonus
        $api = new Loyalteez_API();
        $api->send_event('wordpress_milestone_claim', $user->user_email, [
            'milestone_days' => $milestone_days,
            'bonus_ltz' => $data['bonusLtz'] ?? $data['bonus']
        ]);

        return rest_ensure_response([
            'success' => true,
            'bonusLtz' => $data['bonusLtz'] ?? $data['bonus'],
            'milestone' => $milestone_days
        ]);
    }
}
