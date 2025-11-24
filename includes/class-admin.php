<?php

class Loyalteez_Admin {

    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        // Handle custom events saving before WordPress processes the form
        add_action('admin_init', [$this, 'save_custom_events'], 5);
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
     * Save custom events before WordPress processes the form
     */
    public function save_custom_events() {
        if (!isset($_POST['option_page']) || $_POST['option_page'] !== 'loyalteez_options_group') {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_POST['loyalteez_events']) && is_array($_POST['loyalteez_events'])) {
            $events = $this->sanitize_custom_events($_POST['loyalteez_events']);
            update_option('loyalteez_custom_events', $events);
        } else {
            // If no events submitted, clear the option
            update_option('loyalteez_custom_events', []);
        }
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
}
