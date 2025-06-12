import { BrowserRouter as Router } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext.jsx';
import Layout from './components/Layout';
import RouterConfig from './Router';

export default function App() {
    return (
        <Router>
            <AuthProvider>
                <Layout>
                    <RouterConfig />
                </Layout>
            </AuthProvider>
        </Router>
    );
}