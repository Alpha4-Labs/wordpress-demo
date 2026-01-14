/**
 * Balance Widget JavaScript
 */

(function($) {
    'use strict';

    const { brandId, userIdentifier, apiUrl } = loyalteezBalanceData;

    async function loadBalance() {
        try {
            const response = await fetch(
                `${apiUrl}/loyalteez-api/user-balance?brandId=${encodeURIComponent(brandId)}&userEmail=${encodeURIComponent(userIdentifier)}`
            );
            const data = await response.json();
            $('#loyalteez-balance-amount').text((data.balance || 0).toLocaleString());
        } catch (error) {
            console.error('Error loading balance:', error);
            $('#loyalteez-balance-amount').text('Error');
        }
    }

    $(document).ready(function() {
        loadBalance();
    });
})(jQuery);
