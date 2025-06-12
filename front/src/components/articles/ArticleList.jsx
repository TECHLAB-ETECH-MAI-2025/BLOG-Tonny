import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { getArticles, deleteArticle } from '../../api/article';
import {CardLoader, ListLoader, PageLoader} from "../../Loader.jsx";

export default function ArticleList() {
    const [articles, setArticles] = useState([]);
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
        const fetchArticles = async () => {
            try {
                const response = await getArticles(pagination.page, pagination.limit);
                setArticles(response.data);
                setPagination(prev => ({
                    ...prev,
                    total: response.meta.pagination.total,
                    maxPage: response.meta.pagination.maxPage,
                }));
            } catch (err) {
                setError(err.message);
            } finally {
                setLoading(false);
            }
        };

        fetchArticles();
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
        event.stopPropagation(); // Empêche la redirection lors du clic sur supprimer
        if (window.confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
            try {
                await deleteArticle(id);
                setArticles(articles.filter(article => article.id !== id));
            } catch (err) {
                setError(err.message);
            }
        }
    };

    if (loading) return <PageLoader />;
    if (error) return <div>Erreur: {error}</div>;

    return (
        <div className="bg-white shadow rounded-lg overflow-hidden">
            <div className="px-4 py-5 sm:px-6 flex justify-between items-center">
                <h3 className="text-lg font-medium text-gray-900">Articles</h3>
                <Link
                    to="/articles/new"
                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
                >
                    Nouveau Article
                </Link>
            </div>
            <div className="border-t border-gray-200">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                    <tr>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titre</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contenu</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date de création</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catégories</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                    {articles.map((article) => (
                        <tr key={article.id} onClick={() => navigate(`/articles/${article.id}`)} className="hover:bg-gray-50 cursor-pointer">
                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{article.title}</td>
                            <td className="px-6 py-4 text-sm text-gray-500">{article.content.substring(0, 50)}...</td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {new Date(article.createdAt).toLocaleDateString()}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {article.categories && article.categories.map((category, index) => (
                                    <span key={index} className="px-2 py-1 bg-gray-200 text-gray-700 rounded-full text-xs mr-1">
                                            {category.name}
                                        </span>
                                ))}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <Link to={`/articles/${article.id}/edit`} className="text-indigo-600 hover:text-indigo-900 mr-2" onClick={(e) => e.stopPropagation()}>Modifier</Link>
                                <button onClick={(e) => handleDelete(article.id, e)} className="text-red-600 hover:text-red-900">Supprimer</button>
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
