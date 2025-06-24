import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { createUser, getUser, updateUser } from '../../api/users';
import {PageLoader} from "../shared/Loader.jsx";

export default function UserForm() {
    const navigate = useNavigate();
    const { id } = useParams();
    const isEditMode = Boolean(id);

    const [formData, setFormData] = useState({
        username: '',
        email: '',
        password: '',
        isAdmin: false,
    });

    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        if (isEditMode) {
            const fetchUser = async () => {
                try {
                    setLoading(true);
                    const user = await getUser(id);
                    setFormData({
                        username: user.username,
                        email: user.email,
                        password: '', // Ne pas pré-remplir le mot de passe pour des raisons de sécurité
                        isAdmin: user.roles.includes('ROLE_ADMIN'),
                    });
                } catch (err) {
                    setError('Erreur lors du chargement de l\'utilisateur');
                    console.error('Error fetching user:', err);
                } finally {
                    setLoading(false);
                }
            };

            fetchUser();
        }
    }, [id, isEditMode]);

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: type === 'checkbox' ? checked : value,
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            if (isEditMode) {
                const { password, ...rest } = formData;
                const updateData = password ? formData : rest;
                await updateUser(id, updateData);
            } else {
                await createUser(formData);
            }
            navigate('/users');
        } catch (err) {
            setError(err.message || 'Erreur lors de la sauvegarde de l\'utilisateur');
        } finally {
            setLoading(false);
        }
    };


    if (loading) {
        return <PageLoader></PageLoader>;
    }

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="container mx-auto px-4">
                <h1 className="text-2xl font-bold text-gray-800 mb-6 text-center">
                    {isEditMode ? 'Éditer l\'utilisateur' : 'Créer un utilisateur'}
                </h1>

                {error && (
                    <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                        {error}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4 max-w-md mx-auto">
                    <div className="mb-4">
                        <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="username">
                            Pseudo
                        </label>
                        <input
                            className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="username"
                            type="text"
                            name="username"
                            value={formData.username}
                            onChange={handleChange}
                            required
                        />
                    </div>
                    <div className="mb-6">
                        <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="email">
                            Email
                        </label>
                        <input
                            className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="email"
                            type="email"
                            name="email"
                            value={formData.email}
                            onChange={handleChange}
                            required
                        />
                    </div>
                    <div className="mb-6">
                        <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="password">
                            Mot de passe
                        </label>
                        <input
                            className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="password"
                            type="password"
                            name="password"
                            value={formData.password}
                            onChange={handleChange}
                            required={!isEditMode}
                            placeholder={isEditMode ? "Laisser vide pour ne pas changer" : ""}
                        />
                    </div>
                    <div className="mb-6">
                        <label className="block text-gray-700 text-sm font-bold mb-2">
                            <input
                                type="checkbox"
                                name="isAdmin"
                                checked={formData.isAdmin}
                                onChange={handleChange}
                                className="mr-2 leading-tight"
                            />
                            <span className="text-sm">Administrateur</span>
                        </label>
                    </div>
                    <div className="flex items-center justify-between">
                        <button
                            className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            type="submit"
                            disabled={loading}
                        >
                            {loading ? 'Enregistrement...' : 'Enregistrer'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
