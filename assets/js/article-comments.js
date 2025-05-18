/**
 * Gestionnaire de commentaires pour les pages d'articles
 */
const ArticleComments = {
    /**
     * Initialise la gestion des commentaires
     * @param {Object} options - Options de configuration
     */
    init: function (options) {
        this.options = $.extend({
            articleId: null,
            commentsCount: 0,
            commentFormSelector: '#comment-form',
            commentAlertSelector: '#comment-alert',
            toggleCommentsSelector: '#toggle-comments',
            commentsContainerSelector: '#comments-container',
            commentsToggleIconSelector: '.comments-toggle',
            commentsListSelector: '#comments-list',
            commentsCountSelector: '#comments-count',
            noCommentsMessageSelector: '#no-comments-message',
            newCommentUrl: null
        }, options);

        // Stockage des éléments DOM fréquemment utilisés
        this.$commentForm = $(this.options.commentFormSelector);
        this.$commentAlert = $(this.options.commentAlertSelector);
        this.$toggleComments = $(this.options.toggleCommentsSelector);
        this.$commentsContainer = $(this.options.commentsContainerSelector);
        this.$commentsToggleIcon = $(this.options.commentsToggleIconSelector);
        this.$commentsList = $(this.options.commentsListSelector);
        this.$commentsCount = $(this.options.commentsCountSelector);
        this.$noCommentsMessage = $(this.options.noCommentsMessageSelector);

        this.currentCommentsCount = this.options.commentsCount;

        this.bindEvents();
    },

    /**
     * Attache les écouteurs d'événements
     */
    bindEvents: function () {
        // Gestion de l'affichage/masquage des commentaires
        this.$toggleComments.on('click', this.toggleCommentsVisibility.bind(this));

        // Gestion de la soumission du formulaire de commentaire
        this.$commentForm.on('submit', this.handleCommentSubmit.bind(this));
    },

    /**
     * Affiche ou masque la section des commentaires
     */
    toggleCommentsVisibility: function () {
        if (this.$commentsContainer.hasClass('comments-container-visible')) {
            this.$commentsContainer.removeClass('comments-container-visible');
            this.$commentsContainer.addClass('comments-container-hidden');
            this.$commentsToggleIcon.addClass('collapsed');
        } else {
            this.$commentsContainer.removeClass('comments-container-hidden');
            this.$commentsContainer.addClass('comments-container-visible');
            this.$commentsToggleIcon.removeClass('collapsed');
        }
    },

    /**
     * Gère la soumission du formulaire de commentaire
     * @param {Event} event - L'événement de soumission
     */
    handleCommentSubmit: function (event) {
        event.preventDefault();

        const $form = $(event.currentTarget);
        const formData = new FormData($form[0]);
        const actionUrl = this.options.newCommentUrl;

        // Récupère les valeurs du formulaire
        const author = $form.find('#comment_author').val();
        const content = $form.find('#comment_content').val();

        const $submitBtn = $form.find('button[type="submit"]');
        const originalBtnText = $submitBtn.html();
        $submitBtn.html('<span class="inline-block animate-spin mr-1">⟳</span> Envoi...');
        $submitBtn.prop('disabled', true);

        $.ajax({
            url: actionUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (data) => {
                if (data.success) {
                    this.showSuccessMessage('Commentaire ajouté avec succès!');
                    $form[0].reset();

                    if (this.$commentsContainer.hasClass('comments-container-hidden')) {
                        this.$commentsContainer.removeClass('comments-container-hidden');
                        this.$commentsContainer.addClass('comments-container-visible');
                        this.$commentsToggleIcon.removeClass('collapsed');
                    }

                    this.addNewComment(author, content);
                } else {
                    this.showErrorMessage(data.message || 'Erreur lors de l\'ajout du commentaire');
                }
            },
            error: (xhr, status, error) => {
                console.error('Erreur:', error);

                // Vérifie si l'utilisateur a été redirigé vers la page de connexion
                if (xhr.responseURL && xhr.responseURL.includes('/login')) {
                    this.showErrorMessage('Vous devez être connecté pour commenter');
                } else if (xhr.responseText && xhr.responseText.includes('DOCTYPE html')) {
                    this.showErrorMessage('Session expirée. Veuillez vous reconnecter.');
                } else {
                    this.showErrorMessage('Une erreur est survenue lors de l\'envoi du commentaire');
                }
            },
            complete: () => {
                // Réinitialise l'état du bouton
                $submitBtn.html(originalBtnText);
                $submitBtn.prop('disabled', false);
            }
        });
    },

    /**
     * Ajoute un nouveau commentaire à la liste
     * @param {string} author - L'auteur du commentaire
     * @param {string} content - Le contenu du commentaire
     */
    addNewComment: function (author, content) {
        // Supprime le message "aucun commentaire" s'il existe
        if (this.$noCommentsMessage.length && this.$commentsList.find(this.$noCommentsMessage).length) {
            this.$noCommentsMessage.remove();
        }

        // Crée le nouvel élément de commentaire
        const $newComment = $('<div>')
            .addClass('comment-card bg-gray-50 border-l-4 border-l-blue-500 pl-4 py-3 rounded-r new-comment')
            .html(`
                <div>
                    <span class="font-semibold text-gray-800">${author}</span>
                    <span class="text-gray-700">: ${content}</span>
                </div>
            `);

        this.$commentsList.prepend($newComment);

        // Met à jour le compteur de commentaires
        this.currentCommentsCount++;
        this.$commentsCount.text(`(${this.currentCommentsCount})`);

        $('html, body').animate({
            scrollTop: $newComment.offset().top - 100
        }, 500);
    },

    /**
     * Affiche un message de succès
     * @param {string} message - Le message à afficher
     */
    showSuccessMessage: function (message) {
        this.$commentAlert.text(message);
        this.$commentAlert.removeClass('hidden bg-red-100 text-red-700');
        this.$commentAlert.addClass('bg-green-100 text-green-700');

        setTimeout(() => {
            this.$commentAlert.addClass('hidden');
        }, 3000);
    },

    /**
     * Affiche un message d'erreur
     * @param {string} message - Le message d'erreur à afficher
     */
    showErrorMessage: function (message) {
        this.$commentAlert.text(message);
        this.$commentAlert.removeClass('hidden bg-green-100 text-green-700');
        this.$commentAlert.addClass('bg-red-100 text-red-700');

        setTimeout(() => {
            this.$commentAlert.addClass('hidden');
        }, 5000);
    }
};

export default ArticleComments;