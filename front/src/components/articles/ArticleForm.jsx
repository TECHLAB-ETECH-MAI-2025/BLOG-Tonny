import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { getArticle, createArticle, updateArticle } from '../../api/article';
import { getCategories } from '../../api/category';
import {PageLoader} from "../shared/Loader.jsx";

export default function ArticleForm() {
    const { id } = useParams();
    const navigate = useNavigate();
    const [article, setArticle] = useState({
        title: '',
        content: '',
        categories: []
    });
    const [allCategories, setAllCategories] = useState([]);
    const [loading, setLoading] = useState(!!id);
    const [error, setError] = useState('');

    useEffect(() => {
        const loadCategories = async () => {
            try {
                const response = await getCategories();
                setAllCategories(response.data);
            } catch (err) {
                setError('Erreur lors du chargement des catégories');
            }
        };

        loadCategories().then();

        if (id) {
            const fetchArticle = async () => {
                try {
                    const data = await getArticle(id);
                    setArticle({
                        title: data.title,
                        content: data.content,
                        categories: data.categories ? data.categories.map(c => c.id || c) : []
                    });
                } catch (err) {
                    setError(err.message || 'Erreur lors du chargement de l\'article');
                } finally {
                    setLoading(false);
                }
            };
            fetchArticle();
        } else {
            setLoading(false);
        }
    }, [id]);

/*   useEffect(() => {
        console.log(article);
    }, [article]);
*/
    const handleChange = (e) => {
        const { name, value } = e.target;
        setArticle(prev => ({
            ...prev,
            [name]: value,
        }));
    };

    const handleCategoryToggle = (categoryId) => {
        setArticle(prev => ({
            ...prev,
            categories: prev.categories.includes(categoryId)
                ? prev.categories.filter(id => id !== categoryId)
                : [...prev.categories, categoryId]
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            if (id) {
                await updateArticle(id, article);
            } else {
                await createArticle(article);
            }
            navigate('/articles');
        } catch (err) {
            setError(err.message || 'Erreur lors de la sauvegarde de l\'article');
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
                    {id ? 'Modifier l\'article' : 'Créer un nouvel article'}
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
                    <label htmlFor="title" className="block text-sm font-medium text-gray-700">
                        Titre
                    </label>
                    <input
                        type="text"
                        name="title"
                        id="title"
                        value={article.title}
                        onChange={handleChange}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 border p-2"
                        required
                    />
                </div>

                <div>
                    <label htmlFor="content" className="block text-sm font-medium text-gray-700">
                        Contenu
                    </label>
                    <textarea
                        id="content"
                        name="content"
                        rows={10}
                        value={article.content}
                        onChange={handleChange}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 border p-2"
                        required
                    />
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                        Catégories
                    </label>
                    <div className="flex flex-wrap gap-2">
                        {allCategories.map(category => (
                            <button
                                key={category.id}
                                type="button"
                                onClick={() => handleCategoryToggle(category.id)}
                                className={`px-3 py-1 rounded-full text-sm font-medium transition-colors ${
                                    article.categories.includes(category.id)
                                        ? 'bg-indigo-600 text-white hover:bg-indigo-700 ring-2 ring-indigo-300'
                                        : 'bg-gray-100 text-gray-800 hover:bg-gray-200'
                                }`}
                            >
                                {category.name}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="flex justify-end space-x-3">
                    <button
                        type="button"
                        onClick={() => navigate('/articles')}
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
