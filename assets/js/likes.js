$(document).ready(function () {
    $('.like-btn').on('click', function (e) {
        e.preventDefault();

        const $button = $(this);
        const articleId = $button.data('article-id');
        const csrfToken = $button.data('csrf');
        $button.prop('disabled', true);

        $.ajax({
            url: '/like/article/' + articleId,
            method: 'POST',
            dataType: 'json',
            data: {_token: csrfToken},
            statusCode: {
                401: function (response) {
                    window.location.href = response.responseJSON.redirect;
                },
                403: function (response) {
                    alert('Erreur de sécurité. Veuillez rafraîchir la page.');
                }
            },
            success: function (data) {
                const $icon = $button.find('i');
                $icon.removeClass('far fas').addClass(data.isLiked ? 'fas' : 'far');
                $icon.toggleClass('text-red-500 text-gray-500');
                $('.like-count[data-article-id="' + articleId + '"]').text(data.likesCount);
            },
            error: function (xhr) {
                if (xhr.status !== 401) {
                    console.error('Erreur:', xhr.responseText);
                    alert('Une erreur est survenue');
                }
            },
            complete: function () {
                $button.prop('disabled', false);
            }
        });
    });
});