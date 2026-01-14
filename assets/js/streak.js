/**
 * Streak Widget JavaScript
 */

(function($) {
    'use strict';

    const { brandId, userIdentifier, sharedServicesUrl, apiUrl, nonce } = loyalteezStreakData;

    async function loadStreak() {
        try {
            const encodedBrandId = encodeURIComponent(brandId);
            const encodedUser = encodeURIComponent(userIdentifier);
            const response = await fetch(
                `${sharedServicesUrl}/streak/status/${encodedBrandId}/${encodedUser}?streakType=daily`
            );
            const data = await response.json();
            
            let html = '';
            if (data.currentStreak !== undefined) {
                html = `
                    <div class="streak-info">
                        <div class="streak-days">${data.currentStreak}</div>
                        <div class="streak-label">Day Streak 🔥</div>
                        <div class="streak-multiplier">${data.multiplier || 1}x Multiplier</div>
                    </div>
                `;
                
                if (!data.checkedInToday) {
                    html += `<button class="check-in-btn" id="loyalteez-checkin-btn">☀️ Check In Today</button>`;
                } else {
                    html += `<div class="check-in-done">✅ Checked in! Come back tomorrow.</div>`;
                }
                
                if (data.nextMilestone) {
                    html += `
                        <div class="next-milestone">
                            ${data.daysToNextMilestone || 0} days to ${data.nextMilestone.days}-day bonus (+${data.nextMilestone.bonus} LTZ)
                        </div>
                    `;
                }
                
                if (data.unclaimedMilestones && data.unclaimedMilestones.length > 0) {
                    html += '<div class="milestones"><h4>🎁 Claim Your Milestones!</h4>';
                    data.unclaimedMilestones.forEach(milestone => {
                        html += `
                            <button class="milestone-btn" data-days="${milestone.days}">
                                Claim ${milestone.days}-day bonus (+${milestone.bonus} LTZ)
                            </button>
                        `;
                    });
                    html += '</div>';
                }
            } else {
                html = '<p>Start your streak today!</p><button class="check-in-btn" id="loyalteez-checkin-btn">☀️ Check In Today</button>';
            }
            
            $('#loyalteez-streak-display').html(html);
            
            // Attach event handlers
            $('#loyalteez-checkin-btn').on('click', handleCheckIn);
            $('.milestone-btn').on('click', function() {
                const days = $(this).data('days');
                handleClaimMilestone(days);
            });
        } catch (error) {
            console.error('Error loading streak:', error);
            $('#loyalteez-streak-display').html('<p>Error loading streak data.</p>');
        }
    }

    async function handleCheckIn() {
        const btn = $('#loyalteez-checkin-btn');
        btn.prop('disabled', true).text('Checking in...');
        
        try {
            const response = await fetch(`${apiUrl}/wp-json/loyalteez/v1/checkin`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce
                },
                body: JSON.stringify({
                    userIdentifier: userIdentifier
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(`✅ Checked in! Earned ${result.reward} LTZ`);
                loadStreak();
            } else {
                alert(result.message || 'Failed to check in');
            }
        } catch (error) {
            console.error('Error checking in:', error);
            alert('Error checking in. Please try again.');
        } finally {
            btn.prop('disabled', false);
        }
    }

    async function handleClaimMilestone(days) {
        try {
            const response = await fetch(`${apiUrl}/wp-json/loyalteez/v1/claim-milestone`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce
                },
                body: JSON.stringify({
                    milestoneDays: days
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(`🎉 Claimed ${result.bonusLtz} LTZ milestone bonus!`);
                loadStreak();
            } else {
                alert(result.message || 'Failed to claim milestone');
            }
        } catch (error) {
            console.error('Error claiming milestone:', error);
            alert('Error claiming milestone. Please try again.');
        }
    }

    $(document).ready(function() {
        loadStreak();
    });
})(jQuery);
