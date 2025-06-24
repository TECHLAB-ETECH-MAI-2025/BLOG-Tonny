import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { getArticles, toggleLike } from '../../api/article';
import { formatDate } from "../../utils/formatDate";
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faHeart as solidHeart } from '@fortawesome/free-solid-svg-icons';
import { faHeart as regularHeart } from '@fortawesome/free-regular-svg-icons';
import {PageLoader} from "../shared/Loader.jsx";

const colors = [
    'bg-purple-100 text-purple-800',
    'bg-pink-100 text-pink-800',
    'bg-yellow-100 text-yellow-800',
    'bg-blue-100 text-blue-800',
    'bg-indigo-100 text-indigo-800'
];

export default function HomePage() {
    const [articles, setArticles] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [pagination, setPagination] = useState({
        page: 1,
        limit: 9,
        total: 0,
        maxPage: 1,
    });

    useEffect(() => {
        const fetchArticles = async () => {
            try {
                const response = await getArticles(pagination.page, pagination.limit);
                setArticles(response.data.map(article => ({
                    ...article,
                    isLiked: article.isLiked || false,
                    likesCount: article.likesCount || 0
                })));
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

    const handleLike = async (articleId) => {
        try {
            const response = await toggleLike(articleId);
            setArticles(prev => prev.map(article => {
                if (article.id === articleId) {
                    return {
                        ...article,
                        isLiked: response.isLiked,
                        likesCount: response.likesCount
                    };
                }
                return article;
            }));
        } catch (err) {
            if (err.message.includes('401')) {
                setError('Vous devez être connecté pour liker un article');
            } else {
                setError(err.message);
            }
        }
    };

    const handlePageChange = (newPage) => {
        if (newPage >= 1 && newPage <= pagination.maxPage) {
            setPagination(prev => ({ ...prev, page: newPage }));
        }
    };

    if (loading) return <PageLoader />;
    if (error) return <div className="text-center py-8 text-red-500">Erreur: {error}</div>;

    return (
        <div className="container mx-auto py-8">
            <div className="blog-header text-center border-b-2 border-gray-200 mb-12 pb-4">
                <h1 className="text-3xl font-bold text-gray-800">Derniers articles</h1>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {articles.length > 0 ? (
                    articles.map((article, index) => (
                        <div key={index} className="bg-white rounded-xl shadow-sm hover:shadow-lg transition-shadow duration-300 ease-in-out overflow-hidden">
                            <div className="p-6">
                                <div className="flex justify-between items-center mb-4">
                                    <h2 className="text-xl font-semibold text-gray-800">{article.title}</h2>
                                    <span className="text-sm text-gray-500">
                                        {formatDate(article.createdAt)}
                                    </span>
                                </div>

                                <div className="mb-4">
                                    {article.categories?.map((category, index) => (
                                        <span
                                            key={index}
                                            className={`inline-block ${colors[index % colors.length]} text-xs px-3 py-1 rounded-lg mr-2`}
                                        >
                                            {category.name}
                                        </span>
                                    ))}
                                </div>

                                <p className="text-gray-600 mb-6">
                                    {article.content.length > 150
                                        ? `${article.content.substring(0, 150)}...`
                                        : article.content}
                                </p>

                                <div className="flex justify-between items-center mt-4">
                                    <Link
                                        to={`/articles/${article.id}`}
                                        className="text-blue-500 font-medium hover:underline inline-block"
                                    >
                                        Lire plus →
                                    </Link>
                                    <div className="flex items-center space-x-2">
                                        <button
                                            onClick={(e) => {
                                                e.preventDefault();
                                                handleLike(article.id);
                                            }}
                                            className="like-btn flex items-center justify-center p-2 rounded-full hover:bg-gray-100 transition-colors"
                                        >
                                            <FontAwesomeIcon
                                                icon={article.isLiked ? solidHeart : regularHeart}
                                                className={article.isLiked ? 'text-red-500' : 'text-gray-500'}
                                            />
                                        </button>
                                        <span className="like-count text-sm">
                                            {article.likesCount}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))
                ) : (
                    <div className="col-span-full p-8 text-center text-gray-500 border border-gray-200 rounded-lg">
                        <p>Aucun article disponible.</p>
                    </div>
                )}
            </div>

            {pagination.maxPage > 1 && (
                <div className="pagination-container flex justify-center items-center mt-12">
                    {pagination.page > 1 && (
                        <button
                            onClick={() => handlePageChange(pagination.page - 1)}
                            className="px-4 py-2 bg-gray-200 text-gray-700 border border-gray-300 rounded hover:bg-gray-300 hover:text-gray-800 transition-all duration-200"
                        >
                            Précédent
                        </button>
                    )}

                    {Array.from({ length: Math.min(5, pagination.maxPage) }, (_, i) => {
                        const pageNum = pagination.page <= 3
                            ? i + 1
                            : pagination.page >= pagination.maxPage - 2
                                ? pagination.maxPage - 4 + i
                                : pagination.page - 2 + i;

                        if (pageNum < 1 || pageNum > pagination.maxPage) return null;

                        return (
                            <button
                                key={pageNum}
                                onClick={() => handlePageChange(pageNum)}
                                className={`px-3 py-2 mx-1 min-w-10 text-center border rounded ${
                                    pageNum === pagination.page
                                        ? 'bg-gray-600 text-white border-gray-500'
                                        : 'bg-gray-200 text-gray-700 border-gray-300 hover:bg-gray-300 hover:text-gray-800'
                                } transition-all duration-200`}
                            >
                                {pageNum}
                            </button>
                        );
                    })}

                    {pagination.page < pagination.maxPage && (
                        <button
                            onClick={() => handlePageChange(pagination.page + 1)}
                            className="px-4 py-2 bg-gray-200 text-gray-700 border border-gray-300 rounded hover:bg-gray-300 hover:text-gray-800 transition-all duration-200"
                        >
                            Suivant
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}