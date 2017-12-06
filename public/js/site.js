$(document).ready(function() {
    $('#submitbutton').click(function(e) {
        e.preventDefault();

        var button = $(this);

        button.prop('disabled', true);
        button.removeClass('loading');
        button.removeClass('error');
        button.removeClass('success');
        button.html("Please wait...");

        $.post('/invite', $("#inviteform").serialize())
            .done(function() {
                console.log('success!');
                button.addClass('success');
                button.html('WOOT. Check your email!');
            })
            .fail(function(xhr, status, error) {
                button.addClass('error');
                if (xhr.responseJSON === 'already_in_team') {
                    button.html('Already registered!');
                } else {
                    button.html('Error occurred');
                }
            })
            .always(function() {
                button.prop('disabled', false);
            });

        return false;
    });
});
