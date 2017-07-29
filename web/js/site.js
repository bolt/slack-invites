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
                console.log('fail!');
                button.addClass('error');
                if (error === 'Bad Request') {
                    button.html(xhr.responseJSON);
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
