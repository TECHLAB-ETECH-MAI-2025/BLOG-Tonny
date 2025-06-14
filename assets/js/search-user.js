$(document).ready(function() {
    const searchInput = $('#search-input-user')
    searchInput.on('input', function() {
        const searchTerm = $(this).val().trim().toLowerCase();
        $('.conversation-item').each(function() {
            const username = $(this).find('.text-sm.font-medium.text-gray-900.truncate').text().toLowerCase();
            if (username.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
