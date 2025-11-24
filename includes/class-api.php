<?php

class Loyalteez_API {

    private $api_url = 'https://api.loyalteez.app/loyalteez-api/manual-event';
    private $brand_id;

    public function __construct() {
        $this->brand_id = get_option('loyalteez_brand_id');
    }

    /**
     * Send an event to Loyalteez API
     *
     * @param string $event_type The type of event (e.g., 'post_comment', 'newsletter_signup')
     * @param string $email      The user's email address
     * @param array  $metadata   Optional metadata to include
     * @return bool|WP_Error     True on success, WP_Error on failure
     */
    public function send_event($event_type, $email, $metadata = []) {
        if (empty($this->brand_id)) {
            return new WP_Error('missing_brand_id', 'Loyalteez Brand ID is not configured.');
        }

        if (empty($email) || !is_email($email)) {
            return new WP_Error('invalid_email', 'Invalid email address provided.');
        }

        // Extract just the hostname from site URL for domain validation
        $site_url = get_site_url();
        $parsed_url = parse_url($site_url);
        $domain = isset($parsed_url['host']) ? $parsed_url['host'] : $site_url;
        
        $body = [
            'brandId'   => $this->brand_id,
            'eventType' => $event_type,
            'userEmail' => $email,
            'domain'    => $domain, // Just the hostname, not full URL
            'metadata'  => array_merge([
                'platform'   => 'wordpress',
                'timestamp'  => current_time('c'),
                'source_url' => get_permalink() ?: home_url(),
                'site_url'   => $site_url, // Full URL in metadata for reference
            ], $metadata)
        ];

        $args = [
            'body'        => json_encode($body),
            'headers'     => [
                'Content-Type' => 'application/json',
                'User-Agent'   => 'Loyalteez-WordPress-Plugin/1.0.0'
            ],
            'timeout'     => 15,
            'blocking'    => true, // We want to know if it succeeded, but could be false for performance if queueing
            'data_format' => 'body',
        ];

        $response = wp_remote_post($this->api_url, $args);

        if (is_wp_error($response)) {
            $this->log_error($response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code >= 200 && $response_code < 300) {
            return true;
        } else {
            $this->log_error("API Error ($response_code): $response_body");
            return new WP_Error('api_error', "Loyalteez API returned $response_code", ['body' => $response_body]);
        }
    }

    /**
     * Log errors to WP debug log if enabled
     */
    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG && get_option('loyalteez_debug_mode')) {
            error_log('[Loyalteez] ' . $message);
        }
    }
}

