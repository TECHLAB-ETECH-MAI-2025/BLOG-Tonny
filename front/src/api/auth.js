import fetchApi from './api';

export const login = async (username, password, deviceName = 'React App') => {
    return fetchApi('/auth/login', {
        method: 'POST',
        body: JSON.stringify({ username, password, device_name: deviceName }),
    });
};

export const register = async (username, email, password) => {
    return fetchApi('/auth/register', {
        method: 'POST',
        body: JSON.stringify({ username, email, password }),
    });
};

export const getCurrentUser = async () => {
    return fetchApi('/auth/me');
};

export const logout = async () => {
    return fetchApi('/auth/logout', {
        method: 'POST',
    });
};