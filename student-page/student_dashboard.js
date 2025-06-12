document.addEventListener('DOMContentLoaded', function() {
    const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
    const primaryNav = document.querySelector('#primary-navigation');
    const notificationToggle = document.getElementById('notificationLinkToggle');
    const notificationsDropdown = document.getElementById('notificationsDropdownContent');
    const searchInput = document.getElementById('handbook-search-input');
    const accordionContainer = document.querySelector('.accordion-container');
    const noResultsMessage = document.getElementById('no-results-message');

    if (mobileNavToggle && primaryNav) {
        mobileNavToggle.addEventListener('click', () => {
            const isVisible = primaryNav.getAttribute('data-visible') === 'true';
            primaryNav.setAttribute('data-visible', !isVisible);
            mobileNavToggle.setAttribute('aria-expanded', !isVisible);
        });
    }

    if (notificationToggle && notificationsDropdown) {
        notificationToggle.addEventListener('click', function(event) {
            event.preventDefault();
            notificationsDropdown.classList.toggle('show');
        });
    }

    document.addEventListener('click', function(event) {
        if (notificationsDropdown && notificationsDropdown.classList.contains('show')) {
            if (!notificationToggle.contains(event.target) && !notificationsDropdown.contains(event.target)) {
                notificationsDropdown.classList.remove('show');
            }
        }
    });

    if (searchInput && accordionContainer) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim().toLowerCase();
            let anyCategoryVisible = false;

            const categories = accordionContainer.querySelectorAll('.accordion-item');

            categories.forEach(category => {
                const categoryHeader = category.querySelector('.accordion-header .category-name');
                const violationTypes = category.querySelectorAll('.violation-type-item');
                let isCategoryVisible = false;

                const categoryHeaderText = categoryHeader.textContent.trim().toLowerCase();
                if (categoryHeaderText.includes(searchTerm)) {
                    isCategoryVisible = true;
                }

                violationTypes.forEach(violation => {
                    const violationHeader = violation.querySelector('.violation-type-header');
                    const sanctions = violation.querySelectorAll('.sanction-item');
                    let isViolationVisible = false;

                    const violationHeaderText = violationHeader.textContent.trim().toLowerCase();
                    if (violationHeaderText.includes(searchTerm)) {
                        isViolationVisible = true;
                    }

                    sanctions.forEach(sanction => {
                        const sanctionText = sanction.textContent.trim().toLowerCase();
                        if (sanctionText.includes(searchTerm)) {
                            isViolationVisible = true;
                            sanction.style.display = '';
                        } else if (searchTerm) {
                            sanction.style.display = 'none';
                        } else {
                            sanction.style.display = '';
                        }
                    });

                    if (isViolationVisible) {
                        violation.style.display = 'block';
                        violation.open = !!searchTerm; 
                        isCategoryVisible = true;
                    } else {
                        violation.style.display = 'none';
                    }
                });

                if (isCategoryVisible) {
                    category.style.display = 'block';
                    category.open = !!searchTerm; 
                    anyCategoryVisible = true;
                } else {
                    category.style.display = 'none';
                }
            });

            if (noResultsMessage) {
                noResultsMessage.style.display = anyCategoryVisible ? 'none' : 'block';
            }
        });
    }

    const themeToggle = document.getElementById('theme-checkbox');
    const currentTheme = localStorage.getItem('theme');

    function applyTheme(theme) {
        if (theme === 'dark-mode') {
            document.body.classList.add('dark-mode');
            if(themeToggle) themeToggle.checked = true;
        } else {
            document.body.classList.remove('dark-mode');
            if(themeToggle) themeToggle.checked = false;
        }
    }

    if (currentTheme) {
        applyTheme(currentTheme);
    } else {
        applyTheme('light-mode');
    }

    if(themeToggle) {
        themeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light-mode');
            }
        });
    }
});