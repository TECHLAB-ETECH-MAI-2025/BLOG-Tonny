import fetchApi from './api';

export const getCategories = async (page = 1, limit = 10) => {
    return fetchApi(`/categories?page=${page}&limit=${limit}`);
};

export const getCategory = async (id) => {
    return fetchApi(`/categories/${id}`);
};

export const createCategory = async (categoryData) => {
    return fetchApi('/categories', {
        method: 'POST',
        body: JSON.stringify(categoryData),
    });
};

export const updateCategory = async (id, categoryData) => {
    return fetchApi(`/categories/${id}`, {
        method: 'PUT',
        body: JSON.stringify(categoryData),
    });
};

export const deleteCategory = async (id) => {
    return fetchApi(`/categories/${id}`, {
        method: 'DELETE',
    });
};