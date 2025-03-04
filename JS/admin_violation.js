document.addEventListener("DOMContentLoaded", function () {
    const table = document.querySelector("table tbody");
    const addButton = document.querySelector(".add");
    const editButton = document.querySelector(".edit");
    const deleteButton = document.querySelector(".delete");

    // Add a new row
    addButton.addEventListener("click", function () {
        const studentNumber = prompt("Enter Student Number:");
        const studentName = prompt("Enter Student Name:");
        const program = prompt("Enter Program:");
        const violation = prompt("Enter Violation:");
        const date = prompt("Enter Date (MM/DD/YY):");

        if (studentNumber && studentName && program && violation && date) {
            const newRow = document.createElement("tr");
            newRow.innerHTML = `
                <td>${studentNumber}</td>
                <td>${studentName}</td>
                <td>${program}</td>
                <td>${violation}</td>
                <td>${date}</td>
            `;
            table.appendChild(newRow);
        } else {
            alert("All fields are required!");
        }
    });

    // Edit a selected row
    editButton.addEventListener("click", function () {
        const rows = document.querySelectorAll("table tbody tr");
        let rowIndex = prompt("Enter row number to edit (starting from 1):");
        rowIndex = parseInt(rowIndex) - 1;

        if (rowIndex >= 0 && rowIndex < rows.length) {
            const cells = rows[rowIndex].children;
            cells[0].textContent = prompt("Edit Student Number:", cells[0].textContent);
            cells[1].textContent = prompt("Edit Student Name:", cells[1].textContent);
            cells[2].textContent = prompt("Edit Program:", cells[2].textContent);
            cells[3].textContent = prompt("Edit Violation:", cells[3].textContent);
            cells[4].textContent = prompt("Edit Date:", cells[4].textContent);
        } else {
            alert("Invalid row number!");
        }
    });

    // Delete a selected row
    deleteButton.addEventListener("click", function () {
        const rows = document.querySelectorAll("table tbody tr");
        let rowIndex = prompt("Enter row number to delete (starting from 1):");
        rowIndex = parseInt(rowIndex) - 1;

        if (rowIndex >= 0 && rowIndex < rows.length) {
            rows[rowIndex].remove();
        } else {
            alert("Invalid row number!");
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const table = document.querySelector("table tbody");

    // Function to load data from localStorage
    function loadViolationEntry() {
        const savedEntry = localStorage.getItem("violationEntry");

        if (savedEntry) {
            const data = JSON.parse(savedEntry);

            // Create a new row and insert data
            const newRow = document.createElement("tr");
            newRow.innerHTML = `
                <td>${data.studentNumber}</td>
                <td>${data.fullName}</td>
                <td>${data.courseYear}</td>
                <td>${data.violation}</td>
                <td>${data.date}</td>
            `;

            table.appendChild(newRow);

            // Clear the saved entry after adding to prevent duplication
            localStorage.removeItem("violationEntry");
        }
    }

    // Load violation entry if available
    loadViolationEntry();
});
