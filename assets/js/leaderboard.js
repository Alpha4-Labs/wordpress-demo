/**
 * Leaderboard Widget JavaScript
 */

(function($) {
    'use strict';

    const { brandId, sharedServicesUrl, metric, period, limit } = loyalteezLeaderboardData;

    async function loadLeaderboard() {
        const currentMetric = $('#loyalteez-leaderboard-metric').val() || metric;
        const currentPeriod = $('#loyalteez-leaderboard-period').val() || period;
        
        try {
            const encodedBrandId = encodeURIComponent(brandId);
            const response = await fetch(
                `${sharedServicesUrl}/leaderboard/${encodedBrandId}?metric=${currentMetric}&period=${currentPeriod}&platform=wordpress&limit=${limit}`
            );
            const data = await response.json();
            
            if (data.success && data.leaderboard) {
                let html = '<ol class="leaderboard-list">';
                data.leaderboard.forEach(entry => {
                    const medal = entry.rank === 1 ? '🥇' : entry.rank === 2 ? '🥈' : entry.rank === 3 ? '🥉' : '';
                    html += `
                        <li>
                            <span class="rank">${medal || entry.rank}.</span>
                            <span class="name">${entry.displayName || entry.username}</span>
                            <span class="value">${entry.formattedValue || entry.value}</span>
                        </li>
                    `;
                });
                html += '</ol>';
                $('#loyalteez-leaderboard-list').html(html);
            } else {
                $('#loyalteez-leaderboard-list').html('<p>No leaderboard data available.</p>');
            }
        } catch (error) {
            console.error('Error loading leaderboard:', error);
            $('#loyalteez-leaderboard-list').html('<p>Error loading leaderboard.</p>');
        }
    }

    $(document).ready(function() {
        loadLeaderboard();
        
        $('#loyalteez-leaderboard-metric, #loyalteez-leaderboard-period').on('change', loadLeaderboard);
    });
})(jQuery);
