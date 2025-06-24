import { useState } from 'react';
import {Link, useNavigate} from 'react-router-dom';
import { register } from '../../api/auth';


export default function Register() {
    const [username, setUsername] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [fieldErrors, setFieldErrors] = useState({});
    const [isLoading, setIsLoading] = useState(false);
    const [success, setSuccess] = useState(false);
    const navigate = useNavigate();

    const parseBackendErrors = (errorResponse) => {
        const errors = {};

        if (errorResponse.code === 'USER_ALREADY_EXISTS' || errorResponse.code === 'EMAIL_ALREADY_EXISTS') {
            errors.email = 'Un utilisateur avec cet email existe déjà';
        } else if (errorResponse.code === 'USERNAME_TAKEN') {
            errors.username = 'Ce nom d\'utilisateur est déjà pris';
        } else if (errorResponse.code === 'INVALID_EMAIL_FORMAT') {
            errors.email = 'Format d\'email invalide';
        } else if (errorResponse.code === 'MISSING_REQUIRED_FIELD') {
            const fieldName = errorResponse.error.match(/'([^']+)'/)?.[1];
            if (fieldName) {
                errors[fieldName] = `Le champ ${fieldName} est requis`;
            }
        } else if (errorResponse.code === 'VALIDATION_ERROR' && errorResponse.details) {
            errorResponse.details.forEach(detail => {
                if (detail.toLowerCase().includes('email')) {
                    errors.email = detail;
                } else if (detail.toLowerCase().includes('username') || detail.toLowerCase().includes('nom')) {
                    errors.username = detail;
                } else if (detail.toLowerCase().includes('password') || detail.toLowerCase().includes('mot de passe')) {
                    errors.password = detail;
                }
            });
        } else if (errorResponse.code && errorResponse.code.includes('PASSWORD')) {
            errors.password = errorResponse.error || 'Le mot de passe ne respecte pas les critères requis';
        }

        return errors;
    };

    const handleFieldChange = (field, value, setter) => {
        setter(value);
        if (fieldErrors[field]) {
            setFieldErrors({
                ...fieldErrors,
                [field]: ''
            });
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setFieldErrors({});
        setIsLoading(true);

        try {
            await register(username, email, password);
            setSuccess(true);
            setTimeout(() => navigate('/login'), 2000);
        } catch (err) {
            try {
                const errorData = JSON.parse(err.message);
                const parsedErrors = parseBackendErrors(errorData);

                if (Object.keys(parsedErrors).length > 0) {
                    setFieldErrors(parsedErrors);
                } else {
                    setError(errorData.error || 'Échec de l\'inscription');
                }
            } catch (parseError) {
                setError(err.message || 'Échec de l\'inscription');
            }
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
            <div className="max-w-md w-full space-y-8 bg-white p-8 rounded-lg shadow-md">
                <div>
                    <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
                        Créer un nouveau compte
                    </h2>
                </div>

                {error && Object.keys(fieldErrors).length === 0 && (
                    <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span className="block sm:inline">{error}</span>
                    </div>
                )}

                {success && (
                    <div className="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span className="block sm:inline">Inscription réussie ! Redirection vers la page de connexion...</span>
                    </div>
                )}

                <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
                    <div className="rounded-md shadow-sm space-y-4">
                        <div>
                            <label htmlFor="username" className="block text-sm font-medium text-gray-700">
                                Nom d'utilisateur
                            </label>
                            <input
                                id="username"
                                name="username"
                                type="text"
                                required
                                className={`mt-1 appearance-none block w-full px-3 py-2 border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm ${
                                    fieldErrors.username ? 'border-red-300' : 'border-gray-300'
                                }`}
                                value={username}
                                onChange={(e) => handleFieldChange('username', e.target.value, setUsername)}
                            />
                            {fieldErrors.username && (
                                <p className="mt-1 text-sm text-red-600">{fieldErrors.username}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                                Adresse email
                            </label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                required
                                className={`mt-1 appearance-none block w-full px-3 py-2 border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm ${
                                    fieldErrors.email ? 'border-red-300' : 'border-gray-300'
                                }`}
                                value={email}
                                onChange={(e) => handleFieldChange('email', e.target.value, setEmail)}
                            />
                            {fieldErrors.email && (
                                <p className="mt-1 text-sm text-red-600">{fieldErrors.email}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password" className="block text-sm font-medium text-gray-700">
                                Mot de passe (min 8 caractères)
                            </label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                minLength={8}
                                className={`mt-1 appearance-none block w-full px-3 py-2 border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm ${
                                    fieldErrors.password ? 'border-red-300' : 'border-gray-300'
                                }`}
                                value={password}
                                onChange={(e) => handleFieldChange('password', e.target.value, setPassword)}
                            />
                            {fieldErrors.password && (
                                <p className="mt-1 text-sm text-red-600">{fieldErrors.password}</p>
                            )}
                        </div>
                    </div>

                    <div>
                        <button
                            type="submit"
                            disabled={isLoading || success}
                            className={`group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 ${isLoading || success ? 'opacity-50 cursor-not-allowed' : ''}`}
                        >
                            {isLoading ? 'Inscription en cours...' : 'S\'inscrire'}
                        </button>
                    </div>
                </form>

                <div className="text-center">
                    <p className="mt-2 text-sm text-gray-600">
                        Déjà un compte ?{' '}
                        <Link to="/login" className="font-medium text-blue-600 hover:text-blue-500">
                            Se connecter
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    );
}