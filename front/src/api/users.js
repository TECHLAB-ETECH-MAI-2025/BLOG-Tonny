import fetchApi from './api';

export const getUsers = async () => {
    return fetchApi('/users');
};

export const getUser = async (id) => {
    return fetchApi(`/users/${id}`);
};

export const createUser = async (userData) => {
    return fetchApi('/users', {
        method: 'POST',
        body: JSON.stringify(userData),
    });
};

export const updateUser = async (id, userData) => {
    return fetchApi(`/users/${id}`, {
        method: 'PUT',
        body: JSON.stringify(userData),
    });
};

export const deleteUser = async (id) => {
    return fetchApi(`/users/${id}`, {
        method: 'DELETE',
    });
};

export const getCurrentUserProfile = async () => {
    return fetchApi('/users/me');
};