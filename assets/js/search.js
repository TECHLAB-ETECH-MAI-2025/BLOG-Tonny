class Search {
    constructor() {
        this.$searchInput = $('#search-input');
        this.$searchResults = $('#search-results');
        this.$searchForm = $('#search-form');
        this.init();
    }

    init() {
        if (this.$searchInput.length) {
            this.setupEventListeners();
        }
    }
    /*
     * Mise en place des events
     */
    setupEventListeners() {
        this.$searchInput.on('input', this.handleInput.bind(this));
        $(document).on('click', this.handleDocumentClick.bind(this));

        if (this.$searchForm.length) {
            this.$searchForm.on('submit', this.handleFormSubmit.bind(this));
        }
    }
    /*
    * Écoute sur la barre de recherche
     */
    handleInput(e) {
        const query = $(e.target).val().trim();

        if (query.length < 2) {
            this.hideResults();
            return;
        }

        this.fetchResults(query);
    }

    fetchResults(query) {
        //
        $.ajax({
            url: `/search?q=${encodeURIComponent(query)}`,
            method: 'GET',
            dataType: 'json',
            success: (data) => this.displayResults(data),
            error: (error) => {
                console.error('Error fetching search results:', error);
                this.displayError();
            }
        });
    }

    displayResults(data) {
        if (data.length > 0) {
            const resultsHtml = data.map(item => `
                <a href="${item.url}" class="block px-4 py-2 hover:bg-gray-100 border-b border-gray-100">
                    <div class="font-semibold">${item.title}</div>
                    <div class="text-sm text-gray-500">${item.excerpt}</div>
                </a>
            `).join('');

            this.$searchResults.html(resultsHtml);
            this.showResults();
        } else {
            this.$searchResults.html(`
                <div class="px-4 py-2 text-gray-500">
                    Aucun résultat pour "${this.$searchInput.val()}"
                </div>
            `);
            this.showResults();
        }
    }

    displayError() {
        this.$searchResults.html(`
            <div class="px-4 py-2 text-red-500">
                Error loading search results. Please try again.
            </div>
        `);
        this.showResults();
    }

    handleDocumentClick(e) {
        if (!this.$searchInput.is(e.target) &&
            !this.$searchResults.is(e.target) &&
            this.$searchResults.has(e.target).length === 0) {
            this.hideResults();
        }
    }

    showResults() {
        this.$searchResults
            .removeClass('hidden')
            .addClass('block');
    }

    hideResults() {
        this.$searchResults
            .addClass('hidden')
            .removeClass('block');
    }
}

$(document).ready(() => {
    new Search();
});