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

    const markAllReadButton = document.getElementById('markAllReadBtn');
    if (markAllReadButton) {
        markAllReadButton.addEventListener('click', function() {
            if (confirm('Are you sure you want to mark all notifications as read?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'mark_all_notifications_read.php';
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
});