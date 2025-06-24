// api/article.js - VERSION CORRIGÉE
import fetchApi from './api';

export const getArticles = async (page = 1, limit = 10) => {
    return fetchApi(`/articles?page=${page}&limit=${limit}`);
};

export const getArticle = async (id) => {
    return fetchApi(`/articles/${id}`);
};

export const createArticle = async (articleData) => {
    return fetchApi('/articles', {
        method: 'POST',
        body: JSON.stringify(articleData),
    });
};

export const updateArticle = async (id, articleData) => {
    return fetchApi(`/articles/${id}`, {
        method: 'PUT',
        body: JSON.stringify(articleData),
    });
};

export const deleteArticle = async (id) => {
    return fetchApi(`/articles/${id}`, {
        method: 'DELETE',
    });
};
export const toggleLike = async (articleId) => {
    return fetchApi(`/articles/${articleId}/like`, {
        method: 'POST',
    });
};
