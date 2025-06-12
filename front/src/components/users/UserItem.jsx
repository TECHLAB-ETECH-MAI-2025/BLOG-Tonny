import { useState } from 'react';

export default function UserItem({ user, onDelete }) {
    const [isDeleting, setIsDeleting] = useState(false);

    const handleDelete = async () => {
        if (window.confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
            setIsDeleting(true);
            try {
                await onDelete(user.id);
            } catch (error) {
                console.error('Erreur lors de la suppression:', error);
                alert('Erreur lors de la suppression de l\'utilisateur');
            } finally {
                setIsDeleting(false);
            }
        }
    };

    const isAdmin = user.roles && user.roles.includes('ROLE_ADMIN');

    return (
        <tr className="hover:bg-gray-50 transition-colors duration-150">
            <td className="px-4 py-4 text-sm text-gray-900">
                {user.username}
            </td>
            <td className="px-4 py-4 text-sm text-gray-900">
                <div className="flex items-center">
                    <svg className="flex-shrink-0 mr-1.5 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                    </svg>
                    {user.email}
                </div>
            </td>
            <td className="px-4 py-4">
                <span className={`inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium ${
                    isAdmin ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'
                }`}>
                    {isAdmin ? 'Admin' : 'Utilisateur'}
                </span>
            </td>
            <td className="px-4 py-4">
                {user.createdAt && (
                    <div className="flex items-center text-sm text-gray-500">
                        <svg className="flex-shrink-0 mr-1.5 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fillRule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clipRule="evenodd" />
                        </svg>
                        {new Date(user.createdAt).toLocaleDateString('fr-FR')}
                    </div>
                )}
            </td>
            <td className="px-4 py-4">
                <div className="flex items-center gap-3">
                    <a
                        href={`/users/${user.id}/edit`}
                        className="text-blue-600 hover:text-blue-800 font-medium text-sm transition-colors duration-150"
                    >
                        <i className="fas fa-edit mr-1"></i> Éditer
                    </a>
                    <button
                        onClick={handleDelete}
                        disabled={isDeleting}
                        className="text-red-600 hover:text-red-800 font-medium text-sm transition-colors duration-150 disabled:opacity-50"
                    >
                        {isDeleting ? (
                            <>
                                <i className="fas fa-spinner fa-spin mr-1"></i> Suppression...
                            </>
                        ) : (
                            <>
                                <i className="fas fa-trash mr-1"></i> Supprimer
                            </>
                        )}
                    </button>
                </div>
            </td>
        </tr>
    );
}