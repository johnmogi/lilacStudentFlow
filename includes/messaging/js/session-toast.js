/**
 * Session Management Toast Extensions
 * Handles session timeouts and user activity monitoring
 */

(function($) {
    'use strict';

    // Default configuration
    const DEFAULTS = {
        // Session timeout in milliseconds (30 minutes)
        sessionTimeout: 30 * 60 * 1000,
        // Show warning this many milliseconds before timeout (5 minutes)
        warningBeforeTimeout: 5 * 60 * 1000,
        // Check for activity every 30 seconds
        activityCheckInterval: 30 * 1000,
        // Time to show the warning toast
        warningToastDuration: 0, // 0 means don't auto-dismiss
        // Callback when session is about to expire
        onSessionAboutToExpire: null,
        // Callback when session has expired
        onSessionExpired: null,
        // Callback when session is extended
        onSessionExtended: null
    };

    // Keep track of the last activity timestamp
    let lastActivity = Date.now();
    let warningShown = false;
    let warningToast = null;
    let sessionTimer = null;
    let activityCheckInterval = null;

    // Track user activity
    const trackActivity = function() {
        lastActivity = Date.now();
        if (warningShown) {
            hideWarning();
            if (typeof window.LilacToast.config.session.onSessionExtended === 'function') {
                window.LilacToast.config.session.onSessionExtended();
            }
        }
    };

    // Show warning toast
    const showWarning = function() {
        if (warningShown) return;
        
        const timeLeft = Math.ceil((DEFAULTS.sessionTimeout - (Date.now() - lastActivity)) / 1000 / 60);
        
        warningToast = window.LilacShowToast({
            title: 'Session About to Expire',
            message: `Your session will expire in ${timeLeft} minutes. Continue working?`,
            type: 'warning',
            duration: DEFAULTS.warningToastDuration,
            closable: true,
            buttons: [
                {
                    text: 'Stay Logged In',
                    class: 'button button-primary',
                    click: function() {
                        trackActivity();
                        return true; // Close the toast
                    }
                },
                {
                    text: 'Log Out',
                    class: 'button button-link',
                    click: function() {
                        if (typeof window.LilacToast.config.session.onSessionExpired === 'function') {
                            window.LilacToast.config.session.onSessionExpired();
                        }
                        return true; // Close the toast
                    }
                }
            ]
        });
        
        warningShown = true;
    };

    // Hide warning toast
    const hideWarning = function() {
        if (warningToast) {
            warningToast.toast('hide');
            warningToast = null;
        }
        warningShown = false;
    };

    // Check session status
    const checkSession = function() {
        const timeSinceLastActivity = Date.now() - lastActivity;
        const timeUntilTimeout = DEFAULTS.sessionTimeout - timeSinceLastActivity;
        
        // If we're within the warning period, show the warning
        if (timeUntilTimeout > 0 && timeUntilTimeout <= DEFAULTS.warningBeforeTimeout && !warningShown) {
            showWarning();
        }
        // If session has expired
        else if (timeUntilTimeout <= 0) {
            clearInterval(activityCheckInterval);
            if (typeof window.LilacToast.config.session.onSessionExpired === 'function') {
                window.LilacToast.config.session.onSessionExpired();
            }
        }
    };

    // Initialize session management
    const init = function(options) {
        // Extend defaults with user options
        window.LilacToast.config = window.LilacToast.config || {};
        window.LilacToast.config.session = $.extend({}, DEFAULTS, options);
        
        // Set up activity tracking
        $(document).on('mousemove keydown mousedown touchstart', trackActivity);
        
        // Start checking session status
        activityCheckInterval = setInterval(checkSession, DEFAULTS.activityCheckInterval);
        
        // Initial check
        checkSession();
        
        console.log('Session management initialized');
    };
    
    // Extend LilacToast with session management
    window.LilacToast = window.LilacToast || {};
    window.LilacToast.session = {
        init: init,
        trackActivity: trackActivity,
        showWarning: showWarning,
        hideWarning: hideWarning
    };
    
})(jQuery);
