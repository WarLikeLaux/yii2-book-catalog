(function ($) {
    'use strict';

    var container = document.getElementById('toast-container');
    var subscriptionUrl = container.dataset.subscriptionUrl;
    var errorSubscription = container.dataset.errorSubscription;
    var errorRequest = container.dataset.errorRequest;

    function showToast(message, bgClass) {
        var toast = document.getElementById('app-toast');
        var body = document.getElementById('toast-body');
        toast.className = 'toast align-items-center border-0 ' + bgClass;
        body.textContent = message;
        bootstrap.Toast.getOrCreateInstance(toast).show();
    }

    document.body.addEventListener('htmx:configRequest', function (evt) {
        evt.detail.headers['X-Requested-With'] = 'XMLHttpRequest';
    });

    document.addEventListener('click', function (e) {
        var link = e.target.closest('.sub-link');
        if (!link) return;
        e.preventDefault();
        var id = link.dataset.id;
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('sub-modal'));
        modal.show();
        htmx.ajax('GET', subscriptionUrl + '?authorId=' + id, '#modal-content');
    });

    $(document).on('beforeSubmit', '#subscription-form', function (e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function (data) {
                if (data.success) {
                    showToast(data.message, 'text-bg-success');
                    $('#sub-modal').modal('hide');
                    form[0].reset();
                } else {
                    showToast(data.message || errorSubscription, 'text-bg-danger');
                }
            },
            error: function () {
                showToast(errorRequest, 'text-bg-danger');
            }
        });
        return false;
    });

    document.body.addEventListener('htmx:afterSwap', function (evt) {
        if (evt.detail.target.id === 'book-list' || evt.detail.target.id === 'book-cards-container') {
            if (typeof GLightbox !== 'undefined') {
                GLightbox({selector: '.glightbox'});
            }
        }
    });
})(jQuery);
