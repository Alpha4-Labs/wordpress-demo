<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 */

// Get configured events
$events = get_option('loyalteez_custom_events', []);
if (!is_array($events)) {
    $events = [];
}

// Common WordPress hooks for reference
$common_hooks = [
    'comment_post' => 'Comment Posted',
    'user_register' => 'User Registration',
    'init' => 'Page Load (init)',
    'wp_login' => 'User Login',
    'publish_post' => 'Post Published',
    'transition_post_status' => 'Post Status Changed',
    'woocommerce_order_status_completed' => 'WooCommerce Order Completed',
    'woocommerce_thankyou' => 'WooCommerce Thank You Page',
];
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form action="options.php" method="post" id="loyalteez-settings-form">
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
                    <p class="description" style="margin-bottom: 15px;">
                        Map WordPress actions to your Loyalteez Event Names. Add as many events as you need.
                        <strong>Event Names must match exactly</strong> what you've configured in your Partner Portal.
                    </p>
                    
                    <div id="loyalteez-events-container">
                        <?php if (empty($events)): ?>
                            <p class="description" style="color: #666; font-style: italic;">No events configured. Click "Add Event" below to get started.</p>
                        <?php else: ?>
                            <?php foreach ($events as $index => $event): ?>
                                <div class="loyalteez-event-row" data-index="<?php echo esc_attr($index); ?>" style="margin-bottom: 15px; border: 1px solid #ddd; padding: 15px; background: #fff; border-radius: 4px;">
                                    <div style="display: flex; gap: 10px; align-items: flex-start; flex-wrap: wrap;">
                                        <div style="flex: 1; min-width: 200px;">
                                            <label style="display: block; font-weight: 600; margin-bottom: 5px;">WordPress Hook</label>
                                            <input type="text" 
                                                   name="loyalteez_events[<?php echo esc_attr($index); ?>][hook]" 
                                                   value="<?php echo esc_attr($event['hook'] ?? ''); ?>" 
                                                   class="regular-text" 
                                                   placeholder="comment_post" 
                                                   required />
                                            <p class="description" style="margin-top: 5px;">
                                                WordPress action/hook name (e.g., <code>comment_post</code>, <code>user_register</code>)
                                            </p>
                                        </div>
                                        
                                        <div style="flex: 1; min-width: 200px;">
                                            <label style="display: block; font-weight: 600; margin-bottom: 5px;">Loyalteez Event Name</label>
                                            <input type="text" 
                                                   name="loyalteez_events[<?php echo esc_attr($index); ?>][event_name]" 
                                                   value="<?php echo esc_attr($event['event_name'] ?? ''); ?>" 
                                                   class="regular-text" 
                                                   placeholder="post_comment" 
                                                   required />
                                            <p class="description" style="margin-top: 5px;">
                                                Must match your Partner Portal event name exactly
                                            </p>
                                        </div>
                                        
                                        <div style="flex: 0 0 auto;">
                                            <label style="display: block; font-weight: 600; margin-bottom: 5px;">Enabled</label>
                                            <input type="checkbox" 
                                                   name="loyalteez_events[<?php echo esc_attr($index); ?>][enabled]" 
                                                   value="1" 
                                                   <?php checked(1, $event['enabled'] ?? 1); ?> />
                                        </div>
                                        
                                        <div style="flex: 0 0 auto; align-self: flex-end;">
                                            <button type="button" class="button button-secondary loyalteez-remove-event" style="color: #b32d2e;">
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" id="loyalteez-add-event" class="button button-secondary" style="margin-top: 10px;">
                        + Add Event
                    </button>
                    
                    <div style="margin-top: 20px; padding: 10px; background: #f0f0f1; border-left: 4px solid #2271b1;">
                        <strong>Common WordPress Hooks:</strong>
                        <ul style="margin: 10px 0 0 20px; columns: 2; column-gap: 20px;">
                            <?php foreach ($common_hooks as $hook => $label): ?>
                                <li><code><?php echo esc_html($hook); ?></code> - <?php echo esc_html($label); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <p style="margin-top: 10px; margin-bottom: 0;">
                            <a href="https://developer.wordpress.org/reference/hooks/" target="_blank">View all WordPress hooks →</a>
                        </p>
                    </div>
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

<script type="text/javascript">
jQuery(document).ready(function($) {
    let eventIndex = <?php echo count($events); ?>;
    
    // Add new event
    $('#loyalteez-add-event').on('click', function() {
        const eventRow = `
            <div class="loyalteez-event-row" data-index="${eventIndex}" style="margin-bottom: 15px; border: 1px solid #ddd; padding: 15px; background: #fff; border-radius: 4px;">
                <div style="display: flex; gap: 10px; align-items: flex-start; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">WordPress Hook</label>
                        <input type="text" 
                               name="loyalteez_events[${eventIndex}][hook]" 
                               class="regular-text" 
                               placeholder="comment_post" 
                               required />
                        <p class="description" style="margin-top: 5px;">
                            WordPress action/hook name (e.g., <code>comment_post</code>, <code>user_register</code>)
                        </p>
                    </div>
                    
                    <div style="flex: 1; min-width: 200px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">Loyalteez Event Name</label>
                        <input type="text" 
                               name="loyalteez_events[${eventIndex}][event_name]" 
                               class="regular-text" 
                               placeholder="post_comment" 
                               required />
                        <p class="description" style="margin-top: 5px;">
                            Must match your Partner Portal event name exactly
                        </p>
                    </div>
                    
                    <div style="flex: 0 0 auto;">
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">Enabled</label>
                        <input type="checkbox" 
                               name="loyalteez_events[${eventIndex}][enabled]" 
                               value="1" 
                               checked />
                    </div>
                    
                    <div style="flex: 0 0 auto; align-self: flex-end;">
                        <button type="button" class="button button-secondary loyalteez-remove-event" style="color: #b32d2e;">
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Remove "no events" message if present
        $('#loyalteez-events-container p.description').remove();
        
        $('#loyalteez-events-container').append(eventRow);
        eventIndex++;
    });
    
    // Remove event
    $(document).on('click', '.loyalteez-remove-event', function() {
        $(this).closest('.loyalteez-event-row').fadeOut(300, function() {
            $(this).remove();
            
            // Show "no events" message if container is empty
            if ($('#loyalteez-events-container').children().length === 0) {
                $('#loyalteez-events-container').html('<p class="description" style="color: #666; font-style: italic;">No events configured. Click "Add Event" below to get started.</p>');
            }
        });
    });
});
</script>
