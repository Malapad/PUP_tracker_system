document.addEventListener('DOMContentLoaded', function() {
    const notificationLinkToggle = document.getElementById('notificationLinkToggle');
    const notificationsDropdownContent = document.getElementById('notificationsDropdownContent');
    const markAllReadBtn = document.getElementById('mark-all-read-btn');
    const notificationList = document.querySelector('.notification-list');
    const notificationCountBadge = document.querySelector('.notification-count');

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
    
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            fetch('mark_all_notifications_read.php', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success && notificationList) {
                        notificationList.innerHTML = '<li class="no-notifications">No new notifications.</li>';
                        if (notificationCountBadge) {
                            notificationCountBadge.style.display = 'none';
                        }
                        this.style.display = 'none';
                    }
                })
                .catch(error => console.error('Error marking all notifications as read:', error));
        });
    }

    const searchInput = document.getElementById('handbook-search-input');
    if (searchInput) {
        const accordionItems = document.querySelectorAll('.accordion-container .accordion-item');
        const noResultsMessage = document.getElementById('no-results-message');

        if (accordionItems.length > 0) {
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

                if(noResultsMessage) noResultsMessage.style.display = resultsFound ? 'none' : 'block';
            });
        }
    }

    const requestButton = document.getElementById("requestSanctionButton");
    if(requestButton) {
        const overlay = document.getElementById("confirmationOverlay");
        const closeButton = document.getElementById("closeOverlayButton");
        const overlayMessage = overlay ? overlay.querySelector('p') : null;

        requestButton.addEventListener("click", function() {
            if (this.disabled) return;
            this.disabled = true;
            this.textContent = 'Submitting...';
            const formData = new FormData();
            formData.append('action', 'request_sanction');
            fetch('student_record.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (overlayMessage) { overlayMessage.textContent = data.message; }
                if (overlay) { overlay.style.display = "flex"; }
                if (data.success) { requestButton.textContent = 'Request Sent'; } 
                else { requestButton.disabled = false; requestButton.textContent = 'Request Sanction'; }
            })
            .catch(error => {
                console.error('Error:', error);
                if (overlayMessage) { overlayMessage.textContent = 'An network error occurred. Please try again.';}
                if (overlay) { overlay.style.display = "flex"; }
                this.disabled = false; this.textContent = 'Request Sanction';
            });
        });
        if (closeButton) { closeButton.addEventListener("click", function() { if (overlay) overlay.style.display = "none"; }); }
        if (overlay) { overlay.addEventListener("click", function(event) { if (event.target === overlay) { overlay.style.display = "none"; } }); }
    }
    
    const modal = document.getElementById('announcementModal');
    if (modal) {
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

        function openModal() { modal.classList.add('show'); }
        function closeModal() { modal.classList.remove('show'); }

        if(closeBtn) closeBtn.addEventListener('click', closeModal);
        window.addEventListener('click', function(event) { if (event.target == modal) { closeModal(); } });

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
                            modalMeta.innerHTML = `<span class="meta-item"><svg class="meta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 6c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2m0 10c2.7 0 5.8 1.29 6 2H6c.23-.72 3.31-2 6-2m0-12C9.79 4 8 5.79 8 8s1.79 4 4 4 4-1.79-4-4-1.79-4-4-4zm0 10c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg> By ${data.author_name || 'Admin'}</span><span class="meta-item"><svg class="meta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg> ${data.created_at_formatted}</span>`;
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
    }

    const signOutButton = document.getElementById("signOutBtn");
    if (signOutButton) {
        signOutButton.addEventListener("click", function(event) {
            event.preventDefault(); 
            window.location.href = this.href;
        });
    }

    const markAllReadPageBtn = document.getElementById('markAllReadPageBtn');
    if(markAllReadPageBtn) {
        markAllReadPageBtn.addEventListener('click', function() {
            fetch('mark_all_notifications_read.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            })
            .catch(error => console.error('Error on marking all as read:', error));
        });
    }
});