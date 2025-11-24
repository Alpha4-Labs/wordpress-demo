<?php

class Loyalteez_Hooks {

    private $api;

    public function __construct() {
        require_once plugin_dir_path(__FILE__) . 'class-api.php';
        $this->api = new Loyalteez_API();
    }

    public function init() {
        // Comments
        if (get_option('loyalteez_reward_comments')) {
            add_action('comment_post', [$this, 'handle_comment'], 10, 2);
        }

        // User Registration
        if (get_option('loyalteez_reward_signups')) {
            add_action('user_register', [$this, 'handle_registration'], 10, 1);
        }

        // Daily Visit
        if (get_option('loyalteez_reward_daily_visit')) {
            add_action('init', [$this, 'handle_daily_visit']);
        }

        // AJAX for Share (Frontend)
        add_action('wp_ajax_loyalteez_share', [$this, 'handle_share']);
        add_action('wp_ajax_nopriv_loyalteez_share', [$this, 'handle_share']);

        // Enqueue Scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('loyalteez-public', plugin_dir_url(dirname(__FILE__)) . 'assets/js/loyalteez-public.js', ['jquery'], '1.0.0', true);
        wp_localize_script('loyalteez-public', 'loyalteez_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('loyalteez_share_nonce'),
            'user_email' => is_user_logged_in() ? wp_get_current_user()->user_email : ''
        ]);
    }

    /**
     * Handle new comment
     */
    public function handle_comment($comment_id, $comment_approved) {
        // Only reward approved comments. If strictly approved, check $comment_approved === 1.
        // For simplicity, we reward if it's not spam.
        if ($comment_approved === 'spam') return;

        $comment = get_comment($comment_id);
        $email = $comment->comment_author_email;

        if ($email) {
            $event_name = get_option('loyalteez_event_name_comments', 'post_comment');
            $this->api->send_event($event_name, $email, [
                'comment_id' => $comment_id,
                'post_id' => $comment->comment_post_ID
            ]);
        }
    }

    /**
     * Handle new user registration
     */
    public function handle_registration($user_id) {
        $user = get_userdata($user_id);
        if ($user) {
            $event_name = get_option('loyalteez_event_name_signups', 'user_registration');
            $this->api->send_event($event_name, $user->user_email, [
                'user_id' => $user_id,
                'username' => $user->user_login
            ]);
        }
    }

    /**
     * Handle daily visit for logged-in users
     */
    public function handle_daily_visit() {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $transient_key = 'loyalteez_daily_' . $user_id;

            if (get_transient($transient_key) === false) {
                $user = wp_get_current_user();
                $event_name = get_option('loyalteez_event_name_daily_visit', 'daily_visit');
                $result = $this->api->send_event($event_name, $user->user_email, [
                    'user_id' => $user_id
                ]);

                if (!is_wp_error($result)) {
                    // Set transient for 24 hours
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

        // Note: Share event name currently hardcoded as 'content_share' or could be added to settings
        $result = $this->api->send_event('content_share', $email, [
            'shared_url' => $url
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success(['message' => 'Reward sent!']);
        }
    }
}
