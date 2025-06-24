import fetchApi from './api';

export const getComments = async (articleId) => {
    return fetchApi(`/comments/article/${articleId}`);
};

export const createComment = async (commentData) => {
    return fetchApi('/comments', {
        method: 'POST',
        body: JSON.stringify(commentData),
    });
};

export const deleteComment = async (commentId) => {
    return fetchApi(`/comments/${commentId}`, {
        method: 'DELETE',
    });
};