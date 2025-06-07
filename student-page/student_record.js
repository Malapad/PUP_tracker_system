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

    // --- MODIFIED SANCTION REQUEST LOGIC ---
    const requestButton = document.getElementById("requestSanctionButtonWide");
    const overlay = document.getElementById("confirmationOverlayWide");
    const closeButton = document.getElementById("closeOverlayButtonWide");
    const overlayMessage = overlay ? overlay.querySelector('p') : null;

    if (requestButton) {
        requestButton.addEventListener("click", function() {
            if (this.disabled) return;

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

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
                // If successful, keep the button disabled to prevent re-submission
                if (data.success) {
                    requestButton.innerHTML = '<i class="fas fa-check"></i> Request Sent';
                } else {
                    requestButton.disabled = false; // Re-enable on failure
                    requestButton.innerHTML = 'Request Sanction';
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
                this.innerHTML = 'Request Sanction';
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
});