document.addEventListener("DOMContentLoaded", function() {
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