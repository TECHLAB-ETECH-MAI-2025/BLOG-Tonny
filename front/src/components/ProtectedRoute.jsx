import { Navigate, useLocation } from 'react-router-dom';

const ProtectedRoute = ({ children, isAuthenticated }) => {
    const location = useLocation();

    if (!isAuthenticated) {
        // Sauvegarder l'URL actuelle dans l'état de navigation
        return <Navigate to="/login" state={{ from: location }} replace />;
    }

    return children;
};

export default ProtectedRoute;