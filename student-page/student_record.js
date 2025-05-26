document.addEventListener("DOMContentLoaded", function () {
    const tableBody = document.querySelector(".violation-table tbody");
    const violationRows = tableBody ? tableBody.querySelectorAll("tr").length : 0;

    const requestButton = document.getElementById("requestButton");
    const offenseCountSpan = document.getElementById("offenseCount");
    const overlay = document.getElementById("overlay");
    const closeOverlay = document.getElementById("closeOverlay");

    if (offenseCountSpan) {
        offenseCountSpan.textContent = violationRows;
    }

    if (requestButton && violationRows >= 2) {
        requestButton.disabled = false;
        requestButton.classList.add("active");
    }

    if (requestButton) {
        requestButton.addEventListener("click", () => {
            if (overlay) overlay.style.display = "flex";
        });
    }

    if (closeOverlay) {
        closeOverlay.addEventListener("click", () => {
            overlay.style.display = "none";
        });
    }

    const backBtn = document.querySelector(".back-button");
    if (backBtn) {
        backBtn.addEventListener("click", () => {
            window.location.href = "student_record.html";
        });
    }
});
