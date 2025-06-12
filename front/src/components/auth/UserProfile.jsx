import { useState, useEffect } from 'react';
import { getCurrentUser, logout } from '../../api/auth';
import { updateUser } from '../../api/users';
import {useAuth} from "../../context/AuthContext.jsx";
import {PageLoader} from "../../Loader.jsx";

export default function UserProfile() {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const { handleLogout } = useAuth();
    const [editMode, setEditMode] = useState(false);
    const [formData, setFormData] = useState({
        username: '',
        email: '',
        password: '',
    });
    const [fieldErrors, setFieldErrors] = useState({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        const fetchUser = async () => {
            try {
                const data = await getCurrentUser();
                setUser(data.user);
                setFormData({
                    username: data.user.username,
                    email: data.user.email,
                    password: '',
                });
            } catch (err) {
                setError(err.message || 'Erreur lors du chargement des données utilisateur');
            } finally {
                setLoading(false);
            }
        };

        fetchUser();
    }, []);

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData({
            ...formData,
            [name]: value,
        });

        if (fieldErrors[name]) {
            setFieldErrors({
                ...fieldErrors,
                [name]: ''
            });
        }
    };

    const parseBackendErrors = (errorResponse) => {
        const errors = {};

        if (errorResponse.code === 'EMAIL_ALREADY_EXISTS') {
            errors.email = 'Un utilisateur avec cet email existe déjà';
        } else if (errorResponse.code === 'USERNAME_TAKEN') {
            errors.username = 'Ce nom d\'utilisateur est déjà pris';
        } else if (errorResponse.code === 'INVALID_EMAIL_FORMAT') {
            errors.email = 'Format d\'email invalide';
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
            errors.password = errorResponse.error || 'Erreur de mot de passe';
        }

        return errors;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setIsSubmitting(true);
        setFieldErrors({});
        setError('');

        try {
            const updatedUser = await updateUser(user.id, formData);
            setUser(updatedUser);
            setEditMode(false);
        } catch (err) {
            try {
                const errorData = JSON.parse(err.message);
                const parsedErrors = parseBackendErrors(errorData);

                if (Object.keys(parsedErrors).length > 0) {
                    setFieldErrors(parsedErrors);
                } else {
                    setError(errorData.error || 'Échec de la mise à jour du profil');
                }
            } catch (parseError) {
                setError(err.message || 'Échec de la mise à jour du profil');
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleLogoutClick = async () => {
        try {
            await logout();
            handleLogout();
        } catch (err) {
            setError(err.message || 'Échec de la déconnexion');
        }
    };

    if (loading) {
        return <PageLoader/>;
    }

    if (error && !editMode) {
        return (
            <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span className="block sm:inline">{error}</span>
            </div>
        );
    }

    if (!user) return <p className="text-gray-600">Aucune donnée utilisateur</p>;

    return (
        <div className="max-w-3xl mx-auto px-4 py-8">
            <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 className="text-lg leading-6 font-medium text-gray-900">
                        Profil Utilisateur
                    </h3>
                </div>

                {!editMode ? (
                    <div className="px-4 py-5 sm:p-6">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Nom d'utilisateur</dt>
                                <dd className="mt-1 text-sm text-gray-900">{user.username}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Email</dt>
                                <dd className="mt-1 text-sm text-gray-900">{user.email}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Statut</dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${user.isOnline ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                                        {user.isOnline ? 'En ligne' : 'Hors ligne'}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Membre depuis</dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {new Date(user.createdAt).toLocaleDateString('fr-FR')}
                                </dd>
                            </div>
                        </div>

                        <div className="mt-6 flex space-x-3">
                            <button
                                onClick={() => setEditMode(true)}
                                className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Modifier le profil
                            </button>
                            <button
                                onClick={handleLogoutClick}
                                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                            >
                                Déconnexion
                            </button>
                        </div>
                    </div>
                ) : (
                    <form onSubmit={handleSubmit} className="px-4 py-5 sm:p-6">
                        {error && (
                            <div className="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                <span className="block sm:inline">{error}</span>
                            </div>
                        )}

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label htmlFor="username" className="block text-sm font-medium text-gray-700">
                                    Nom d'utilisateur
                                </label>
                                <input
                                    type="text"
                                    name="username"
                                    id="username"
                                    value={formData.username}
                                    onChange={handleInputChange}
                                    required
                                    className={`mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm ${
                                        fieldErrors.username ? 'border-red-300' : 'border-gray-300'
                                    }`}
                                />
                                {fieldErrors.username && (
                                    <p className="mt-1 text-sm text-red-600">{fieldErrors.username}</p>
                                )}
                            </div>
                            <div>
                                <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                                    Email
                                </label>
                                <input
                                    type="email"
                                    name="email"
                                    id="email"
                                    value={formData.email}
                                    onChange={handleInputChange}
                                    required
                                    className={`mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm ${
                                        fieldErrors.email ? 'border-red-300' : 'border-gray-300'
                                    }`}
                                />
                                {fieldErrors.email && (
                                    <p className="mt-1 text-sm text-red-600">{fieldErrors.email}</p>
                                )}
                            </div>
                            <div className="sm:col-span-2">
                                <label htmlFor="password" className="block text-sm font-medium text-gray-700">
                                    Nouveau mot de passe (laisser vide pour ne pas changer)
                                </label>
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    value={formData.password}
                                    onChange={handleInputChange}
                                    className={`mt-1 block w-full border rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm ${
                                        fieldErrors.password ? 'border-red-300' : 'border-gray-300'
                                    }`}
                                />
                                {fieldErrors.password && (
                                    <p className="mt-1 text-sm text-red-600">{fieldErrors.password}</p>
                                )}
                            </div>
                        </div>

                        <div className="mt-6 flex space-x-3">
                            <button
                                type="submit"
                                disabled={isSubmitting}
                                className={`inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 ${isSubmitting ? 'opacity-50 cursor-not-allowed' : ''}`}
                            >
                                {isSubmitting ? 'Enregistrement...' : 'Enregistrer les modifications'}
                            </button>
                            <button
                                type="button"
                                onClick={() => {
                                    setEditMode(false);
                                    setFieldErrors({});
                                    setError('');
                                }}
                                className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Annuler
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </div>
    );
}