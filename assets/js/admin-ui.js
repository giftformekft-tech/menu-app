/**
 * Forme Smart Menu - Admin UI JavaScript
 * Version: 0.7.0
 */

(function ($) {
    'use strict';

    // Tab Switching
    function initTabs() {
        $('.fsm-admin-tabs__link').on('click', function (e) {
            e.preventDefault();

            const targetId = $(this).data('tab');

            // Update active states
            $('.fsm-admin-tabs__link').removeClass('is-active');
            $(this).addClass('is-active');

            $('.fsm-admin-tab-content').removeClass('is-active');
            $('#' + targetId).addClass('is-active');

            // Store active tab in localStorage
            localStorage.setItem('fsm_active_tab', targetId);
        });

        // Restore last active tab
        const lastTab = localStorage.getItem('fsm_active_tab');
        if (lastTab && $('#' + lastTab).length) {
            $('.fsm-admin-tabs__link[data-tab="' + lastTab + '"]').trigger('click');
        }
    }

    // Accordion Toggle
    function initAccordions() {
        $('.fsm-accordion__header').on('click', function () {
            const $header = $(this);
            const $content = $header.next('.fsm-accordion__content');

            $header.toggleClass('is-open');
            $content.toggleClass('is-open').slideToggle(200);
        });
    }

    // Initialize on document ready
    $(document).ready(function () {
        initTabs();
        initAccordions();
    });

})(jQuery);
