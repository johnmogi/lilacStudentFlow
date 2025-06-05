jQuery(document).ready(function($) {
    // Handle code generation
    $('#generate-codes-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var $results = $('#code-generation-results');
        
        $button.prop('disabled', true).text('Generating...');
        $results.html('<p>Generating codes, please wait...</p>');
        
        $.ajax({
            url: registrationCodes.ajax_url,
            type: 'POST',
            data: {
                action: 'generate_codes',
                nonce: registrationCodes.nonce,
                count: $form.find('#code-count').val(),
                role: $form.find('#code-role').val()
            },
            success: function(response) {
                if (response.success && response.data.codes) {
                    var html = '<div class="notice notice-success"><p>Successfully generated ' + response.data.codes.length + ' codes:</p><ul class="code-list">';
                    response.data.codes.forEach(function(code) {
                        html += '<li><code>' + code + '</code></li>';
                    });
                    html += '</ul></div>';
                    $results.html(html);
                } else {
                    $results.html('<div class="notice notice-error"><p>Error: ' + (response.data.message || 'Unknown error occurred') + '</p></div>');
                }
            },
            error: function() {
                $results.html('<div class="notice notice-error"><p>Error: Could not connect to the server. Please try again.</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false).text('Generate Codes');
            }
        });
    });

    // Handle code validation
    $('#validate-code-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var $results = $('#code-validation-results');
        
        $button.prop('disabled', true).text('Validating...');
        $results.html('<p>Validating code, please wait...</p>');
        
        $.ajax({
            url: registrationCodes.ajax_url,
            type: 'POST',
            data: {
                action: 'validate_code',
                nonce: registrationCodes.nonce,
                code: $form.find('#code-to-validate').val()
            },
            success: function(response) {
                if (response.success) {
                    var html = '<div class="notice notice-success">';
                    html += '<p><strong>Code is valid!</strong></p>';
                    html += '<ul>';
                    html += '<li><strong>Code:</strong> ' + response.data.code + '</li>';
                    html += '<li><strong>Role:</strong> ' + response.data.role + '</li>';
                    html += '<li><strong>Status:</strong> ' + (response.data.is_used ? 'Used' : 'Available') + '</li>';
                    if (response.data.used_by) {
                        html += '<li><strong>Used by:</strong> User #' + response.data.used_by + '</li>';
                    }
                    if (response.data.used_at) {
                        html += '<li><strong>Used at:</strong> ' + response.data.used_at + '</li>';
                    }
                    html += '</ul></div>';
                    $results.html(html);
                } else {
                    $results.html('<div class="notice notice-error"><p>' + (response.data.message || 'Invalid code') + '</p></div>');
                }
            },
            error: function() {
                $results.html('<div class="notice notice-error"><p>Error: Could not connect to the server. Please try again.</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false).text('Validate Code');
            }
        });
    });
});
