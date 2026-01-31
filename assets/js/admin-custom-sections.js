jQuery(document).ready(function ($) {

    // Initialize Sortable
    var sortableList = $('#fsm-sortable-list');
    if (sortableList.length) {
        sortableList.sortable({
            handle: '.fsm-drag-handle',
            axis: 'y',
            placeholder: 'ui-state-highlight',
            helper: function (e, tr) {
                var $originals = tr.children();
                var $helper = tr.clone();
                $helper.children().each(function (index) {
                    // Set helper cell sizes to match the original sizes
                    $(this).width($originals.eq(index).width());
                });
                return $helper;
            },
            update: function (event, ui) {
                // Collect new order
                var order = {};
                sortableList.find('tr').each(function (index) {
                    var id = $(this).data('id');
                    order[index] = id;
                });

                // Send AJAX request
                $.post(fsmAdmin.ajaxUrl, {
                    action: 'fsm_reorder_sections',
                    nonce: fsmAdmin.nonce,
                    order: order
                }).done(function (response) {
                    if (!response.success) {
                        alert('Hiba történt a sorrend mentésekor.');
                    }
                }).fail(function () {
                    alert('Hiba történt a kommunikációban.');
                });
            }
        });
    }

    // Toggle Status
    $('.fsm-toggle-status').on('click', function () {
        var btn = $(this);
        var id = btn.data('id');

        btn.prop('disabled', true);

        $.post(fsmAdmin.ajaxUrl, {
            action: 'fsm_toggle_section',
            nonce: fsmAdmin.nonce,
            id: id
        }).done(function (response) {
            btn.prop('disabled', false);
            if (response.success) {
                // Toggle UI
                if (btn.text().trim() === 'Aktív') {
                    btn.text('Inaktív');
                    btn.addClass('button-link-delete');
                } else {
                    btn.text('Aktív');
                    btn.removeClass('button-link-delete');
                }
            } else {
                alert('Hiba az állapot váltásakor.');
            }
        }).fail(function () {
            btn.prop('disabled', false);
            alert('Hiba történt a kommunikációban.');
        });
    });

    // Confirm Delete
    $('.fsm-delete-btn').on('click', function (e) {
        if (!confirm(fsmAdmin.confirmDelete)) {
            e.preventDefault();
        }
    });

});
