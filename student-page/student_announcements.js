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

    const modal = document.getElementById('announcementModal');
    if (!modal) return;

    const modalTitle = document.getElementById('modalTitle');
    const modalMeta = document.getElementById('modalMeta');
    const modalContent = document.getElementById('modalContent');
    const closeBtn = document.querySelector('.announcement-close-btn');
    
    let readAnnouncements = JSON.parse(localStorage.getItem('readAnnouncements')) || [];
    const announcementCards = document.querySelectorAll('.announcement-card');

    announcementCards.forEach(card => {
        const announcementId = card.getAttribute('data-id');
        if (!readAnnouncements.includes(announcementId)) {
            card.classList.add('unread');
        }
    });

    function openModal() {
        modal.classList.add('show');
    }

    function closeModal() {
        modal.classList.remove('show');
    }

    closeBtn.addEventListener('click', closeModal);
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            closeModal();
        }
    });

    announcementCards.forEach(card => {
        card.addEventListener('click', function() {
            const announcementId = this.getAttribute('data-id');

            if (!readAnnouncements.includes(announcementId)) {
                readAnnouncements.push(announcementId);
                localStorage.setItem('readAnnouncements', JSON.stringify(readAnnouncements));
                this.classList.remove('unread');
            }

            modalTitle.textContent = 'Loading...';
            modalMeta.innerHTML = '';
            modalContent.textContent = '';
            openModal();

            fetch(`student_announcements.php?action=get_announcement&id=${announcementId}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const data = result.data;
                        modalTitle.textContent = data.title;
                        modalMeta.innerHTML = `
                            <span class="meta-item"><svg class="meta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 6c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2m0 10c2.7 0 5.8 1.29 6 2H6c.23-.72 3.31-2 6-2m0-12C9.79 4 8 5.79 8 8s1.79 4 4 4 4-1.79-4-4-1.79-4-4-4zm0 10c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg> By ${data.author_name || 'Admin'}</span>
                            <span class="meta-item"><svg class="meta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg> ${data.created_at_formatted}</span>
                        `;
                        modalContent.textContent = data.content;
                    } else {
                        modalTitle.textContent = 'Error';
                        modalContent.textContent = 'Could not load the announcement.';
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    modalTitle.textContent = 'Error';
                    modalContent.textContent = 'An error occurred while fetching the announcement.';
                });
        });
    });
});