import 'datatables.net';
import 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';

export function initArticlesDataTable() {
    $(document).ready(function () {
        // Etape 1 : Charger dynamiquement les catégories pour le filtre
        $.ajax({
            url: '/api/categories',
            type: 'GET',
            success: function (categories) {
                const categoryFilter = $('#category-filter');
                // Option "Toutes les catégories"
                categoryFilter.append('<option value="">Toutes les catégories</option>');
                // Toutes les autres catégories
                categories.forEach(function (category) {
                    categoryFilter.append(`<option value="${category.id}">${category.name}</option>`);
                });
                // Initialiser la table une fois les filtres prêts
                initDataTable();
            },
            error: function (xhr) {
                console.error('Erreur lors du chargement des catégories:', xhr.responseText);
                initDataTable();
            }
        });

        /**
         * Initialisation du DataTable avec style Tailwind.
         */
        function initDataTable() {
            const table = $('#articles-datatable').DataTable({
                responsive: true,
                processing: true,
                serverSide: true, // Côté serveur (API Symfony)
                ajax: {
                    url: '/api/articles',
                    type: 'GET',
                    data: function (d) {
                        d.categoryId = $('#category-filter').val();
                        return d;
                    }
                },
                columns: [
                    {data: 'id'},
                    {
                        data: 'title',
                        className: 'px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900'
                    },
                    {
                        data: 'content',
                        // Rendu limité à 100 caractères avec "..."
                        render: function (data) {
                            return data.length > 100 ? data.substr(0, 100) + '...' : data;
                        },
                        className: 'px-4 py-2 text-sm text-gray-500'
                    },
                    {
                        data: 'categories',
                        // Affichage des catégories sous forme de badges
                        render: function (data) {
                            if (!data) return '';
                            return data.map(cat =>
                                `<span class="p-1 m-1 inline-flex text-xs leading-5 font-semibold rounded-md bg-${getCategoryColor(cat.id)}-100 text-${getCategoryColor(cat.id)}-800">
                                    ${cat.name}
                                </span>`
                            ).join(' ');
                        },
                        orderable: false,
                        className: 'px-4 py-2'
                    },
                    {
                        data: 'createdAt',
                        render: function (data) {
                            const date = new Date(data);
                            return date.toLocaleDateString('fr-FR');
                        },
                        className: 'px-4 py-2 whitespace-nowrap text-sm text-gray-500'
                    },
                    {
                        data: 'id',
                        render: function (data) {
                            return `
                                <div class="flex space-x-2">
                                    <a href="/article/${data}" class="text-indigo-600 hover:text-indigo-900 font-medium transition duration-150">
                                        <i class="fas fa-eye mr-1"></i> Voir
                                    </a>
                                    <a href="/article/${data}/edit" class="text-blue-600 hover:text-blue-900 font-medium transition duration-150">
                                        <i class="fas fa-edit mr-1"></i> Éditer
                                    </a>
                                </div>
                            `;
                        },
                        orderable: false,
                        className: 'px-4 py-2 whitespace-nowrap text-sm'
                    }
                ],
                language: {
                    "emptyTable": "Aucune donnée disponible dans le tableau",
                    "info": "Affichage de _START_ à _END_ sur _TOTAL_ entrées",
                    "infoEmpty": "Affichage de 0 à 0 sur 0 entrées",
                    "infoFiltered": "(filtrées depuis _MAX_ entrées totales)",
                    "lengthMenu": "Afficher _MENU_ entrées",
                    "loadingRecords": "Chargement...",
                    "processing": "Traitement en cours...",
                    "search": "Rechercher:",
                    "zeroRecords": "Aucun résultat trouvé",
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
                },
                // Callback à l'initialisation
                initComplete: function () {
                    $('.dataTables_wrapper .dataTables_filter input').addClass('border border-gray-300 rounded-md py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:border-zinc-500 ml-2');
                    $('.dataTables_wrapper .dataTables_length select').addClass('border border-gray-300 rounded-md py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:border-zinc-500 mx-2');
                    $('.dataTables_paginate .paginate_button').addClass('px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 mx-1');
                    $('.dataTables_paginate .paginate_button.current').removeClass('bg-white').addClass('bg-zinc-600 text-white hover:bg-zinc-700');
                }
            });

            // Fonction utilitaire pour attribuer des couleurs aux catégories
            function getCategoryColor(categoryId) {
                // Assigner des couleurs différentes selon l'ID de catégorie
                const colors = ['red', 'blue', 'purple', 'pink', 'indigo', 'teal'];
                return colors[categoryId % colors.length];
            }

            // Événements pour les filtres
            $('#category-filter').on('change', function () {
                table.ajax.reload();
            });

            $('#reset-filters').on('click', function () {
                $('#category-filter').val('');
                table.ajax.reload();
            });

            // Gestionnaire pour le bouton de suppression
            $(document).on('click', '.delete-article', function(e) {
                e.preventDefault();
                const articleId = $(this).data('id');

                if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
                    $.ajax({
                        url: `/app_article_delete/${articleId}`,
                        type: 'DELETE',
                        success: function() {
                            table.ajax.reload();
                        },
                        error: function(xhr) {
                            console.error('Erreur lors de la suppression:', xhr.responseText);
                            alert('Une erreur est survenue lors de la suppression de l\'article.');
                        }
                    });
                }
            });
        }
    });
}

initArticlesDataTable();
