jQuery(document).ready(function($) {
    $('#happy-place-manual-sync').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $spinner = $button.next('.spinner');
        const $status = $('#sync-status');
        
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        $status.html('');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'happy_place_manual_sync',
                nonce: happy_place_sync.nonce
            },
            success: function(response) {
                if (response.success) {
                    $status.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                } else {
                    $status.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $status.html('<div class="notice notice-error"><p>An error occurred during the sync process.</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
});
