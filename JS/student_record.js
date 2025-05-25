/*document.addEventListener("DOMContentLoaded", function() {
    let offenseCount = document.querySelectorAll("#violationTable tr").length;
    let offenseCountSpan = document.getElementById("offenseCount");
    let requestButton = document.getElementById("requestButton");
    let overlay = document.getElementById("overlay");
    let closeOverlay = document.getElementById("closeOverlay");

    // Update offense count
    offenseCountSpan.textContent = offenseCount;

    // Enable button if offense count is 2 or more
    if (offenseCount >= 2) {
        requestButton.classList.add("active");
        requestButton.disabled = false;
    }

    // Show overlay on button click
    requestButton.addEventListener("click", function() {
        overlay.style.display = "flex";
    });

    // Hide overlay on close button click
    closeOverlay.addEventListener("click", function() {
        overlay.style.display = "none";
    });
});
*/

document.addEventListener("DOMContentLoaded", function () {
    const tableBody = document.querySelector(".violation-table tbody");
    const violationRows = tableBody ? tableBody.querySelectorAll("tr").length : 0;

    const requestButton = document.getElementById("requestButton");
    const offenseCountSpan = document.getElementById("offenseCount");
    const overlay = document.getElementById("overlay");
    const closeOverlay = document.getElementById("closeOverlay");

    // Update offense count display if element exists
    if (offenseCountSpan) {
        offenseCountSpan.textContent = violationRows;
    }

    // Enable request button if offenses are 2 or more
    if (requestButton && violationRows >= 2) {
        requestButton.disabled = false;
        requestButton.classList.add("active");
    }

    // Show overlay
    if (requestButton) {
        requestButton.addEventListener("click", () => {
            if (overlay) overlay.style.display = "flex";
        });
    }

    // Close overlay
    if (closeOverlay) {
        closeOverlay.addEventListener("click", () => {
            overlay.style.display = "none";
        });
    }

    // Optional: Handle "Back to Violation Summary" button
    const backBtn = document.querySelector(".back-button");
    if (backBtn) {
        backBtn.addEventListener("click", () => {
            window.location.href = "student_record.html"; // Adjust this URL as needed
        });
    }
});
