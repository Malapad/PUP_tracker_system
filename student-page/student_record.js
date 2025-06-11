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

    const requestButton = document.getElementById("requestSanctionButton");
    const overlay = document.getElementById("confirmationOverlay");
    const closeButton = document.getElementById("closeOverlayButton");
    const overlayMessage = overlay ? overlay.querySelector('p') : null;

    if (requestButton) {
        requestButton.addEventListener("click", function() {
            if (this.disabled) return;

            this.disabled = true;
            this.textContent = 'Submitting...';

            const formData = new FormData();
            formData.append('action', 'request_sanction');

            fetch('student_record.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (overlayMessage) {
                    overlayMessage.textContent = data.message;
                }
                if (overlay) {
                    overlay.style.display = "flex";
                }
                if (data.success) {
                    requestButton.textContent = 'Request Sent';
                } else {
                    requestButton.disabled = false;
                    requestButton.textContent = 'Request Sanction';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (overlayMessage) {
                    overlayMessage.textContent = 'An network error occurred. Please try again.';
                }
                if (overlay) {
                    overlay.style.display = "flex";
                }
                this.disabled = false;
                this.textContent = 'Request Sanction';
            });
        });
    }

    if (closeButton) {
        closeButton.addEventListener("click", function() {
            if (overlay) overlay.style.display = "none";
        });
    }

    if (overlay) {
        overlay.addEventListener("click", function(event) {
            if (event.target === overlay) {
                overlay.style.display = "none";
            }
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