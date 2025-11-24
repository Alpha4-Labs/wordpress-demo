<?php

class Loyalteez_Hooks {

    private $api;

    public function __construct() {
        require_once plugin_dir_path(__FILE__) . 'class-api.php';
        $this->api = new Loyalteez_API();
    }

    public function init() {
        // Load custom events and register hooks dynamically
        $events = get_option('loyalteez_custom_events', []);
        
        if (is_array($events) && !empty($events)) {
            foreach ($events as $event) {
                if (empty($event['hook']) || empty($event['event_name']) || empty($event['enabled'])) {
                    continue;
                }
                
                $hook = sanitize_text_field($event['hook']);
                $event_name = sanitize_text_field($event['event_name']);
                
                // Register the hook dynamically
                // Use a closure to capture the event name
                add_action($hook, function() use ($event_name, $hook) {
                    $this->handle_dynamic_event($event_name, $hook, func_get_args());
                }, 10, 99); // Accept up to 99 arguments
            }
        }
        
        // Legacy support: Keep old individual event handlers for backward compatibility
        // These can be removed in a future version once users migrate to custom events
        if (get_option('loyalteez_reward_comments')) {
            $legacy_event_name = get_option('loyalteez_event_name_comments', 'post_comment');
            add_action('comment_post', function($comment_id, $comment_approved) use ($legacy_event_name) {
                $this->handle_comment($comment_id, $comment_approved, $legacy_event_name);
            }, 10, 2);
        }

        if (get_option('loyalteez_reward_signups')) {
            $legacy_event_name = get_option('loyalteez_event_name_signups', 'user_registration');
            add_action('user_register', function($user_id) use ($legacy_event_name) {
                $this->handle_registration($user_id, $legacy_event_name);
            }, 10, 1);
        }

        if (get_option('loyalteez_reward_daily_visit')) {
            $legacy_event_name = get_option('loyalteez_event_name_daily_visit', 'daily_visit');
            add_action('init', function() use ($legacy_event_name) {
                $this->handle_daily_visit($legacy_event_name);
            });
        }

        // AJAX for Share (Frontend) - Keep this as it's a special case
        add_action('wp_ajax_loyalteez_share', [$this, 'handle_share']);
        add_action('wp_ajax_nopriv_loyalteez_share', [$this, 'handle_share']);

        // Enqueue Scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Generic handler for dynamic events
     * Attempts to extract user email from various WordPress hook contexts
     */
    public function handle_dynamic_event($event_name, $hook, $args) {
        $email = null;
        $metadata = [
            'hook' => $hook,
            'timestamp' => current_time('mysql', true)
        ];
        
        // Try to extract email based on common hook patterns
        switch ($hook) {
            case 'comment_post':
                // Args: comment_id, comment_approved
                if (isset($args[0])) {
                    $comment = get_comment($args[0]);
                    if ($comment) {
                        $email = $comment->comment_author_email;
                        $metadata['comment_id'] = $args[0];
                        $metadata['post_id'] = $comment->comment_post_ID;
                    }
                }
                break;
                
            case 'user_register':
            case 'wp_login':
                // Args: user_id
                if (isset($args[0])) {
                    $user = get_userdata($args[0]);
                    if ($user) {
                        $email = $user->user_email;
                        $metadata['user_id'] = $args[0];
                        $metadata['username'] = $user->user_login;
                    }
                }
                break;
                
            case 'init':
                // Check if user is logged in
                if (is_user_logged_in()) {
                    $user = wp_get_current_user();
                    $email = $user->user_email;
                    $metadata['user_id'] = $user->ID;
                    
                    // For init hook, check if we've already processed today (daily visit logic)
                    if ($hook === 'init') {
                        $transient_key = 'loyalteez_' . md5($event_name . '_' . $user->ID);
                        if (get_transient($transient_key) !== false) {
                            return; // Already processed today
                        }
                        set_transient($transient_key, 1, 24 * HOUR_IN_SECONDS);
                    }
                }
                break;
                
            case 'publish_post':
            case 'transition_post_status':
                // Args: new_status, old_status, post
                if (isset($args[2]) && is_object($args[2])) {
                    $post = $args[2];
                    $author = get_userdata($post->post_author);
                    if ($author) {
                        $email = $author->user_email;
                        $metadata['post_id'] = $post->ID;
                        $metadata['post_title'] = $post->post_title;
                        if (isset($args[0])) {
                            $metadata['new_status'] = $args[0];
                        }
                        if (isset($args[1])) {
                            $metadata['old_status'] = $args[1];
                        }
                    }
                }
                break;
                
            case 'woocommerce_order_status_completed':
            case 'woocommerce_thankyou':
                // Args: order_id
                if (isset($args[0]) && function_exists('wc_get_order')) {
                    $order = wc_get_order($args[0]);
                    if ($order) {
                        $email = $order->get_billing_email();
                        $metadata['order_id'] = $args[0];
                        $metadata['order_total'] = $order->get_total();
                    }
                }
                break;
                
            default:
                // Generic fallback: try to get email from current user
                if (is_user_logged_in()) {
                    $user = wp_get_current_user();
                    $email = $user->user_email;
                    $metadata['user_id'] = $user->ID;
                }
                
                // Store all args for debugging
                $metadata['hook_args'] = $args;
                break;
        }
        
        // Send event if we have an email
        if ($email && is_email($email)) {
            $this->api->send_event($event_name, $email, $metadata);
        } else {
            // Log if debug mode is enabled
            if (get_option('loyalteez_debug_mode')) {
                error_log('[Loyalteez] Could not extract email for hook: ' . $hook . ' with args: ' . print_r($args, true));
            }
        }
    }

    public function enqueue_scripts() {
        // Only enqueue if the file exists
        $script_path = plugin_dir_path(dirname(__FILE__)) . 'assets/js/loyalteez-public.js';
        if (file_exists($script_path)) {
            wp_enqueue_script('loyalteez-public', plugin_dir_url(dirname(__FILE__)) . 'assets/js/loyalteez-public.js', ['jquery'], '1.0.0', true);
            wp_localize_script('loyalteez-public', 'loyalteez_vars', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('loyalteez_share_nonce'),
                'user_email' => is_user_logged_in() ? wp_get_current_user()->user_email : ''
            ]);
        }
    }

    /**
     * Handle new comment (legacy support)
     */
    public function handle_comment($comment_id, $comment_approved, $event_name = 'post_comment') {
        if ($comment_approved === 'spam') return;

        $comment = get_comment($comment_id);
        $email = $comment->comment_author_email;

        if ($email) {
            $this->api->send_event($event_name, $email, [
                'comment_id' => $comment_id,
                'post_id' => $comment->comment_post_ID
            ]);
        }
    }

    /**
     * Handle new user registration (legacy support)
     */
    public function handle_registration($user_id, $event_name = 'user_registration') {
        $user = get_userdata($user_id);
        if ($user) {
            $this->api->send_event($event_name, $user->user_email, [
                'user_id' => $user_id,
                'username' => $user->user_login
            ]);
        }
    }

    /**
     * Handle daily visit for logged-in users (legacy support)
     */
    public function handle_daily_visit($event_name = 'daily_visit') {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $transient_key = 'loyalteez_daily_' . $user_id;

            if (get_transient($transient_key) === false) {
                $user = wp_get_current_user();
                $result = $this->api->send_event($event_name, $user->user_email, [
                    'user_id' => $user_id
                ]);

                if (!is_wp_error($result)) {
                    set_transient($transient_key, 1, 24 * HOUR_IN_SECONDS);
                }
            }
        }
    }

    /**
     * Handle AJAX share event
     */
    public function handle_share() {
        check_ajax_referer('loyalteez_share_nonce', 'nonce');

        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        $email = '';

        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $email = $user->user_email;
        } else if (isset($_POST['email']) && is_email($_POST['email'])) {
            $email = sanitize_email($_POST['email']);
        }

        if (!$email) {
            wp_send_json_error(['message' => 'Email required']);
        }

        // Check if there's a custom event for content_share, otherwise use default
        $events = get_option('loyalteez_custom_events', []);
        $event_name = 'content_share';
        
        foreach ($events as $event) {
            if (isset($event['hook']) && $event['hook'] === 'loyalteez_share' && !empty($event['enabled'])) {
                $event_name = $event['event_name'];
                break;
            }
        }

        $result = $this->api->send_event($event_name, $email, [
            'shared_url' => $url
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success(['message' => 'Reward sent!']);
        }
    }
}
