import { Link, useLocation } from 'react-router-dom';
import { useState, useEffect, useRef } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faSearch,
    faTimes,
    faUser,
    faSignOutAlt
} from '@fortawesome/free-solid-svg-icons';
import { searchArticles } from "../api/search.js";
import {useAuth} from "../context/AuthContext.jsx";

export default function Layout({ children }) {
    const { isAuthenticated, handleLogout, user } = useAuth();
    const location = useLocation();
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [showResults, setShowResults] = useState(false);
    const searchRef = useRef(null);

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (searchRef.current && !searchRef.current.contains(event.target)) {
                setShowResults(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    const handleSearch = async (e) => {
        e.preventDefault();
        if (searchQuery.trim().length < 2) {
            setSearchResults([]);
            setShowResults(false);
            return;
        }

        try {
            const results = await searchArticles(searchQuery);
            setSearchResults(results);
            setShowResults(true);
        } catch (error) {
            console.error('Erreur de recherche:', error);
            setSearchResults([]);
            setShowResults(false);
        }
    };

    const clearSearch = () => {
        setSearchQuery('');
        setSearchResults([]);
        setShowResults(false);
    };

    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="bg-white shadow-sm">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-16">
                        {/* Partie gauche - Logo et navigation */}
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <Link to="/" className="text-xl font-bold text-indigo-600 hover:text-indigo-800">
                                    Blog
                                </Link>
                            </div>

                            {isAuthenticated && (
                                <div className="hidden sm:ml-6 sm:flex sm:space-x-4">
                                    <NavLink to="/" currentPath={location.pathname}>
                                        Accueil
                                    </NavLink>
                                    <NavLink to="/articles" currentPath={location.pathname}>
                                        Articles
                                    </NavLink>
                                    <NavLink to="/categories" currentPath={location.pathname}>
                                        Catégories
                                    </NavLink>
                                    <NavLink to="/users" currentPath={location.pathname}>
                                        Utilisateurs
                                    </NavLink>
                                </div>
                            )}
                        </div>

                        {/* Partie centrale - Recherche */}
                        <div className="flex-1 flex items-center justify-center px-2 lg:ml-6 lg:justify-end">
                            <div className="max-w-lg w-full lg:max-w-xs relative" ref={searchRef}>
                                <form onSubmit={handleSearch}>
                                    <label htmlFor="search" className="sr-only">Rechercher</label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <FontAwesomeIcon icon={faSearch} className="h-5 w-5 text-gray-400" />
                                        </div>
                                        <input
                                            id="search"
                                            name="search"
                                            className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            placeholder="Rechercher des articles..."
                                            type="search"
                                            value={searchQuery}
                                            onChange={(e) => setSearchQuery(e.target.value)}
                                            onFocus={() => searchQuery.length >= 2 && setShowResults(true)}
                                        />
                                        {searchQuery && (
                                            <button
                                                type="button"
                                                className="absolute inset-y-0 right-0 pr-3 flex items-center"
                                                onClick={clearSearch}
                                            >
                                                <FontAwesomeIcon icon={faTimes} className="h-5 w-5 text-gray-400 hover:text-gray-500" />
                                            </button>
                                        )}
                                    </div>
                                </form>

                                {showResults && (
                                    <div className="absolute z-10 mt-1 w-full bg-white shadow-lg rounded-md max-h-96 overflow-auto border border-gray-200">
                                        <div className="py-1">
                                            {searchResults.length > 0 ? (
                                                searchResults.map((result) => (
                                                    <Link
                                                        key={result.id}
                                                        to={result.url}
                                                        className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                        onClick={() => setShowResults(false)}
                                                    >
                                                        <div className="font-medium">{result.title}</div>
                                                        <div className="text-gray-500 truncate">{result.excerpt}</div>
                                                    </Link>
                                                ))
                                            ) : searchQuery.length >= 2 ? (
                                                <div className="px-4 py-2 text-sm text-gray-500">
                                                    Aucun résultat trouvé pour "{searchQuery}"
                                                </div>
                                            ) : null}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Partie droite - Authentification/Profil */}
                        <div className="hidden sm:ml-6 sm:flex sm:items-center">
                            {isAuthenticated ? (
                                <div className="relative group">
                                    <button className="flex items-center space-x-2 text-gray-600 hover:text-gray-900">
                                        <FontAwesomeIcon icon={faUser} className="h-5 w-5" />
                                        <span className='capitalize'>@{user?.username}</span>
                                    </button>

                                    <div className="absolute right-0 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block">
                                        <Link
                                            to="/profile"
                                            className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                        >
                                            Profil
                                        </Link>
                                        <Link
                                            to="/chat"
                                            className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                        >
                                            Chat
                                        </Link>
                                        <button
                                            onClick={handleLogout}
                                            className="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                        >
                                            <div className="flex items-center">
                                                <FontAwesomeIcon icon={faSignOutAlt} className="mr-2 text-red-500" />
                                                <span className="text-red-500">Déconnexion</span>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            ) : (
                                <div className="flex space-x-4">
                                    <Link
                                        to="/login"
                                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        Connexion
                                    </Link>
                                    <Link
                                        to="/register"
                                        className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        Inscription
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </nav>

            <main className="py-10">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {children}
                </div>
            </main>
        </div>
    );
}

function NavLink({ to, currentPath, children }) {
    return (
        <Link
            to={to}
            className={`px-3 py-2 rounded-md text-sm font-medium ${
                currentPath === to
                    ? 'bg-indigo-100 text-indigo-700'
                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
            }`}
        >
            {children}
        </Link>
    );
}