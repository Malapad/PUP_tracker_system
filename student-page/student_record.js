document.addEventListener("DOMContentLoaded", function() {
    let offenseCount = document.querySelectorAll("#violationTable tr").length;
    let offenseCountSpan = document.getElementById("offenseCount");
    let requestButton = document.getElementById("requestButton");
    let overlay = document.getElementById("overlay");
    let closeOverlay = document.getElementById("closeOverlay");

    offenseCountSpan.textContent = offenseCount;

    if (offenseCount >= 2) {
        requestButton.classList.add("active");
        requestButton.disabled = false;
    }

    requestButton.addEventListener("click", function() {
        overlay.style.display = "flex";
    });

    closeOverlay.addEventListener("click", function() {
        overlay.style.display = "none";
    });
});
