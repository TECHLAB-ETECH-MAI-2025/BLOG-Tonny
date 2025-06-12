import { Loader2 } from 'lucide-react';

// Spinner simple
export const LoadingSpinner = ({ size = 'md', color = 'blue' }) => {
    const sizeClasses = {
        sm: 'w-4 h-4',
        md: 'w-8 h-8',
        lg: 'w-12 h-12',
        xl: 'w-16 h-16'
    };

    const colorClasses = {
        blue: 'text-blue-600',
        gray: 'text-gray-600',
        white: 'text-white',
        green: 'text-green-600'
    };

    return (
        <Loader2
            className={`${sizeClasses[size]} ${colorClasses[color]} animate-spin`}
        />
    );
};

// Loading pour toute la page
export const PageLoader = ({ message = "Chargement..." }) => {
    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-50">
            <div className="text-center">
                <div className="mb-4">
                    <LoadingSpinner size="xl" />
                </div>
                <p className="text-gray-600 font-medium">{message}</p>
            </div>
        </div>
    );
};

// Loading pour les cartes/sections
export const CardLoader = ({ height = '200px' }) => {
    return (
        <div
            className="bg-white rounded-lg shadow-md border border-gray-200 animate-pulse"
            style={{ height }}
        >
            <div className="p-6 space-y-4">
                <div className="h-4 bg-gray-300 rounded w-3/4"></div>
                <div className="h-4 bg-gray-300 rounded w-1/2"></div>
                <div className="h-4 bg-gray-300 rounded w-5/6"></div>
            </div>
        </div>
    );
};

// Loading pour les listes
export const ListLoader = ({ items = 5 }) => {
    return (
        <div className="space-y-4">
            {Array.from({ length: items }).map((_, i) => (
                <div key={i} className="animate-pulse">
                    <div className="flex items-center space-x-4 p-4 bg-white rounded-lg shadow-sm border">
                        <div className="w-12 h-12 bg-gray-300 rounded-full"></div>
                        <div className="flex-1 space-y-2">
                            <div className="h-4 bg-gray-300 rounded w-3/4"></div>
                            <div className="h-3 bg-gray-300 rounded w-1/2"></div>
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
};

// Loading pour les boutons
export const ButtonLoader = ({
                                 children,
                                 loading = false,
                                 disabled = false,
                                 className = '',
                                 ...props
                             }) => {
    return (
        <button
            disabled={loading || disabled}
            className={`relative inline-flex items-center justify-center ${className} ${
                loading || disabled ? 'opacity-75 cursor-not-allowed' : ''
            }`}
            {...props}
        >
            {loading && (
                <LoadingSpinner
                    size="sm"
                    color="white"
                />
            )}
            <span className={loading ? 'ml-2' : ''}>
                {children}
            </span>
        </button>
    );
};

// Loading pour le contenu inline
export const InlineLoader = ({ message = "Chargement..." }) => {
    return (
        <div className="flex items-center justify-center py-8">
            <LoadingSpinner size="md" />
            <span className="ml-3 text-gray-600">{message}</span>
        </div>
    );
};

export default LoadingSpinner;