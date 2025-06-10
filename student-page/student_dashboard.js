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

    const searchInput = document.getElementById('handbook-search-input');
    const accordionItems = document.querySelectorAll('.accordion-container .accordion-item');
    const noResultsMessage = document.getElementById('no-results-message');

    if (searchInput && accordionItems.length > 0) {
        const originalContent = new Map();
        accordionItems.forEach(item => {
            const elementsToSearch = [item.querySelector('.accordion-header span'), ...item.querySelectorAll('.accordion-content li')];
            elementsToSearch.forEach(el => {
                if(el) originalContent.set(el, el.textContent);
            });
        });

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            let resultsFound = false;

            accordionItems.forEach(item => {
                const headerSpan = item.querySelector('.accordion-header span');
                const listItems = item.querySelectorAll('.accordion-content li');
                let isMatch = false;
                
                const highlight = (element) => {
                    const originalText = originalContent.get(element) || '';
                    if (searchTerm && originalText.toLowerCase().includes(searchTerm.toLowerCase())) {
                        isMatch = true;
                        const regex = new RegExp(searchTerm, 'gi');
                        element.innerHTML = originalText.replace(regex, match => `<mark>${match}</mark>`);
                    } else {
                        element.innerHTML = originalText;
                    }
                };

                highlight(headerSpan);
                listItems.forEach(li => highlight(li));

                if (isMatch || !searchTerm) {
                    item.style.display = 'block';
                    resultsFound = true;
                } else {
                    item.style.display = 'none';
                }
            });

            noResultsMessage.style.display = resultsFound ? 'none' : 'block';
        });
    }

    const themeToggle = document.getElementById('theme-checkbox');
    const currentTheme = localStorage.getItem('theme');

    function applyTheme(theme) {
        if (theme === 'dark-mode') {
            document.body.classList.add('dark-mode');
            themeToggle.checked = true;
        } else {
            document.body.classList.remove('dark-mode');
            themeToggle.checked = false;
        }
    }

    if (currentTheme) {
        applyTheme(currentTheme);
    } else {
        applyTheme('light-mode');
    }

    themeToggle.addEventListener('change', function() {
        if (this.checked) {
            document.body.classList.add('dark-mode');
            localStorage.setItem('theme', 'dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
            localStorage.setItem('theme', 'light-mode');
        }
    });
});