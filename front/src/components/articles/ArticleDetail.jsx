import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { getArticle } from '../../api/article';
import { getComments, createComment } from '../../api/comment';
import { formatDate } from "../../utils/formatDate.js";
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faChevronDown, faChevronUp } from '@fortawesome/free-solid-svg-icons';
import {PageLoader} from "../shared/Loader.jsx";

const colors = [
    'bg-purple-100 text-purple-800',
    'bg-pink-100 text-pink-800',
    'bg-yellow-100 text-yellow-800',
    'bg-blue-100 text-blue-800',
    'bg-indigo-100 text-indigo-800'
];

export default function ArticleDetail() {
    const { id } = useParams();
    const [article, setArticle] = useState(null);
    const [comments, setComments] = useState([]);
    const [showComments, setShowComments] = useState(true);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [commentForm, setCommentForm] = useState({
        author: '',
        content: ''
    });
    const [commentError, setCommentError] = useState('');

    useEffect(() => {
        const fetchData = async () => {
            try {
                const [articleData, commentsData] = await Promise.all([
                    getArticle(id),
                    getComments(id)
                ]);
                setArticle(articleData);
                setComments(commentsData);
            } catch (err) {
                setError(err.message);
            } finally {
                setLoading(false);
            }
        };

        fetchData();
    }, [id]);

    const handleCommentSubmit = async (e) => {
        e.preventDefault();
        setCommentError('');

        if (!commentForm.author || !commentForm.content) {
            setCommentError('Veuillez remplir tous les champs');
            return;
        }

        try {
            const newComment = await createComment({
                ...commentForm,
                articleId: id
            });

            setComments(prev => [newComment, ...prev]);
            setCommentForm({
                author: '',
                content: ''
            });
        } catch (err) {
            setCommentError(err.message || 'Erreur lors de l\'envoi du commentaire');
        }
    };

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setCommentForm(prev => ({
            ...prev,
            [name]: value
        }));
    };

    if (loading) return <PageLoader/>;
    if (error) return <div className="text-center py-8 text-red-500">Erreur: {error}</div>;
    if (!article) return <div className="text-center py-8">Article non trouvé</div>;

    return (
        <div className="container mx-auto py-6 px-4 max-w-4xl">
            <div className="bg-white rounded-xl shadow-md overflow-hidden mb-6">
                <div className="p-6">
                    <div className="flex justify-between items-start mb-4">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-800">{article.title}</h1>
                            <p className="text-sm text-gray-500 mt-1">
                                Publié le {formatDate(article.createdAt)}
                            </p>
                        </div>
                        <div className="flex space-x-2">
                            {article.categories?.map((category, index) => (
                                <span
                                    key={index}
                                    className={`inline-block ${colors[index % colors.length]} text-xs px-3 py-1 rounded-lg`}
                                >
                                    {category.name}
                                </span>
                            ))}
                        </div>
                    </div>

                    <div className="prose max-w-none text-gray-700 mt-4">
                        {article.content}
                    </div>
                </div>
            </div>

            {/* Section Commentaires */}
            <div className="bg-white rounded-xl shadow-md overflow-hidden">
                <div className="p-6">
                    <div
                        className="flex justify-between items-center cursor-pointer mb-4"
                        onClick={() => setShowComments(!showComments)}
                    >
                        <h2 className="text-xl font-semibold text-gray-800 flex items-center">
                            <FontAwesomeIcon
                                icon={showComments ? faChevronUp : faChevronDown}
                                className="mr-2 text-blue-500"
                            />
                            Commentaires ({comments.length})
                        </h2>
                    </div>

                    {showComments && (
                        <>
                            <div className="space-y-4 mb-6">
                                {comments.length > 0 ? (
                                    comments.map(comment => (
                                        <div key={comment.id} className="border-l-4 border-blue-500 pl-4 py-2 bg-gray-50 rounded-r">
                                            <div className="font-medium text-gray-800">{comment.author}</div>
                                            <p className="text-gray-600">{comment.content}</p>
                                            <p className="text-xs text-gray-400 mt-1">
                                                {formatDate(comment.createdAt)}
                                            </p>
                                        </div>
                                    ))
                                ) : (
                                    <div className="text-center text-gray-500 py-4">
                                        Aucun commentaire pour cet article.
                                    </div>
                                )}
                            </div>

                            <div className="border-t pt-4">
                                <h3 className="font-semibold text-gray-800 mb-3">Ajouter un commentaire</h3>
                                {commentError && (
                                    <div className="text-red-500 text-sm mb-3">{commentError}</div>
                                )}
                                <form onSubmit={handleCommentSubmit}>
                                    <div className="mb-4">
                                        <label htmlFor="author" className="block text-sm font-medium text-gray-700 mb-1">
                                            Votre nom
                                        </label>
                                        <input
                                            type="text"
                                            id="author"
                                            name="author"
                                            value={commentForm.author}
                                            onChange={handleInputChange}
                                            className="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            required
                                        />
                                    </div>
                                    <div className="mb-4">
                                        <label htmlFor="content" className="block text-sm font-medium text-gray-700 mb-1">
                                            Votre commentaire
                                        </label>
                                        <textarea
                                            id="content"
                                            name="content"
                                            rows="3"
                                            value={commentForm.content}
                                            onChange={handleInputChange}
                                            className="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            required
                                        ></textarea>
                                    </div>
                                    <div className="flex justify-end">
                                        <button
                                            type="submit"
                                            className="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
                                        >
                                            Publier
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}