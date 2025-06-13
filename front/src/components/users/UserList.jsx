import { useState, useEffect } from 'react';
import { getUsers, deleteUser } from '../../api/users';
import UserItem from './UserItem';
import {PageLoader} from "../shared/Loader.jsx";

export default function UserList() {
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        fetchUsers().then();
    }, []);

    const fetchUsers = async () => {
        try {
            setLoading(true);
            const data = await getUsers();
            setUsers(data);
            setError('');
        } catch (err) {
            console.error('Erreur lors du chargement:', err);
            setError('Erreur lors du chargement des utilisateurs');
        } finally {
            setLoading(false);
        }
    };

    const handleDeleteUser = async (userId) => {
        try {
            await deleteUser(userId);
            setUsers(users.filter(user => user.id !== userId));
        } catch (err) {
            console.error('Erreur lors de la suppression:', err);
            throw err;
        }
    };

    const handleCreateUser = () => {
        window.location.href = '/users/new';
    };

    if (loading) return <PageLoader />;
    if (error) return <div>Erreur: {error}</div>;


    return (
        <div className="bg-white shadow rounded-lg overflow-hidden p-4">
                <h1 className="text-2xl font-bold text-gray-800 mb-6 text-center">
                    Gestion des utilisateurs
                </h1>

                {error && (
                    <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span className="block sm:inline">{error}</span>
                        <button
                            onClick={fetchUsers}
                            className="ml-4 text-sm underline hover:no-underline"
                        >
                            Réessayer
                        </button>
                    </div>
                )}

                <div className="mb-4 flex justify-between items-center">
                    <button
                        onClick={handleCreateUser}
                        className="inline-block bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-5 rounded-md transition-colors duration-200"
                    >
                        <i className="fas fa-plus mr-2"></i>
                        Créer un utilisateur
                    </button>

                    <button
                        onClick={fetchUsers}
                        className="inline-block bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-md transition-colors duration-200"
                    >
                        <i className="fas fa-sync mr-2"></i>
                        Actualiser
                    </button>
                </div>

                <div className="bg-white rounded-lg overflow-hidden ">
                    {users.length === 0 ? (
                        <div className="px-4 py-8 text-center text-gray-500">
                            <i className="fas fa-users text-4xl mb-4 text-gray-300"></i>
                            <p>Aucun utilisateur trouvé</p>
                        </div>
                    ) : (
                        <table className="w-full">
                            <thead>
                            <tr className="bg-gray-50">
                                <th className="px-4 py-4 text-left text-sm font-semibold text-gray-700">
                                    Pseudo
                                </th>
                                <th className="px-4 py-4 text-left text-sm font-semibold text-gray-700">
                                    Email
                                </th>
                                <th className="px-4 py-4 text-left text-sm font-semibold text-gray-700">
                                    Rôle
                                </th>
                                <th className="px-4 py-4 text-left text-sm font-semibold text-gray-700">
                                    Inscription
                                </th>
                                <th className="px-4 py-4 text-left text-sm font-semibold text-gray-700">
                                    Actions
                                </th>
                            </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                            {users.map((user) => (
                                <UserItem
                                    key={user.id}
                                    user={user}
                                    onDelete={handleDeleteUser}
                                />
                            ))}
                            </tbody>
                        </table>
                    )}
                </div>

                <div className="mt-6 text-center text-sm text-gray-500">
                    {users.length} utilisateur{users.length > 1 ? 's' : ''} trouvé{users.length > 1 ? 's' : ''}
                </div>
        </div>
    );
}