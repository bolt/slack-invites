var $ = require('jquery');
require('bootstrap-sass');
require('tether');
require('vue');

$(document).ready(function() {
    renderMembers();
    submitListener();
});

function renderMembers() {
    $.post('/members', $('form[name="invite"]').serialize())
        .done(function(data) {
                $('#spinner').hide();
                $('#avatars').append(data);
            }
        );
}

function submitListener() {
    $('#invite_submit').click(function(e) {
        var button = $(this);

        e.preventDefault();

        $('#invite_errors_json').hide().html('');
        postForm(button);
    });
}

function postForm(button) {
    var label = freezeButton(button);
    $.post('/', $('form[name="invite"]').serialize())
        .done(function() {
            console.log('success!');
            button.addClass('success');
            button.html('WOOT. Check your email!');
        })
        .fail(function(xhr) {
            var panel = $('#invite_errors_json');
            panel.show();
            if (xhr.status === 500) {
                panel.html('There was a server error!');
            } else {
                printError(panel, xhr.responseJSON.errors);
            }
            button.html(label);
            button.prop('disabled', false);
        })
        .always(function() {
        });
}

function freezeButton(button) {
    var label = button.text();

    button.prop('disabled', true);
    button.removeClass('loading');
    button.removeClass('error');
    button.removeClass('success');
    button.html('Please wait...');

    return label;
}

function printError(panel, errors) {
    for (var err in errors) {
        if (!errors.hasOwnProperty(err)) {
            continue;
        }
        if (errors[err] !== null && typeof errors[err] === 'object') {
            printError(panel, errors[err]);
            continue;
        }
        panel.append('<li>' + errors[err] + '</li>');
    }
}
