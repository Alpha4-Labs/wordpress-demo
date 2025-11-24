<?php

class Loyalteez_Admin {

    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
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
        register_setting('loyalteez_options_group', 'loyalteez_brand_id');
        register_setting('loyalteez_options_group', 'loyalteez_reward_comments');
        register_setting('loyalteez_options_group', 'loyalteez_reward_signups');
        register_setting('loyalteez_options_group', 'loyalteez_reward_daily_visit');
        register_setting('loyalteez_options_group', 'loyalteez_debug_mode');
    }

    public function display_settings_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings-page.php';
    }
}

