/**
 * Session Timeout Handler
 * Warns users before their session expires and redirects to login page when timeout occurs
 */
(function() {
    'use strict';
    
    // Configuration
    const config = {
        // Warning time in milliseconds (5 minutes before timeout)
        warningTime: 5 * 60 * 1000,
        // Session duration in milliseconds (30 minutes total)
        sessionDuration: 30 * 60 * 1000,
        // URLs
        redirectUrl: 'login.php',
        // Warning dialog settings
        dialog: {
            title: 'Session Expiration Warning',
            message: 'Your session is about to expire due to inactivity.',
            buttonText: 'Continue Session'
        },
        // Enable debug logs
        debug: false
    };

    // Variables
    let warningTimer = null;
    let redirectTimer = null;
    
    // Log function with debug mode check
    function log(message, data = null) {
        if (config.debug && console) {
            if (data) {
                console.log(`[Session Timeout] ${message}`, data);
            } else {
                console.log(`[Session Timeout] ${message}`);
            }
        }
    }

    // Start the session timer when page loads
    function startSessionTimer() {
        log('Starting session timer');
        
        // Clear any existing timers
        clearTimeout(warningTimer);
        clearTimeout(redirectTimer);
        
        // Set timer for showing warning
        warningTimer = setTimeout(() => {
            showWarning();
        }, config.sessionDuration - config.warningTime);
        
        // Set timer for redirect
        redirectTimer = setTimeout(() => {
            redirectToLogin();
        }, config.sessionDuration);
    }
    
    // Show warning dialog
    function showWarning() {
        log('Showing warning dialog');
        
        // Check if Bootstrap is available
        if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
            // Create or use existing modal
            let $modal = $('#session-timeout-modal');
            
            if ($modal.length === 0) {
                // Create modal if it doesn't exist
                const modalHtml = `
                    <div class="modal fade" id="session-timeout-modal" tabindex="-1" role="dialog" aria-labelledby="sessionTimeoutTitle" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header bg-warning text-white">
                                    <h5 class="modal-title" id="sessionTimeoutTitle">${config.dialog.title}</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <p>${config.dialog.message}</p>
                                    <p>You will be redirected to the login page in <span id="session-time-remaining">5:00</span>.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" id="session-extend-button">${config.dialog.buttonText}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                $('body').append(modalHtml);
                $modal = $('#session-timeout-modal');
                
                // Handle button click
                $('#session-extend-button').on('click', function() {
                    extendSession();
                });
            }
            
            // Start countdown timer
            let timeLeft = Math.floor(config.warningTime / 1000);
            const $timeDisplay = $('#session-time-remaining');
            
            const countdownInterval = setInterval(() => {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                $timeDisplay.text(`${minutes}:${seconds < 10 ? '0' + seconds : seconds}`);
                
                if (--timeLeft < 0) {
                    clearInterval(countdownInterval);
                }
            }, 1000);
            
            // Show modal
            $modal.modal('show');
            
            // When modal is hidden, restart session if it was dismissed without clicking button
            $modal.on('hidden.bs.modal', function() {
                clearInterval(countdownInterval);
                // Only restart if we're not in the middle of redirecting
                if (redirectTimer) {
                    log('Warning dismissed, extending session');
                    extendSession();
                }
            });
        } else {
            // Fallback for when Bootstrap is not available
            if (confirm(`${config.dialog.title}\n\n${config.dialog.message}\n\nClick OK to continue your session.`)) {
                extendSession();
            }
        }
    }
    
    // Extend the session
    function extendSession() {
        log('Extending session');
        
        // Close modal if it exists
        if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
            $('#session-timeout-modal').modal('hide');
        }
        
        // Reset timers
        startSessionTimer();
        
        // Here you would typically make an AJAX call to extend the session on the server
        // For example:
        /*
        $.ajax({
            url: 'extend-session.php',
            method: 'POST',
            success: function(response) {
                log('Session extended on server');
            },
            error: function(xhr, status, error) {
                log('Error extending session on server', error);
            }
        });
        */
    }
    
    // Redirect to login page
    function redirectToLogin() {
        log('Session expired, redirecting to login page');
        
        // Clear timers
        clearTimeout(warningTimer);
        clearTimeout(redirectTimer);
        warningTimer = null;
        redirectTimer = null;
        
        // Close modal if it's open
        if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
            $('#session-timeout-modal').modal('hide');
        }
        
        // Redirect
        window.location.href = config.redirectUrl;
    }
    
    // Reset timer on user activity
    function resetOnActivity() {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'];
        
        events.forEach(event => {
            document.addEventListener(event, function() {
                // Don't extend if we're in warning period to avoid confusion
                if (warningTimer !== null) {
                    log('Activity detected, extending session');
                    extendSession();
                }
            });
        });
    }
    
    // Initialize
    function init() {
        log('Initializing session timeout handler');
        startSessionTimer();
        resetOnActivity();
    }
    
    // Start when DOM is ready
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(init, 1);
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})(); 