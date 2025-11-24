<?php

class Loyalteez_Admin {

    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        // Handle custom events saving - use higher priority to run after form submission
        add_action('admin_init', [$this, 'save_custom_events'], 20);
        // Also hook into the option update to catch it
        add_filter('pre_update_option_loyalteez_custom_events', [$this, 'pre_update_custom_events'], 10, 2);
        // Handle test event
        add_action('admin_post_loyalteez_test_event', [$this, 'handle_test_event']);
    }

    public function add_admin_menu() {
        add_options_page(
            'Loyalteez Rewards',
            'Loyalteez',
            'manage_options',
            'loyalteez-rewards',
            [$this, 'display_settings_page']
        );
    }

    public function register_settings() {
        // Register settings with sanitization callbacks
        register_setting('loyalteez_options_group', 'loyalteez_brand_id', [
            'sanitize_callback' => [$this, 'sanitize_brand_id'],
            'default' => ''
        ]);
        
        // Toggles
        register_setting('loyalteez_options_group', 'loyalteez_reward_comments', [
            'sanitize_callback' => 'absint',
            'default' => 0
        ]);
        register_setting('loyalteez_options_group', 'loyalteez_reward_signups', [
            'sanitize_callback' => 'absint',
            'default' => 0
        ]);
        register_setting('loyalteez_options_group', 'loyalteez_reward_daily_visit', [
            'sanitize_callback' => 'absint',
            'default' => 0
        ]);
        
        // Event Names
        register_setting('loyalteez_options_group', 'loyalteez_event_name_comments', [
            'sanitize_callback' => [$this, 'sanitize_event_name'],
            'default' => 'post_comment'
        ]);
        register_setting('loyalteez_options_group', 'loyalteez_event_name_signups', [
            'sanitize_callback' => [$this, 'sanitize_event_name'],
            'default' => 'user_registration'
        ]);
        register_setting('loyalteez_options_group', 'loyalteez_event_name_daily_visit', [
            'sanitize_callback' => [$this, 'sanitize_event_name'],
            'default' => 'daily_visit'
        ]);
        
        register_setting('loyalteez_options_group', 'loyalteez_debug_mode', [
            'sanitize_callback' => 'absint',
            'default' => 0
        ]);
        
        // Register custom events setting (handled separately)
        register_setting('loyalteez_options_group', 'loyalteez_custom_events', [
            'sanitize_callback' => [$this, 'sanitize_custom_events'],
            'default' => []
        ]);
    }
    
    /**
     * Save custom events - handles form submission
     */
    public function save_custom_events() {
        // Check if this is our settings form submission
        if (!isset($_POST['option_page']) || $_POST['option_page'] !== 'loyalteez_options_group') {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if form was submitted (either submit button or our hidden field)
        if (!isset($_POST['submit']) && !isset($_POST['loyalteez_events_submitted'])) {
            return;
        }
        
        // Try to get events from POST array first
        $events = null;
        if (isset($_POST['loyalteez_events']) && is_array($_POST['loyalteez_events'])) {
            $events = $this->sanitize_custom_events($_POST['loyalteez_events']);
        } 
        // Fallback: try JSON backup field
        elseif (isset($_POST['loyalteez_events_json']) && !empty($_POST['loyalteez_events_json'])) {
            $json_data = json_decode(stripslashes($_POST['loyalteez_events_json']), true);
            if (is_array($json_data)) {
                $events = $this->sanitize_custom_events($json_data);
            }
        }
        
        // Save events if we have them
        if ($events !== null) {
            $result = update_option('loyalteez_custom_events', $events, false);
            
            // Debug logging
            if (defined('WP_DEBUG') && WP_DEBUG && get_option('loyalteez_debug_mode')) {
                error_log('[Loyalteez] Saved ' . count($events) . ' events. Update result: ' . ($result ? 'success' : 'no change'));
                error_log('[Loyalteez] Events data: ' . print_r($events, true));
            }
        } elseif (isset($_POST['loyalteez_events_submitted'])) {
            // Form was submitted but no events - user removed all events
            update_option('loyalteez_custom_events', [], false);
            
            if (defined('WP_DEBUG') && WP_DEBUG && get_option('loyalteez_debug_mode')) {
                error_log('[Loyalteez] Form submitted with no events - cleared option');
            }
        }
        // If neither condition is met, preserve existing events
    }
    
    /**
     * Filter to handle custom events option update
     */
    public function pre_update_custom_events($value, $old_value) {
        // If value is coming from POST, use that instead
        if (isset($_POST['loyalteez_events']) && is_array($_POST['loyalteez_events'])) {
            return $this->sanitize_custom_events($_POST['loyalteez_events']);
        }
        return $value;
    }
    
    /**
     * Sanitize custom events array
     */
    public function sanitize_custom_events($input) {
        if (!is_array($input)) {
            return [];
        }
        
        $sanitized = [];
        foreach ($input as $event) {
            if (!isset($event['hook']) || !isset($event['event_name'])) {
                continue; // Skip invalid entries
            }
            
            $hook = sanitize_text_field($event['hook']);
            $event_name = sanitize_text_field($event['event_name']);
            
            // Remove invalid characters from hook and event name
            $hook = preg_replace('/[^a-zA-Z0-9_]/', '', $hook);
            $event_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $event_name);
            
            if (empty($hook) || empty($event_name)) {
                continue; // Skip empty entries
            }
            
            $sanitized[] = [
                'hook' => $hook,
                'event_name' => $event_name,
                'enabled' => isset($event['enabled']) ? 1 : 0
            ];
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize Brand ID (wallet address)
     */
    public function sanitize_brand_id($input) {
        $input = sanitize_text_field($input);
        // Basic validation - should be hex address format
        if (!empty($input) && !preg_match('/^0x[a-fA-F0-9]{40}$/', $input)) {
            add_settings_error('loyalteez_brand_id', 'invalid_brand_id', 'Brand ID should be a valid Ethereum address (0x followed by 40 hex characters).');
            return get_option('loyalteez_brand_id'); // Return existing value on error
        }
        return $input;
    }
    
    /**
     * Sanitize event name (alphanumeric, underscores, hyphens only)
     */
    public function sanitize_event_name($input) {
        $input = sanitize_text_field($input);
        // Allow alphanumeric, underscores, hyphens
        $input = preg_replace('/[^a-zA-Z0-9_-]/', '', $input);
        return $input;
    }

    public function display_settings_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings-page.php';
    }
    
    /**
     * Handle test event submission
     */
    public function handle_test_event() {
        // Verify nonce
        if (!isset($_POST['loyalteez_test_nonce']) || !wp_verify_nonce($_POST['loyalteez_test_nonce'], 'loyalteez_test_event')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action');
        }
        
        $test_email = isset($_POST['test_email']) ? sanitize_email($_POST['test_email']) : '';
        $test_event_name = isset($_POST['test_event_name']) ? sanitize_text_field($_POST['test_event_name']) : 'test_event';
        
        if (empty($test_email) || !is_email($test_email)) {
            $result = [
                'success' => false,
                'message' => 'Invalid email address provided.',
                'details' => []
            ];
        } else {
            // Load API class and send test event
            require_once plugin_dir_path(__FILE__) . 'class-api.php';
            $api = new Loyalteez_API();
            
            $result_data = $api->send_event($test_event_name, $test_email, [
                'test' => true,
                'source' => 'admin_test',
                'timestamp' => current_time('mysql', true)
            ]);
            
            if (is_wp_error($result_data)) {
                $error_data = $result_data->get_error_data();
                $error_message = $result_data->get_error_message();
                
                // Extract more details if available
                $details = [
                    'error_code' => $result_data->get_error_code(),
                    'error_message' => $error_message
                ];
                
                if (is_array($error_data)) {
                    $details = array_merge($details, $error_data);
                }
                
                $result = [
                    'success' => false,
                    'message' => 'Test event failed: ' . $error_message,
                    'details' => $details
                ];
            } else {
                $result = [
                    'success' => true,
                    'message' => 'Test event sent successfully! Check your Partner Portal to see if the event was received.',
                    'details' => [
                        'event_name' => $test_event_name,
                        'email' => $test_email,
                        'brand_id' => get_option('loyalteez_brand_id')
                    ]
                ];
            }
        }
        
        // Redirect back with result
        $redirect_url = add_query_arg([
            'page' => 'loyalteez-rewards',
            'settings-updated' => 'true',
            'test_result' => base64_encode(json_encode($result))
        ], admin_url('options-general.php'));
        
        wp_redirect($redirect_url);
        exit;
    }
}
