import fetchApi from './api';

export const searchArticles = async (query) => {
    return fetchApi(`/search?q=${encodeURIComponent(query)}`);
};