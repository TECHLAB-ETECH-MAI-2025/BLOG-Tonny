import 'datatables.net';
import 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';

export function initArticlesDataTable(showDeleted = false) {
    $(document).ready(function () {
        // Charger dynamiquement les catégories pour le filtre
        $.ajax({
            url: '/get/categories',
            type: 'GET',
            success: function (categories) {
                const categoryFilter = $('#category-filter');
                categoryFilter.empty();
                categoryFilter.append('<option value="">Toutes les catégories</option>');
                categories.forEach(function (category) {
                    categoryFilter.append(`<option value="${category.id}">${category.name}</option>`);
                });
                initDataTable();
            },
            error: function (xhr) {
                console.error('Erreur lors du chargement des catégories:', xhr.responseText);
                initDataTable();
            }
        });

        /**
         * Initialisation du DataTable avec gestion du soft delete
         */
        function initDataTable() {
            // Définir l'URL en fonction du type d'affichage
            const apiUrl = showDeleted ? '/get/articles/deleted' : '/get/articles';

            const table = $('#articles-datatable').DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                ajax: {
                    url: apiUrl,
                    type: 'GET',
                    data: function (d) {
                        if (!showDeleted) {
                            d.categoryId = $('#category-filter').val();
                            d.includeDeleted = $('#include-deleted-filter').is(':checked');
                        }
                        return d;
                    },
                    error: function(xhr, error, code) {
                        console.error('Erreur AJAX:', xhr.responseText);
                        alert('Erreur lors du chargement des données');
                    }
                },
                columns: getColumnsConfig(),
                language: {
                    url: "https://cdn.datatables.net/plug-ins/2.3.1/i18n/fr-FR.json",
                    "paginate": {
                        "first": "<<",
                        "last": ">>",
                        "next": ">",
                        "previous": "<"
                    }
                },
                dom: '<"flex flex-col md:flex-row items-center justify-between mb-4"<"flex-1"f><"flex items-center space-x-2"l>>rtip',
                createdRow: function(row, data, dataIndex) {
                    $(row).addClass('hover:bg-gray-50 transition duration-150');
                    // Marquer visuellement les articles supprimés
                    if (data.isDeleted) {
                        $(row).addClass('bg-red-50 text-gray-500');
                    }
                },
                initComplete: function () {
                    // Styling des éléments DataTables
                    $('.dataTables_wrapper .dataTables_filter input').addClass('border border-gray-300 rounded-md py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:border-zinc-500 ml-2');
                    $('.dataTables_wrapper .dataTables_length select').addClass('border border-gray-300 rounded-md py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:border-zinc-500 mx-2');
                    $('.dataTables_paginate .paginate_button').addClass('px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 mx-1');
                    $('.dataTables_paginate .paginate_button.current').removeClass('bg-white').addClass('bg-zinc-600 text-white hover:bg-zinc-700');
                }
            });

            // Configuration des colonnes selon le contexte
            function getColumnsConfig() {
                const baseColumns = [
                    {data: 'id'},
                    {
                        data: 'title',
                        className: 'px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900',
                        render: function(data, type, row) {
                            if (row.isDeleted) {
                                return `<span class="line-through text-gray-500">${data}</span>`;
                            }
                            return data;
                        }
                    },
                    {
                        data: 'content',
                        render: function (data, type, row) {
                            const content = data.length > 100 ? data.substr(0, 100) + '...' : data;
                            if (row.isDeleted) {
                                return `<span class="text-gray-400">${content}</span>`;
                            }
                            return content;
                        },
                        className: 'px-4 py-2 text-sm text-gray-500'
                    },
                    {
                        data: 'categories',
                        render: function (data, type, row) {
                            if (!data) return '';
                            const opacity = row.isDeleted ? 'opacity-50' : '';
                            return data.map(cat =>
                                `<span class="p-1 m-1 inline-flex text-xs leading-5 font-semibold rounded-md bg-${getCategoryColor(cat.id)}-100 text-${getCategoryColor(cat.id)}-800 ${opacity}">
                                    ${cat.name}
                                </span>`
                            ).join(' ');
                        },
                        orderable: false,
                        className: 'px-4 py-2'
                    },
                    {
                        data: 'createdAt',
                        render: function (data, type, row) {
                            const date = new Date(data);
                            const formattedDate = date.toLocaleDateString('fr-FR');
                            if (row.isDeleted) {
                                return `<span class="text-gray-400">${formattedDate}</span>`;
                            }
                            return formattedDate;
                        },
                        className: 'px-4 py-2 whitespace-nowrap text-sm text-gray-500'
                    }
                ];

                // Colonne d'actions
                baseColumns.push({
                    data: 'id',
                    render: function (data, type, row) {
                        return getActionsHtml(data, row);
                    },
                    orderable: false,
                    className: 'px-4 py-2 whitespace-nowrap text-sm'
                });

                return baseColumns;
            }

            // Génération du HTML des actions selon le contexte
            function getActionsHtml(articleId, row) {
                if (showDeleted || row.isDeleted) {

                    // Actions pour articles supprimés
                    return `
                        <div class="flex space-x-2">
                            <button class="restore-article text-green-600 hover:text-green-900 font-medium transition duration-150" data-id="${articleId}">
                                <i class="fas fa-undo mr-1"></i> Restaurer
                            </button>
                            <button class="permanent-delete-article text-red-600 hover:text-red-900 font-medium transition duration-150" data-id="${articleId}">
                                <i class="fas fa-trash mr-1"></i> Supprimer définitivement
                            </button>
                        </div>
                    `;
                } else {
                    // Actions pour articles actifs
                    return `
                        <div class="flex space-x-2">
                            <a href="/admin/article/${articleId}" class="text-indigo-600 hover:text-indigo-900 font-medium transition duration-150">
                                <i class="fas fa-eye mr-1"></i> Voir
                            </a>
                            <a href="/admin/article/${articleId}/edit" class="text-blue-600 hover:text-blue-900 font-medium transition duration-150">
                                <i class="fas fa-edit mr-1"></i> Éditer
                            </a>
                            <button class="delete-article text-red-600 hover:text-red-900 font-medium transition duration-150" data-id="${articleId}">
                                <i class="fas fa-trash mr-1"></i> Supprimer
                            </button>
                        </div>
                    `;
                }
            }

            // Fonction utilitaire pour les couleurs des catégories
            function getCategoryColor(categoryId) {
                const colors = ['red', 'blue', 'purple', 'pink', 'indigo', 'teal'];
                return colors[categoryId % colors.length];
            }

            // Événements pour les filtres catégories
            if (!showDeleted) {
                $('#category-filter').on('change', function () {
                    table.ajax.reload();
                });

                $('#include-deleted-filter').on('change', function () {
                    table.ajax.reload();
                });

                $('#reset-filters').on('click', function () {
                    $('#category-filter').val('');
                    $('#include-deleted-filter').prop('checked', false);
                    table.ajax.reload();
                });
            }

            // Gestionnaires d'événements pour les actions
            setupEventHandlers(table);
        }

        /**
         * Configuration des gestionnaires d'événements
         */
        function setupEventHandlers(table) {
            // Suppression (soft delete)
            $(document).on('click', '.delete-article', function(e) {
                e.preventDefault();
                const articleId = $(this).data('id');

                if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
                    $.ajax({
                        url: `/get/articles/${articleId}/delete`,
                        type: 'DELETE',
                        success: function(response) {
                            if (response.success) {
                                table.ajax.reload();
                                showNotification('success', response.message);
                            } else {
                                showNotification('error', response.message);
                            }
                        },
                        error: function(xhr) {
                            console.error('Erreur lors de la suppression:', xhr.responseText);
                            showNotification('error', 'Une erreur est survenue lors de la suppression.');
                        }
                    });
                }
            });

            // Restauration
            $(document).on('click', '.restore-article', function(e) {
                e.preventDefault();
                const articleId = $(this).data('id');

                if (confirm('Êtes-vous sûr de vouloir restaurer cet article ?')) {
                    $.ajax({
                        url: `/get/articles/${articleId}/restore`,
                        type: 'POST',
                        success: function(response) {
                            if (response.success) {
                                table.ajax.reload();
                                showNotification('success', response.message);
                            } else {
                                showNotification('error', response.message);
                            }
                        },
                        error: function(xhr) {
                            console.error('Erreur lors de la restauration:', xhr.responseText);
                            showNotification('error', 'Une erreur est survenue lors de la restauration.');
                        }
                    });
                }
            });

            // Suppression définitive
            $(document).on('click', '.permanent-delete-article', function(e) {
                e.preventDefault();
                const articleId = $(this).data('id');

                if (confirm('ATTENTION : Cette action est irréversible !\n\nÊtes-vous sûr de vouloir supprimer définitivement cet article ?')) {
                    $.ajax({
                        url: `/get/articles/${articleId}/permanent-delete`,
                        type: 'DELETE',
                        success: function(response) {
                            if (response.success) {
                                table.ajax.reload();
                                showNotification('success', response.message);
                            } else {
                                showNotification('error', response.message);
                            }
                        },
                        error: function(xhr) {
                            console.error('Erreur lors de la suppression définitive:', xhr.responseText);
                            showNotification('error', 'Une erreur est survenue lors de la suppression définitive.');
                        }
                    });
                }
            });
        }

        /**
         * Affichage des notifications
         */
        function showNotification(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const notification = $(`
                <div class="alert ${alertClass} alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);

            $('body').append(notification);

            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 5000);
        }
    });
}

initArticlesDataTable();
