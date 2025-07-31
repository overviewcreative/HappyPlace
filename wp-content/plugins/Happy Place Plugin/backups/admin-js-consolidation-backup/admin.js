// Admin JavaScript
jQuery(document).ready(function($) {
    // Handle settings form submission
    $('.happy-place-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        // Add your form handling logic here
        
        // Example AJAX call
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'happy_place_save_settings',
                nonce: $('#happy_place_nonce').val(),
                formData: $(this).serialize()
            },
            success: function(response) {
                if (response.success) {
                    alert('Settings saved successfully!');
                } else {
                    alert('Error saving settings.');
                }
            }
        });
    });
});
