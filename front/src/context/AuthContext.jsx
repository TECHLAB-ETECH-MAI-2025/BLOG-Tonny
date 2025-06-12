import { createContext, useContext, useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';

const AuthContext = createContext();

export function AuthProvider({ children }) {
    const [isAuthenticated, setIsAuthenticated] = useState(
        !!localStorage.getItem('authToken')
    );
    const navigate = useNavigate();
    const location = useLocation();

    const handleLogin = (userData) => {
        localStorage.setItem('authToken', userData.token);
        setIsAuthenticated(true);

        // Récupérer l'URL de destination depuis l'état de navigation
        const from = location.state?.from?.pathname || '/';
        navigate(from, { replace: true });
    };

    const handleLogout = () => {
        localStorage.removeItem('authToken');
        setIsAuthenticated(false);
        navigate('/login');
    };

    return (
        <AuthContext.Provider value={{ isAuthenticated, handleLogin, handleLogout }}>
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    const context = useContext(AuthContext);
    if (!context) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
}