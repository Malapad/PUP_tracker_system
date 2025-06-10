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
                    if (data.success) {
                        if (notificationList) {
                            notificationList.innerHTML = '<li class="no-notifications">No new notifications.</li>';
                        }
                        if (notificationCountBadge) {
                            notificationCountBadge.style.display = 'none';
                        }
                        this.style.display = 'none';
                    }
                })
                .catch(error => console.error('Error marking all notifications as read:', error));
        });
    }

    const themeToggle = document.getElementById('theme-checkbox');
    if(themeToggle) {
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