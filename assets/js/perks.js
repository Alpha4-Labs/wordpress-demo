/**
 * Perks Widget JavaScript
 */

(function($) {
    'use strict';

    const { brandId, userIdentifier, sharedServicesUrl, apiUrl, nonce, category } = loyalteezPerksData;

    async function loadPerks() {
        try {
            const encodedBrandId = encodeURIComponent(brandId);
            const url = new URL(`${sharedServicesUrl}/perks/${encodedBrandId}`);
            if (category !== 'all') {
                url.searchParams.set('category', category);
            }
            
            const response = await fetch(url.toString());
            const data = await response.json();
            
            if (data.success && data.perks && data.perks.length > 0) {
                // Get user balance
                const balanceResponse = await fetch(
                    `${apiUrl}/loyalteez-api/user-balance?brandId=${encodeURIComponent(brandId)}&userEmail=${encodeURIComponent(userIdentifier)}`
                );
                const balanceData = await balanceResponse.json();
                const balance = balanceData.balance || 0;
                
                let html = '<div class="perks-grid">';
                data.perks.forEach(perk => {
                    const canAfford = balance >= perk.cost;
                    html += `
                        <div class="perk-card">
                            <h4>${perk.name}</h4>
                            ${perk.description ? `<p>${perk.description}</p>` : ''}
                            <div class="perk-cost">${perk.cost} LTZ</div>
                            ${perk.available > 0 ? `<div class="perk-available">${perk.available} available</div>` : ''}
                            ${canAfford && perk.available > 0 ? `
                                <button class="redeem-btn" data-perk-id="${perk.id}">Redeem</button>
                            ` : !canAfford ? `
                                <div class="insufficient-balance">Need ${perk.cost - balance} more LTZ</div>
                            ` : `
                                <div class="out-of-stock">Out of stock</div>
                            `}
                        </div>
                    `;
                });
                html += '</div>';
                $('#loyalteez-perks-list').html(html);
                
                // Attach redeem handlers
                $('.redeem-btn').on('click', function() {
                    const perkId = $(this).data('perk-id');
                    handleRedeem(perkId);
                });
            } else {
                $('#loyalteez-perks-list').html('<p>No perks available at this time.</p>');
            }
        } catch (error) {
            console.error('Error loading perks:', error);
            $('#loyalteez-perks-list').html('<p>Error loading perks.</p>');
        }
    }

    async function handleRedeem(perkId) {
        if (!confirm('Are you sure you want to redeem this perk?')) {
            return;
        }
        
        const btn = $(`.redeem-btn[data-perk-id="${perkId}"]`);
        btn.prop('disabled', true).text('Redeeming...');
        
        try {
            const response = await fetch(`${apiUrl}/wp-json/loyalteez/v1/redeem`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce
                },
                body: JSON.stringify({
                    perkId: perkId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(`✅ Perk redeemed! Confirmation code: ${result.confirmationCode || 'N/A'}`);
                loadPerks();
            } else {
                alert(result.message || 'Failed to redeem perk');
            }
        } catch (error) {
            console.error('Error redeeming perk:', error);
            alert('Error redeeming perk. Please try again.');
        } finally {
            btn.prop('disabled', false).text('Redeem');
        }
    }

    $(document).ready(function() {
        loadPerks();
    });
})(jQuery);
