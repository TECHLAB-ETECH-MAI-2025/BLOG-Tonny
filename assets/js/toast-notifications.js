class ToastNotification {
    constructor() {
        this.container = null;
        this.init();
    }

    init() {
        if (!document.getElementById('toast-container')) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'fixed z-50 top-4 right-4 flex flex-col space-y-3';
            document.body.appendChild(this.container);
        } else {
            this.container = document.getElementById('toast-container');
        }
    }

    show(message, type = 'info', duration = 5000) {
        const toast = document.createElement('div');
        toast.className = `toast-notification transform transition-all duration-300 ease-in-out 
                          w-80 max-w-full bg-white rounded-lg shadow-lg overflow-hidden
                          border-l-4 ${this.getBorderColor(type)}`;

        const icon = this.getIcon(type);
        const bgColor = this.getBackgroundColor(type);

        toast.innerHTML = `
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0 pt-0.5">
                        <div class="h-5 w-5 ${bgColor} rounded-full flex items-center justify-center">
                            ${icon}
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-gray-800">
                            ${message}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button class="toast-close inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Animation d'entrée
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.cursor = 'pointer';
        toast.addEventListener('click', () => {
            window.location.href = '/chat';
            this.hide(toast);
        });
        this.container.appendChild(toast);

        // Force le reflow pour déclencher l'animation
        void toast.offsetWidth;

        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';

        // Gestion de la fermeture
        const closeButton = toast.querySelector('.toast-close');
        closeButton.addEventListener('click', () => {
            this.hide(toast);
        });

        // Auto-fermeture
        if (duration > 0) {
            setTimeout(() => {
                this.hide(toast);
            }, duration);
        }

        return toast;
    }

    hide(toast) {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';

        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }

    getBorderColor(type) {
        switch (type) {
            case 'success': return 'border-green-500';
            case 'error': return 'border-red-500';
            case 'warning': return 'border-yellow-500';
            case 'info':
            default: return 'border-blue-500';
        }
    }

    getBackgroundColor(type) {
        switch (type) {
            case 'success': return 'text-green-500';
            case 'error': return 'text-red-500';
            case 'warning': return 'text-yellow-500';
            case 'info':
            default: return 'text-blue-500';
        }
    }

    getIcon(type) {
        switch (type) {
            case 'success':
                return `<svg fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>`;
            case 'error':
                return `<svg fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>`;
            case 'warning':
                return `<svg fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>`;
            case 'info':
            default:
                return `<svg fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>`;
        }
    }

    // Méthodes utilitaires
    success(message, duration = 5000) {
        return this.show(message, 'success', duration);
    }

    error(message, duration = 5000) {
        return this.show(message, 'error', duration);
    }

    warning(message, duration = 5000) {
        return this.show(message, 'warning', duration);
    }

    info(message, duration = 5000) {
        return this.show(message, 'info', duration);
    }
}

// Instance globale
window.Toast = new ToastNotification();