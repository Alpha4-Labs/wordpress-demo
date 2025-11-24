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
    
    <?php 
    // Show success message with link to test site
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true' && !isset($_GET['test_result'])) : 
    ?>
    <div class="notice notice-success is-dismissible" style="padding: 12px;">
        <p><strong>✅ Settings saved successfully!</strong></p>
        <p style="margin-top: 8px;">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="button button-primary" target="_blank">
                🌐 Visit Your Site to Test
            </a>
            <a href="<?php echo esc_url(home_url('/?p=1')); ?>" class="button" target="_blank" style="margin-left: 8px;">
                📝 Test Comment on "Hello World" Post
            </a>
        </p>
        <p class="description" style="margin-top: 8px;">
            <strong>Note:</strong> Events trigger automatically when actions occur (comments, signups, etc.). 
            Use the "Test API Connection" section below to manually test your configuration.
        </p>
    </div>
    <?php endif; ?>
    
    <?php
    // Show configuration status
    $brand_id = get_option('loyalteez_brand_id');
    $events = get_option('loyalteez_custom_events', []);
    $enabled_events = array_filter($events, function($e) { return !empty($e['enabled']); });
    
    if (empty($brand_id)) {
        echo '<div class="notice notice-warning is-dismissible" style="padding: 12px;">';
        echo '<p><strong>⚠️ Configuration Incomplete</strong></p>';
        echo '<p>Please enter your Brand ID above to enable rewards.</p>';
        echo '</div>';
    } elseif (empty($enabled_events)) {
        echo '<div class="notice notice-warning is-dismissible" style="padding: 12px;">';
        echo '<p><strong>⚠️ No Events Enabled</strong></p>';
        echo '<p>Add and enable at least one event above to start rewarding users.</p>';
        echo '</div>';
    } else {
        echo '<div class="notice notice-info is-dismissible" style="padding: 12px;">';
        echo '<p><strong>ℹ️ Active Configuration</strong></p>';
        echo '<p>Brand ID: <code>' . esc_html($brand_id) . '</code> | ';
        echo 'Active Events: <strong>' . count($enabled_events) . '</strong></p>';
        echo '</div>';
    }
    ?>

    <form action="options.php" method="post" id="loyalteez-settings-form">
        <?php
        settings_fields('loyalteez_options_group');
        do_settings_sections('loyalteez_options_group');
        ?>
        <!-- Hidden field to ensure events data is preserved -->
        <input type="hidden" name="loyalteez_events_submitted" value="1" />

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
    
    <!-- Test API Connection -->
    <div class="card" style="max-width: 800px; margin-top: 20px;">
        <h2 style="padding: 12px; border-bottom: 1px solid #ddd;">🧪 Test API Connection</h2>
        <div style="padding: 15px;">
            <p class="description">Test if your configuration is working by sending a test event to the Loyalteez API.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top: 15px;">
                <input type="hidden" name="action" value="loyalteez_test_event" />
                <?php wp_nonce_field('loyalteez_test_event', 'loyalteez_test_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="test_email">Test Email</label></th>
                        <td>
                            <input type="email" name="test_email" id="test_email" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" class="regular-text" required />
                            <p class="description">Email address to use for the test event.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="test_event_name">Event Name</label></th>
                        <td>
                            <input type="text" name="test_event_name" id="test_event_name" value="test_event" class="regular-text" required />
                            <p class="description">Event name to test (must exist in your Partner Portal).</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Send Test Event', 'secondary', 'test_event', false); ?>
            </form>
            
            <?php
            // Show test results if available
            if (isset($_GET['test_result'])) {
                $result = json_decode(base64_decode($_GET['test_result']), true);
                if ($result) {
                    $status_class = $result['success'] ? 'notice-success' : 'notice-error';
                    echo '<div class="notice ' . esc_attr($status_class) . ' is-dismissible" style="margin-top: 15px;">';
                    echo '<p><strong>' . ($result['success'] ? '✅ Success!' : '❌ Failed') . '</strong></p>';
                    echo '<p>' . esc_html($result['message']) . '</p>';
                    if (!empty($result['details'])) {
                        echo '<pre style="background: #f5f5f5; padding: 10px; overflow-x: auto;">' . esc_html(print_r($result['details'], true)) . '</pre>';
                    }
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    let eventIndex = <?php echo count($events); ?>;
    
        // Ensure events are saved when form is submitted
    $('#loyalteez-settings-form').on('submit', function(e) {
        // Collect all event data before form submission
        const events = [];
        $('.loyalteez-event-row').each(function() {
            const $row = $(this);
            const hook = $row.find('input[name*="[hook]"]').val();
            const eventName = $row.find('input[name*="[event_name]"]').val();
            const enabled = $row.find('input[name*="[enabled]"]').is(':checked') ? '1' : '0';
            
            if (hook && eventName) {
                events.push({
                    hook: hook.trim(),
                    event_name: eventName.trim(),
                    enabled: enabled
                });
            }
        });
        
        // Log for debugging
        console.log('[Loyalteez] Form submitting with events:', events);
        
        // Add JSON backup field in case WordPress strips array data
        if ($('#loyalteez_events_json').length === 0) {
            $('<input>').attr({
                type: 'hidden',
                id: 'loyalteez_events_json',
                name: 'loyalteez_events_json'
            }).appendTo('#loyalteez-settings-form');
        }
        $('#loyalteez_events_json').val(JSON.stringify(events));
    });
    
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
