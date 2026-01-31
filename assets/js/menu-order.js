/**
 * Forme Smart Menu - Menu Order Handler
 * Version: 0.7.0
 * Handles drag & drop menu ordering with Sortable.js
 */

(function ($) {
    'use strict';

    let sortableInstance = null;

    function initMenuOrder() {
        const orderList = document.getElementById('fsm-menu-order-list');
        if (!orderList) return;

        // Initialize Sortable.js
        sortableInstance = Sortable.create(orderList, {
            animation: 150,
            handle: '.fsm-sortable-handle',
            ghostClass: 'is-dragging',
            onEnd: function (evt) {
                updateOrderData();
            }
        });

        // Update hidden input on any change
        updateOrderData();
    }

    function updateOrderData() {
        const items = document.querySelectorAll('#fsm-menu-order-list .fsm-sortable-item');
        const order = [];

        items.forEach(function (item) {
            order.push({
                type: item.dataset.itemType,
                id: item.dataset.itemId
            });
        });

        document.getElementById('fsm-menu-order-data').value = JSON.stringify(order);
    }

    // Reset to default order
    $(document).on('click', '#fsm-reset-order', function (e) {
        e.preventDefault();
        if (!confirm('Biztosan visszaállítod az alapértelmezett sorrendet? A jelenlegi sorrend el fog veszni.')) {
            return;
        }

        // Submit form with reset flag
        const form = $(this).closest('form');
        $('<input>').attr({
            type: 'hidden',
            name: 'fsm_reset_order',
            value: '1'
        }).appendTo(form);
        form.submit();
    });

    // Initialize on tab switch
    $(document).on('click', '.fsm-admin-tabs__link[data-tab="tab-order"]', function () {
        setTimeout(initMenuOrder, 100);
    });

    // Initialize if already on order tab
    $(document).ready(function () {
        if ($('#tab-order').hasClass('is-active')) {
            initMenuOrder();
        }
    });

})(jQuery);
