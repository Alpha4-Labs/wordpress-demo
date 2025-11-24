# Loyalteez WordPress Recipes

Common patterns for extending the Loyalteez WordPress Plugin.

## Recipe 1: Reward WooCommerce Purchases
Add this to your theme's `functions.php` or a custom plugin to reward users after a completed order.

```php
add_action('woocommerce_order_status_completed', 'reward_woo_purchase');

function reward_woo_purchase($order_id) {
    if (!class_exists('Loyalteez_API')) return;

    $order = wc_get_order($order_id);
    $email = $order->get_billing_email();
    $total = $order->get_total();

    $api = new Loyalteez_API();
    $api->send_event('purchase_completed', $email, [
        'order_id' => $order_id,
        'amount'   => $total,
        'currency' => $order->get_currency()
    ]);
}
```

## Recipe 2: Reward Gravity Forms Submission
Reward users for filling out a specific form (e.g., a survey).

```php
add_action('gform_after_submission_1', 'reward_survey_completion', 10, 2); // Form ID 1

function reward_survey_completion($entry, $form) {
    if (!class_exists('Loyalteez_API')) return;

    // Assuming Email is in field ID 3
    $email = rgar($entry, '3'); 

    if ($email) {
        $api = new Loyalteez_API();
        $api->send_event('survey_completed', $email, [
            'form_id' => $form['id'],
            'entry_id' => $entry['id']
        ]);
    }
}
```

## Recipe 3: Custom "Refer-a-Friend" Hook
Trigger a reward when a user refers someone (conceptual implementation).

```php
function handle_referral($referrer_email, $new_user_email) {
    if (!class_exists('Loyalteez_API')) return;

    $api = new Loyalteez_API();
    
    // Reward Referrer
    $api->send_event('referral_successful', $referrer_email, [
        'referred_user' => $new_user_email
    ]);

    // Reward Referee (Bonus)
    $api->send_event('referral_bonus', $new_user_email, [
        'referred_by' => $referrer_email
    ]);
}
```

## Recipe 4: Gamipress Integration
Trigger Loyalteez rewards when a user earns a Gamipress achievement.

```php
add_action('gamipress_award_achievement', 'reward_gp_achievement', 10, 3);

function reward_gp_achievement($user_id, $achievement_id, $trigger) {
    if (!class_exists('Loyalteez_API')) return;

    $user = get_userdata($user_id);
    
    $api = new Loyalteez_API();
    $api->send_event('achievement_unlocked', $user->user_email, [
        'achievement_id' => $achievement_id,
        'achievement_name' => get_the_title($achievement_id)
    ]);
}
```

