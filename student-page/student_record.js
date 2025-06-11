document.addEventListener('DOMContentLoaded', function() {
    // --- Notification Dropdown Logic ---
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

    // --- Sanction Request Overlay Logic ---
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
                    // Optionally update the button text or re-disable it permanently if the request is successful and only one request is allowed.
                    requestButton.textContent = 'Request Sent';
                    // You might want to disable it permanently or reload the page to reflect the new state.
                    // For now, it just says 'Request Sent' but remains disabled by the server-side check on reload.
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
    
    // --- Theme Toggle Logic ---
    const themeToggle = document.getElementById('theme-checkbox');
    const currentTheme = localStorage.getItem('theme');

    function applyTheme(theme) {
        if (theme === 'dark-mode') {
            document.body.classList.add('dark-mode');
            if (themeToggle) themeToggle.checked = true; // Ensure toggle is checked
        } else {
            document.body.classList.remove('dark-mode');
            if (themeToggle) themeToggle.checked = false; // Ensure toggle is unchecked
        }
    }

    if (currentTheme) {
        applyTheme(currentTheme);
    } else {
        applyTheme('light-mode');
    }

    if (themeToggle) {
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

    // --- Tab Switching Logic ---
    const tabButtons = document.querySelectorAll('.tabs-navigation .tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTabId = this.dataset.tab; // Get the data-tab attribute value

            // Remove active classes from all buttons and content
            tabButtons.forEach(btn => btn.classList.remove('active-tab-button'));
            tabContents.forEach(content => content.classList.remove('active-tab'));

            // Add active class to the clicked button
            this.classList.add('active-tab-button');

            // Show the target tab content
            const targetTabContent = document.getElementById(targetTabId);
            if (targetTabContent) {
                targetTabContent.classList.add('active-tab');
            }
        });
    });

    // Optionally, if you want a default active tab on page load (though PHP handles this initially)
    // You could set the first tab as active if none are, but it's redundant with the initial PHP setup.
});
