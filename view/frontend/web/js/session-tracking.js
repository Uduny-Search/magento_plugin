/**
 * Uduny Session Tracking JavaScript
 *
 * Loaded via text/x-magento-init from tracking.phtml when tracking is active.
 * Also supports direct RequireJS usage:
 *   require(['UnusualDope_Uduny/js/session-tracking'], function(tracker) {
 *       var token = tracker.getToken();
 *   });
 */
define([
    'jquery',
    'mage/url'
], function ($, urlBuilder) {
    'use strict';

    var STORAGE_KEY = 'uduny_session_token';

    var sessionTracker = {
        initialized: false,

        /**
         * Initialize session tracking
         */
        init: function (config) {
            this.config = config || {};
            if (this.config.enabled === false) {
                return;
            }
            if (this.initialized && !this.config.forceRefresh) {
                return;
            }
            this.initialized = true;
            this.trackEndpoint = this.config.trackUrl || urlBuilder.build('uduny/track/session');

            var existingToken = this.getToken();
            if (existingToken && !this.config.forceRefresh) {
                return;
            }

            this.registerSession();
        },

        /**
         * Register session with Magento backend
         */
        registerSession: function () {
            var self = this;

            $.ajax({
                url: this.trackEndpoint,
                type: 'POST',
                dataType: 'json',
                data: {},
                showLoader: false,

                success: function (response) {
                    if (response && response.success && response.token) {
                        self.setToken(response.token);

                        $(document).trigger('uduny:session:ready', {
                            token: response.token,
                            isLoggedIn: response.is_logged_in
                        });
                    }
                },

                error: function (xhr, status, error) {
                    if (window.console && console.error) {
                        console.error('[Uduny] Session registration error:', error);
                    }
                }
            });
        },

        /**
         * Get stored Uduny token
         *
         * @returns {string|null}
         */
        getToken: function () {
            try {
                return sessionStorage.getItem(STORAGE_KEY);
            } catch (e) {
                return null;
            }
        },

        /**
         * Set Uduny token in storage
         *
         * @param {string} token
         */
        setToken: function (token) {
            try {
                sessionStorage.setItem(STORAGE_KEY, token);
            } catch (e) {
                // Ignore storage errors
            }
        },

        /**
         * Clear stored token
         */
        clearToken: function () {
            try {
                sessionStorage.removeItem(STORAGE_KEY);
            } catch (e) {
                // Ignore
            }
        },

        /**
         * Check if session is tracked
         *
         * @returns {boolean}
         */
        isTracked: function () {
            return !!this.getToken();
        }
    };

    /**
     * Callable initializer for Magento text/x-magento-init
     */
    function initUdunyTracking(config) {
        sessionTracker.init(config);
        return sessionTracker;
    }

    initUdunyTracking.init = sessionTracker.init.bind(sessionTracker);
    initUdunyTracking.registerSession = sessionTracker.registerSession.bind(sessionTracker);
    initUdunyTracking.getToken = sessionTracker.getToken.bind(sessionTracker);
    initUdunyTracking.setToken = sessionTracker.setToken.bind(sessionTracker);
    initUdunyTracking.clearToken = sessionTracker.clearToken.bind(sessionTracker);
    initUdunyTracking.isTracked = sessionTracker.isTracked.bind(sessionTracker);

    return initUdunyTracking;
});
