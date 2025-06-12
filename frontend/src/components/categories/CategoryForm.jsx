import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { getCategory, createCategory, updateCategory } from '../../api/category';
import {PageLoader} from "../../Loader.jsx";

export default function CategoryForm() {
    const { id } = useParams();
    const navigate = useNavigate();
    const [category, setCategory] = useState({
        name: '',
        description: ''
    });
    const [loading, setLoading] = useState(!!id);
    const [error, setError] = useState('');

    useEffect(() => {
        if (id) {
            const fetchCategory = async () => {
                try {
                    const data = await getCategory(id);
                    setCategory({
                        name: data.name || '',
                        description: data.description || ''
                    });
                } catch (err) {
                    setError(err.message || 'Failed to load category');
                } finally {
                    setLoading(false);
                }
            };
            fetchCategory();
        } else {
            setLoading(false);
        }
    }, [id]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setCategory(prev => ({
            ...prev,
            [name]: value,
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            if (id) {
                await updateCategory(id, category);
            } else {
                await createCategory(category);
            }
            navigate('/categories');
        } catch (err) {
            setError(err.message || 'Failed to save category');
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return <PageLoader/>;
    }

    return (
        <div className="max-w-3xl mx-auto my-8 bg-white rounded-xl shadow-md overflow-hidden">
            <div className="px-6 py-4 border-b border-gray-200">
                <h3 className="text-xl font-semibold text-gray-800">
                    {id ? 'Modifier la catégorie' : 'Créer une nouvelle catégorie'}
                </h3>
            </div>

            {error && (
                <div className="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                    <p className="font-bold">Erreur</p>
                    <p>{error}</p>
                </div>
            )}

            <form onSubmit={handleSubmit} className="p-6 space-y-6">
                <div>
                    <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                        Nom *
                    </label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        value={category.name}
                        onChange={handleChange}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 border p-2"
                        required
                        minLength={2}
                        maxLength={255}
                    />
                </div>

                <div>
                    <label htmlFor="description" className="block text-sm font-medium text-gray-700">
                        Description
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows={4}
                        value={category.description}
                        onChange={handleChange}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 border p-2"
                        maxLength={500}
                    />
                </div>

                <div className="flex justify-end space-x-3">
                    <button
                        type="button"
                        onClick={() => navigate('/categories')}
                        className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Annuler
                    </button>
                    <button
                        type="submit"
                        className="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        disabled={loading}
                    >
                        {loading ? 'Enregistrement...' : 'Enregistrer'}
                    </button>
                </div>
            </form>
        </div>
    );
}