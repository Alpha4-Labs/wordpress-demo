/**
 * Loyalteez Dashboard JavaScript
 * 
 * Handles dashboard interactions: tabs, data loading, check-ins, redemptions
 */

(function($) {
    'use strict';

    const { brandId, userIdentifier, sharedServicesUrl, apiUrl } = loyalteezData;

    // Tab switching
    $('.tab-button').on('click', function() {
        const tab = $(this).data('tab');
        
        // Update buttons
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        
        // Update content
        $('.tab-content').removeClass('active');
        $(`#tab-${tab}`).addClass('active');
        
        // Load data for active tab
        loadTabData(tab);
    });

    // Load data for a specific tab
    function loadTabData(tab) {
        switch(tab) {
            case 'balance':
                loadBalance();
                break;
            case 'streak':
                loadStreak();
                break;
            case 'leaderboard':
                loadLeaderboard();
                break;
            case 'perks':
                loadPerks();
                break;
            case 'achievements':
                loadAchievements();
                break;
        }
    }

    // Load balance
    async function loadBalance() {
        try {
            const response = await fetch(
                `${apiUrl}/loyalteez-api/user-balance?brandId=${encodeURIComponent(brandId)}&userEmail=${encodeURIComponent(userIdentifier)}`
            );
            const data = await response.json();
            $('#balance-amount').text((data.balance || 0).toLocaleString());
        } catch (error) {
            console.error('Error loading balance:', error);
            $('#balance-amount').text('Error');
        }
    }

    // Load streak
    async function loadStreak() {
        try {
            const encodedBrandId = encodeURIComponent(brandId);
            const encodedUser = encodeURIComponent(userIdentifier);
            const response = await fetch(
                `${sharedServicesUrl}/streak/status/${encodedBrandId}/${encodedUser}?streakType=daily`
            );
            const data = await response.json();
            
            if (data.currentStreak !== undefined) {
                let html = `
                    <div class="streak-info">
                        <div class="streak-days">${data.currentStreak}</div>
                        <div class="streak-label">Day Streak</div>
                        <div class="streak-multiplier">${data.multiplier}x Multiplier</div>
                    </div>
                `;
                
                if (!data.checkedInToday) {
                    html += `<button class="check-in-btn" id="checkin-btn">☀️ Check In Today</button>`;
                } else {
                    html += `<div class="check-in-done">✅ Checked in! Come back tomorrow.</div>`;
                }
                
                if (data.nextMilestone) {
                    html += `
                        <div class="next-milestone">
                            ${data.daysToNextMilestone} days to ${data.nextMilestone.days}-day bonus (+${data.nextMilestone.bonus} LTZ)
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
                
                $('#streak-display').html(html);
                
                // Attach event handlers
                $('#checkin-btn').on('click', handleCheckIn);
                $('.milestone-btn').on('click', function() {
                    const days = $(this).data('days');
                    handleClaimMilestone(days);
                });
            } else {
                $('#streak-display').html('<p>Start your streak today!</p><button class="check-in-btn" id="checkin-btn">☀️ Check In Today</button>');
                $('#checkin-btn').on('click', handleCheckIn);
            }
        } catch (error) {
            console.error('Error loading streak:', error);
            $('#streak-display').html('<p>Error loading streak data.</p>');
        }
    }

    // Handle daily check-in
    async function handleCheckIn() {
        const btn = $('#checkin-btn');
        btn.prop('disabled', true).text('Checking in...');
        
        try {
            const response = await fetch(`${apiUrl}/wp-json/loyalteez/v1/checkin`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': loyalteezData.nonce || ''
                },
                body: JSON.stringify({
                    userIdentifier: userIdentifier
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(`✅ Checked in! Earned ${result.reward} LTZ`);
                // Reload data
                loadBalance();
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

    // Handle milestone claim
    async function handleClaimMilestone(days) {
        try {
            const response = await fetch(`${apiUrl}/wp-json/loyalteez/v1/claim-milestone`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': loyalteezData.nonce || ''
                },
                body: JSON.stringify({
                    milestoneDays: days
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(`🎉 Claimed ${result.bonusLtz} LTZ milestone bonus!`);
                loadBalance();
                loadStreak();
            } else {
                alert(result.message || 'Failed to claim milestone');
            }
        } catch (error) {
            console.error('Error claiming milestone:', error);
            alert('Error claiming milestone. Please try again.');
        }
    }

    // Load leaderboard
    async function loadLeaderboard() {
        const metric = $('#leaderboard-metric').val();
        const period = $('#leaderboard-period').val();
        
        try {
            const encodedBrandId = encodeURIComponent(brandId);
            const response = await fetch(
                `${sharedServicesUrl}/leaderboard/${encodedBrandId}?metric=${metric}&period=${period}&platform=wordpress&limit=10`
            );
            const data = await response.json();
            
            if (data.success && data.leaderboard) {
                let html = '<ol class="leaderboard-list">';
                data.leaderboard.forEach(entry => {
                    const medal = entry.rank === 1 ? '🥇' : entry.rank === 2 ? '🥈' : entry.rank === 3 ? '🥉' : '';
                    html += `
                        <li>
                            <span class="rank">${medal || entry.rank}.</span>
                            <span class="name">${entry.displayName}</span>
                            <span class="value">${entry.formattedValue}</span>
                        </li>
                    `;
                });
                html += '</ol>';
                $('#leaderboard-list').html(html);
            } else {
                $('#leaderboard-list').html('<p>No leaderboard data available.</p>');
            }
        } catch (error) {
            console.error('Error loading leaderboard:', error);
            $('#leaderboard-list').html('<p>Error loading leaderboard.</p>');
        }
    }

    // Leaderboard filter changes
    $('#leaderboard-metric, #leaderboard-period').on('change', loadLeaderboard);

    // Load perks
    async function loadPerks() {
        try {
            const encodedBrandId = encodeURIComponent(brandId);
            const response = await fetch(
                `${sharedServicesUrl}/perks/${encodedBrandId}?category=all`
            );
            const data = await response.json();
            
            if (data.success && data.perks && data.perks.length > 0) {
                let html = '<div class="perks-grid">';
                data.perks.forEach(perk => {
                    html += `
                        <div class="perk-card">
                            <h4>${perk.name}</h4>
                            <p>${perk.description}</p>
                            <div class="perk-cost">${perk.cost} LTZ</div>
                            <div class="perk-available">${perk.available} available</div>
                            <button class="redeem-btn" data-perk-id="${perk.id}" ${perk.available === 0 ? 'disabled' : ''}>
                                Redeem
                            </button>
                        </div>
                    `;
                });
                html += '</div>';
                $('#perks-list').html(html);
                
                // Attach redeem handlers
                $('.redeem-btn').on('click', function() {
                    const perkId = $(this).data('perk-id');
                    handleRedeem(perkId);
                });
            } else {
                $('#perks-list').html('<p>No perks available at this time.</p>');
            }
        } catch (error) {
            console.error('Error loading perks:', error);
            $('#perks-list').html('<p>Error loading perks.</p>');
        }
    }

    // Handle perk redemption
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
                    'X-WP-Nonce': loyalteezData.nonce || ''
                },
                body: JSON.stringify({
                    perkId: perkId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(`✅ Perk redeemed! Confirmation code: ${result.confirmationCode}`);
                loadBalance();
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

    // Load achievements
    async function loadAchievements() {
        try {
            const encodedBrandId = encodeURIComponent(brandId);
            const encodedUser = encodeURIComponent(userIdentifier);
            const response = await fetch(
                `${sharedServicesUrl}/achievements/${encodedBrandId}/${encodedUser}`
            );
            const data = await response.json();
            
            if (data.success && data.achievements) {
                let html = '<div class="achievements-list">';
                data.achievements.forEach(achievement => {
                    const status = achievement.isUnlocked ? '✅' : '⏳';
                    html += `
                        <div class="achievement-item ${achievement.isUnlocked ? 'unlocked' : ''}">
                            <span class="status">${status}</span>
                            <span class="type">${achievement.achievementType}</span>
                            <span class="progress">${achievement.currentProgress}</span>
                        </div>
                    `;
                });
                html += '</div>';
                $('#achievements-list').html(html);
            } else {
                $('#achievements-list').html('<p>No achievements available.</p>');
            }
        } catch (error) {
            console.error('Error loading achievements:', error);
            $('#achievements-list').html('<p>Error loading achievements.</p>');
        }
    }

    // Load initial data
    $(document).ready(function() {
        loadTabData('balance');
    });

})(jQuery);
