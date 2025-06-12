import { Link } from 'react-router-dom';
import { Home, ArrowLeft } from 'lucide-react';

const NotFound = () => {
    return (
        <div className="min-h-screen bg-gradient-to-b from-blue-50 to-white flex items-center justify-center px-4">
            <div className="text-center max-w-md mx-auto">
                {/* Animation 404 */}
                <div className="mb-8">
                    <div className="text-8xl font-bold text-blue-600 mb-4 animate-bounce">
                        404
                    </div>
                    <div className="w-32 h-1 bg-gradient-to-r from-blue-400 to-purple-500 mx-auto rounded-full"></div>
                </div>

                <h1 className="text-3xl font-bold text-gray-800 mb-4">
                    Page non trouvée
                </h1>
                <p className="text-gray-600 mb-8 leading-relaxed">
                    Oups ! La page que vous recherchez semble avoir disparu dans le cyberespace.
                    Elle a peut-être été déplacée, supprimée ou n'a jamais existé.
                </p>

                <div className="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link
                        to="/"
                        className="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-lg hover:shadow-xl"
                    >
                        <Home className="w-5 h-5 mr-2" />
                        Retour à l'accueil
                    </Link>

                    <button
                        onClick={() => window.history.back()}
                        className="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors duration-200"
                    >
                        <ArrowLeft className="w-5 h-5 mr-2" />
                        Page précédente
                    </button>
                </div>
            </div>
        </div>
    );
};

export default NotFound;