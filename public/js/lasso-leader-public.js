/**
 * Lasso Leader Frontend Tracking Script (Corrected Version with Enhanced Debugging)
 *
 * This script handles the logic for injecting the Lasso CRM tracking script
 * based on global, page-specific, and exclusion settings.
 */
(function() {
    'use strict';

    // --- Main execution function ---
    function initLassoTracking() {
        const settings = window.lassoLeaderTrackingData || {};

        if (!settings.enableTracking) {
            console.log("Lasso Leader: Tracking is globally disabled in the plugin settings.");
            return;
        }
        
        if (!settings.currentPageId || settings.currentPageId === '0') {
            console.log('Lasso Leader: Not on a singular page, tracking not initialized.');
            return;
        }

        const tracker = new LassoTracker(settings);
        const result = tracker.getTrackingDecision(); // Get a result object

        // Use the result to show the specific message you wanted
        if (result.accountId) {
            console.log(`%c${result.accountId} is working`, 'color: #0073aa; font-weight: bold;');
            console.log(`   Reason: ${result.reason}`);
            injectLassoScript(result.accountId);
        } else {
            console.log(`%cThere's No Tracking Code`, 'color: #d63638; font-weight: bold;');
            console.log(`   Reason: ${result.reason}`);
        }
    }

    /**
     * A class to encapsulate the tracking logic.
     */
    class LassoTracker {
        constructor(settings) {
            this.globalId = settings.globalAccountId || null;
            this.customMap = settings.customTrackingMap || {};
            this.excludeList = settings.pagesToExclude || [];
            this.currentPageId = settings.currentPageId;
        }

        /**
         * **UPDATED METHOD**
         * Determines the correct Lasso Account ID and provides a reason for the decision.
         * @returns {object} An object with {accountId, reason}.
         */
        getTrackingDecision() {
            // 1. Check exclusion list first.
            if (this.excludeList.includes(this.currentPageId)) {
                return { accountId: null, reason: `Page ID ${this.currentPageId} is in the exclusion list.` };
            }

            // 2. Check for a page-specific Account ID.
            if (this.customMap[this.currentPageId]) {
                return { accountId: this.customMap[this.currentPageId], reason: `A custom Account ID was found for Page ID ${this.currentPageId}.` };
            }

            // 3. Fall back to the global Account ID.
            if (this.globalId) {
                return { accountId: this.globalId, reason: 'Using the global Account ID as a fallback.' };
            }

            // 4. If no ID is found, return null.
            return { accountId: null, reason: 'No custom or global Account ID is set for this page.' };
        }
    }

    /**
     * Injects the modern Lasso analytics script into the document body.
     * @param {string} accountId The Lasso Account ID to use.
     */
    function injectLassoScript(accountId) {
        if (document.getElementById('lasso-analytics-script')) {
            return; // Script already exists.
        }
        const script = document.createElement('script');
        script.id = 'lasso-analytics-script';
        script.type = 'text/javascript';

        script.textContent = `
window.LassoAnalyticsAPI = 2;
(function(t,r,a,c,k,e,d){t.LassoAnalyticsObject=k;t[k]=t[k]||function(){(t[k].q=t[k].q||[]).push(arguments);return t[k]},e=r.createElement(a),e.async=1;e.src=c;d=r.getElementsByTagName(a)[0];d.parentNode.insertBefore(e,d)})(window,document,'script','https://platform.lassocrm.com/wt/analytics.min.js','LassoAnalytics');
LassoAnalytics('setAccountId', '${accountId}')('pageView')('patchRegistrationForms');
`;
        document.body.appendChild(script);
    }

    // --- Run on page load ---
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLassoTracking);
    } else {
        initLassoTracking();
    }

})();