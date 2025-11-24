/**
 * Loyalteez WordPress Plugin - Frontend JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle share button clicks
        $(document).on('click', '.loyalteez-share', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const url = $button.data('url') || window.location.href;
            const userEmail = loyalteez_vars.user_email || '';
            
            // If user is not logged in, prompt for email
            if (!userEmail) {
                const email = prompt('Please enter your email address to receive rewards:');
                if (!email || !isValidEmail(email)) {
                    alert('Please enter a valid email address.');
                    return;
                }
                sendShareEvent(url, email);
            } else {
                sendShareEvent(url, userEmail);
            }
        });
        
        /**
         * Send share event via AJAX
         */
        function sendShareEvent(url, email) {
            $.ajax({
                url: loyalteez_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'loyalteez_share',
                    nonce: loyalteez_vars.nonce,
                    url: url,
                    email: email
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message || 'Reward sent!');
                    } else {
                        console.error('Loyalteez Share Error:', response.data);
                        alert(response.data.message || 'Failed to send reward. Please try again.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Loyalteez AJAX Error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        error: error,
                        responseText: xhr.responseText
                    });
                    
                    // Check for CORS errors
                    if (xhr.status === 0 || error === 'NetworkError') {
                        alert('Network error. Please check your browser console for CORS issues.');
                    } else {
                        alert('Failed to send reward. Please try again later.');
                    }
                }
            });
        }
        
        /**
         * Simple email validation
         */
        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
    });
})(jQuery);

