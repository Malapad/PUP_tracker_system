document.addEventListener('DOMContentLoaded', function() {
    const notificationLinkToggle = document.getElementById('notificationLinkToggle');
    const notificationsDropdownContent = document.getElementById('notificationsDropdownContent');

    if (notificationLinkToggle && notificationsDropdownContent) {
        notificationLinkToggle.addEventListener('click', function(event) {
            event.preventDefault();
            notificationsDropdownContent.classList.toggle('show');
        });
    }

    document.addEventListener('click', function(event) {
        if (notificationsDropdownContent && notificationLinkToggle) {
            if (!notificationLinkToggle.contains(event.target) && !notificationsDropdownContent.contains(event.target)) {
                notificationsDropdownContent.classList.remove('show');
            }
        }
    });

    const handbookSearchInputElem = document.getElementById('handbookSearchInput');
    if (handbookSearchInputElem) {
        handbookSearchInputElem.addEventListener('input', handbookSearch);
        
        const handbookSearchForm = document.getElementById('handbookSearchForm');
        if (handbookSearchForm) {
            handbookSearchForm.addEventListener('submit', function(event) {
                event.preventDefault();
                handbookSearch();
            });
        }
    }
});

function handbookSearch() {
    const searchTerm = document.getElementById('handbookSearchInput').value.toLowerCase();
    const list = document.getElementById('handbookViolationList');
    const listItems = list.querySelectorAll('li.handbook-item');
    let foundItems = 0;

    listItems.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            item.style.display = '';
            foundItems++;
        } else {
            item.style.display = 'none';
        }
    });

    let noResultsMessage = list.querySelector('.no-handbook-items-message.search-specific');
    const originalNoItemsMessage = list.querySelector('.no-handbook-items-message:not(.search-specific)');

    if (foundItems === 0 && searchTerm) {
        if (!noResultsMessage) {
            noResultsMessage = document.createElement('li');
            noResultsMessage.className = 'no-handbook-items-message search-specific';
            list.appendChild(noResultsMessage);
        }
        noResultsMessage.textContent = 'No violation types match your search.';
        noResultsMessage.style.display = '';
        if (originalNoItemsMessage) originalNoItemsMessage.style.display = 'none';
    } else {
        if (noResultsMessage) {
            noResultsMessage.style.display = 'none';
        }
        if (!searchTerm && originalNoItemsMessage && listItems.length === 0) {
             originalNoItemsMessage.style.display = '';
        } else if (!searchTerm && originalNoItemsMessage && listItems.length > 0) {
            originalNoItemsMessage.style.display = 'none';
        }
    }
}