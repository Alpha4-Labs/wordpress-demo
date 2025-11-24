<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 */
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form action="options.php" method="post">
        <?php
        settings_fields('loyalteez_options_group');
        do_settings_sections('loyalteez_options_group');
        ?>

        <table class="form-table">
            <tr valign="top">
                <th scope="row">Brand ID</th>
                <td>
                    <input type="text" name="loyalteez_brand_id" value="<?php echo esc_attr(get_option('loyalteez_brand_id')); ?>" class="regular-text" />
                    <p class="description">Your Loyalteez Brand ID (Wallet Address). Get this from the <a href="https://partner.loyalteez.app" target="_blank">Partner Portal</a>.</p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Reward Events</th>
                <td>
                    <p class="description" style="margin-bottom: 15px;">Enable events and map them to the exact <strong>Event Name</strong> defined in your Partner Portal.</p>
                    
                    <fieldset style="margin-bottom: 15px; border: 1px solid #ddd; padding: 15px; background: #fff;">
                        <legend style="font-weight: 600;">Comments</legend>
                        <label for="loyalteez_reward_comments">
                            <input type="checkbox" name="loyalteez_reward_comments" id="loyalteez_reward_comments" value="1" <?php checked(1, get_option('loyalteez_reward_comments'), true); ?> />
                            Enable Comment Rewards
                        </label>
                        <br/>
                        <label for="loyalteez_event_name_comments" style="display: inline-block; margin-top: 8px;">Event Name:</label>
                        <input type="text" name="loyalteez_event_name_comments" id="loyalteez_event_name_comments" value="<?php echo esc_attr(get_option('loyalteez_event_name_comments', 'post_comment')); ?>" class="regular-text" placeholder="post_comment" />
                        <p class="description">Default: <code>post_comment</code></p>
                    </fieldset>

                    <fieldset style="margin-bottom: 15px; border: 1px solid #ddd; padding: 15px; background: #fff;">
                        <legend style="font-weight: 600;">User Registration</legend>
                        <label for="loyalteez_reward_signups">
                            <input type="checkbox" name="loyalteez_reward_signups" id="loyalteez_reward_signups" value="1" <?php checked(1, get_option('loyalteez_reward_signups'), true); ?> />
                            Enable Signup Rewards
                        </label>
                        <br/>
                        <label for="loyalteez_event_name_signups" style="display: inline-block; margin-top: 8px;">Event Name:</label>
                        <input type="text" name="loyalteez_event_name_signups" id="loyalteez_event_name_signups" value="<?php echo esc_attr(get_option('loyalteez_event_name_signups', 'user_registration')); ?>" class="regular-text" placeholder="user_registration" />
                        <p class="description">Default: <code>user_registration</code></p>
                    </fieldset>

                    <fieldset style="margin-bottom: 15px; border: 1px solid #ddd; padding: 15px; background: #fff;">
                        <legend style="font-weight: 600;">Daily Visit</legend>
                        <label for="loyalteez_reward_daily_visit">
                            <input type="checkbox" name="loyalteez_reward_daily_visit" id="loyalteez_reward_daily_visit" value="1" <?php checked(1, get_option('loyalteez_reward_daily_visit'), true); ?> />
                            Enable Daily Visit Rewards
                        </label>
                        <br/>
                        <label for="loyalteez_event_name_daily_visit" style="display: inline-block; margin-top: 8px;">Event Name:</label>
                        <input type="text" name="loyalteez_event_name_daily_visit" id="loyalteez_event_name_daily_visit" value="<?php echo esc_attr(get_option('loyalteez_event_name_daily_visit', 'daily_visit')); ?>" class="regular-text" placeholder="daily_visit" />
                        <p class="description">Default: <code>daily_visit</code></p>
                    </fieldset>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Debug Mode</th>
                <td>
                    <label for="loyalteez_debug_mode">
                        <input type="checkbox" name="loyalteez_debug_mode" id="loyalteez_debug_mode" value="1" <?php checked(1, get_option('loyalteez_debug_mode'), true); ?> />
                        Enable Debug Logging
                    </label>
                    <p class="description">Logs API errors to your WordPress <code>debug.log</code> file.</p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
