import ArticleComments from '../article-comments';

/**
 * Initialisation de la page d'article
 */
$(document).ready(function () {
    if ($('#comments-container').length) {

        const articleId = $('input[name="article_id"]').val();

        const commentsCount = parseInt($('#comments-count').text().match(/\d+/)[0]);

        // Récupère l'URL pour l'ajout de nouveaux commentaires
        const newCommentUrl = $('#comment-form').data('action-url');

        // Initialise le gestionnaire de commentaires
        ArticleComments.init({
            articleId: articleId,
            commentsCount: commentsCount,
            newCommentUrl: newCommentUrl || $('#comment-form').attr('action')
        });
    }
});