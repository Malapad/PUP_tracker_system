document.addEventListener("DOMContentLoaded", function() {
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
