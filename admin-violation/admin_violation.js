document.getElementById("addViolationBtn").addEventListener("click", function() {
    document.getElementById("modal").style.display = "block";
});

document.getElementById("closeModal").addEventListener("click", function() {
    document.getElementById("modal").style.display = "none";
    document.getElementById("violationForm").reset();
});

window.addEventListener("click", function(e) {
    const modal = document.getElementById("modal");
    if (e.target == modal) {
        modal.style.display = "none";
        document.getElementById("violationForm").reset();
    }
});

document.getElementById("searchInput").addEventListener("keyup", function() {
    let filter = this.value.toLowerCase();
    let tableBody = document.getElementById("violationTableBody");
    let rows = tableBody.getElementsByTagName("tr");

    for (let i = 0; i < rows.length; i++) {
        let cells = rows[i].getElementsByTagName("td");
        let match = false;

        for (let j = 0; j < cells.length - 1; j++) {
            if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                match = true;
                break;
            }
        }
        rows[i].style.display = match ? "" : "none";
    }
});
