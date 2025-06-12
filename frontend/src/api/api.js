const API_BASE_URL = 'http://localhost:8000/api';

async function fetchApi(endpoint, options = {}) {
    const url = `${API_BASE_URL}${endpoint}`;
    const headers = {
        'Content-Type': 'application/json',
        ...options.headers,
    };

    // Add authentication token if available
    const token = localStorage.getItem('authToken');
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    try {
        const response = await fetch(url, {
            ...options,
            headers,
        });

        if (!response.ok) {
            let errorMessage = 'API request failed';
            try {
                const error = await response.json();
                if (error.code === 'VALIDATION_ERROR' && error.details) {
                    // Handle validation errors specifically
                    errorMessage = error.details.join(', ');
                } else {
                    errorMessage = error.error || errorMessage;
                }
            } catch (e) {
                // If response is not JSON, use status text
                errorMessage = response.statusText || errorMessage;
            }
            throw new Error(errorMessage);
        }
        if (response.status === 204) {
            return null;
        }

        return response.json();
    } catch (error) {
        // Handle network errors
        if (error.name === 'TypeError' && error.message.includes('fetch')) {
            throw new Error('Network error: Unable to connect to the server');
        }
        throw error;
    }
}

export default fetchApi;
