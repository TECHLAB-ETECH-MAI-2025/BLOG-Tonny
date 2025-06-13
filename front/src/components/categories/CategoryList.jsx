import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { getCategories, deleteCategory } from '../../api/category';
import {PageLoader} from "../shared/Loader.jsx";

export default function CategoryList() {
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [pagination, setPagination] = useState({
        page: 1,
        limit: 10,
        total: 0,
        maxPage: 1,
    });
    const navigate = useNavigate();

    useEffect(() => {
        const fetchCategories = async () => {
            try {
                const response = await getCategories(pagination.page, pagination.limit);
                setCategories(response.data || []);
                setPagination(prev => ({
                    ...prev,
                    total: response.meta?.pagination?.total || 0,
                    maxPage: response.meta?.pagination?.maxPage || 1,
                }));
            } catch (err) {
                setError(err.message || 'Failed to load categories');
            } finally {
                setLoading(false);
            }
        };

        fetchCategories();
    }, [pagination.page, pagination.limit]);

    const handlePageChange = (newPage) => {
        if (newPage >= 1 && newPage <= pagination.maxPage) {
            setPagination(prev => ({
                ...prev,
                page: newPage,
            }));
        }
    };

    const handleDelete = async (id, event) => {
        event.stopPropagation();
        if (window.confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) {
            try {
                await deleteCategory(id);
                setCategories(prev => prev.filter(category => category.id !== id));
            } catch (err) {
                setError(err.message || 'Failed to delete category');
            }
        }
    };
    if (loading) return <PageLoader />;
    if (error) return <div className="p-4 text-red-500">Erreur: {error}</div>;
    if (categories.length === 0) return <div className="p-4 text-center">Aucune catégorie trouvée</div>;

    return (
        <div className="bg-white shadow rounded-lg overflow-hidden">
            <div className="px-4 py-5 sm:px-6 flex justify-between items-center">
                <h3 className="text-lg font-medium text-gray-900">Catégories</h3>
                <Link
                    to="/categories/new"
                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
                >
                    Nouvelle Catégorie
                </Link>
            </div>
            <div className="border-t border-gray-200">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                    <tr>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                    {categories.map((category) => (
                        <tr key={category.id} onClick={() => navigate(`/categories/${category.id}`)} className="hover:bg-gray-50 cursor-pointer">
                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {category.name || 'N/A'}
                            </td>
                            <td className="px-6 py-4 text-sm text-gray-500">
                                {category.description?.substring(0, 50) || 'Aucune description'}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <Link
                                    to={`/categories/${category.id}/edit`}
                                    className="text-indigo-600 hover:text-indigo-900 mr-2"
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    Modifier
                                </Link>
                                <button
                                    onClick={(e) => handleDelete(category.id, e)}
                                    className="text-red-600 hover:text-red-900"
                                >
                                    Supprimer
                                </button>
                            </td>
                        </tr>
                    ))}
                    </tbody>
                </table>
            </div>
            <div className="px-4 py-3 flex justify-between items-center">
                <button
                    onClick={() => handlePageChange(pagination.page - 1)}
                    disabled={pagination.page <= 1}
                    className="px-4 py-2 border rounded text-sm font-medium text-gray-700 disabled:opacity-50"
                >
                    Précédent
                </button>
                <span>Page {pagination.page} sur {pagination.maxPage}</span>
                <button
                    onClick={() => handlePageChange(pagination.page + 1)}
                    disabled={pagination.page >= pagination.maxPage}
                    className="px-4 py-2 border rounded text-sm font-medium text-gray-700 disabled:opacity-50"
                >
                    Suivant
                </button>
            </div>
        </div>
    );
}