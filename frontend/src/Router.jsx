import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from './context/AuthContext';
import ProtectedRoute from './components/ProtectedRoute';
import Login from "./components/auth/Login.jsx";
import Register from "./components/auth/Register.jsx";
import UserProfile from "./components/auth/UserProfile.jsx";
import UserForm from "./components/users/UserForm.jsx";
import ArticleList from "./components/articles/ArticleList.jsx";
import ArticleForm from "./components/articles/ArticleForm.jsx";
import ArticleDetail from "./components/articles/ArticleDetail.jsx";
import UserList from "./components/users/UserList.jsx";
import CategoryList from "./components/categories/CategoryList.jsx";
import CategoryDetail from "./components/categories/CategoryDetail.jsx";
import CategoryForm from "./components/categories/CategoryForm.jsx";
import HomePage from "./components/home/HomePage.jsx";
import NotFound from "./NotFound.jsx";
import Chat from "./components/chat/Chat.jsx";


const RouterConfig = () => {
    const { isAuthenticated, handleLogin, handleLogout } = useAuth();

    return (
        <Routes>
            <Route path="/" element={<HomePage />} />

            <Route
                path="/login"
                element={
                    isAuthenticated ? <Navigate to="/" /> : <Login onLogin={handleLogin} />
                }
            />

            <Route
                path="/register"
                element={isAuthenticated ? <Navigate to="/" /> : <Register />}
            />

            {/* Routes protégées */}
            <Route
                path="/profile"
                element={
                    <ProtectedRoute isAuthenticated={isAuthenticated}>
                        <UserProfile onLogout={handleLogout} />
                    </ProtectedRoute>
                }
            />

            <Route
                path="/users"
                element={
                    <ProtectedRoute isAuthenticated={isAuthenticated}>
                        <UserList />
                    </ProtectedRoute>
                }
            />

            <Route
                path="/users/new"
                element={
                    <ProtectedRoute isAuthenticated={isAuthenticated}>
                        <UserForm />
                    </ProtectedRoute>
                }
            />

            <Route
                path="/users/:id/edit"
                element={
                    <ProtectedRoute isAuthenticated={isAuthenticated}>
                        <UserForm />
                    </ProtectedRoute>
                }
            />

            {/* Routes publiques */}
            <Route path="/articles" element={<ArticleList />} />
            <Route path="/articles/:id" element={<ArticleDetail />} />

            {/* Routes protégées pour articles */}
            <Route
                path="/articles/new"
                element={
                    <ProtectedRoute isAuthenticated={isAuthenticated}>
                        <ArticleForm />
                    </ProtectedRoute>
                }
            />

            <Route
                path="/articles/:id/edit"
                element={
                    <ProtectedRoute isAuthenticated={isAuthenticated}>
                        <ArticleForm />
                    </ProtectedRoute>
                }
            />

            {/* Routes protégées pour catégories */}
            <Route
                path="/categories"
                element={
                    <ProtectedRoute isAuthenticated={isAuthenticated}>
                        <CategoryList />
                    </ProtectedRoute>
                }
            />

            <Route
                path="/categories/new"
                element={
                    <ProtectedRoute isAuthenticated={isAuthenticated}>
                        <CategoryForm />
                    </ProtectedRoute>
                }
            />

            <Route
                path="/categories/:id"
                element={
                    <ProtectedRoute isAuthenticated={isAuthenticated}>
                        <CategoryDetail />
                    </ProtectedRoute>
                }
            />

            <Route
                path="/categories/:id/edit"
                element={
                    <ProtectedRoute isAuthenticated={isAuthenticated}>
                        <CategoryForm />
                    </ProtectedRoute>
                }
            />
            <Route
                path="/chat"
                element={
                    <ProtectedRoute isAuthenticated={isAuthenticated}>
                        <Chat />
                    </ProtectedRoute>
                }
            />
            <Route path="*" element={<NotFound />} />
        </Routes>
    );
};

export default RouterConfig;