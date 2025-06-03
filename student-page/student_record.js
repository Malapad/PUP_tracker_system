document.addEventListener("DOMContentLoaded", function() {
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

    const requestButton = document.getElementById("requestSanctionButtonWide");
    const overlay = document.getElementById("confirmationOverlayWide");
    const closeButton = document.getElementById("closeOverlayButtonWide");

    if (requestButton) {
        requestButton.addEventListener("click", function() {
            if (!this.disabled) {
                if (overlay) overlay.style.display = "flex";
            }
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
});