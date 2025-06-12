import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { getCategory } from '../../api/category';

export default function CategoryDetail() {
    const { id } = useParams();
    const [category, setCategory] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        const fetchCategory = async () => {
            try {
                const data = await getCategory(id);
                setCategory(data);
            } catch (err) {
                setError(err.message || 'Failed to load category');
            } finally {
                setLoading(false);
            }
        };
        fetchCategory();
    }, [id]);

    if (loading) return <div className="p-4 text-center">Chargement en cours...</div>;
    if (error) return <div className="p-4 text-red-500">Erreur: {error}</div>;
    if (!category) return <div className="p-4 text-center">Catégorie non trouvée</div>;

    return (
        <div className="bg-white shadow rounded-lg overflow-hidden">
            <div className="px-4 py-5 sm:px-6 flex justify-between items-center">
                <div>
                    <h3 className="text-lg font-medium text-gray-900">{category.name || 'N/A'}</h3>
                </div>
                <Link
                    to={`/categories/${category.id}/edit`}
                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
                >
                    Modifier
                </Link>
            </div>
            <div className="border-t border-gray-200 px-4 py-5 sm:p-0">
                <div className="px-4 py-5 sm:px-6">
                    <p className="text-gray-700">
                        {category.description || 'Aucune description disponible'}
                    </p>
                </div>
            </div>
        </div>
    );
}