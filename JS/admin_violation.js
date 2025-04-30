/*document.addEventListener("DOMContentLoaded", function () {
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
*/

// Open modal when "Add Student" button is clicked
document.getElementById("addStudentBtn").addEventListener("click", function() {
    document.getElementById("modal").style.display = "block";
});

// Close modal when "Cancel" button is clicked
document.getElementById("closeModal").addEventListener("click", function() {
    document.getElementById("modal").style.display = "none"; 
});

// Handle the form submission and add student to the table
document.getElementById("studentForm").addEventListener("submit", function(event) {
    event.preventDefault();

    let studentNumber = document.getElementById("studentNumber").value;
    let lastName = document.getElementById("lastName").value;
    let firstName = document.getElementById("firstName").value;
    let middleName = document.getElementById("middleName").value;
    let program = document.getElementById("program").value;
    let yearSection = document.getElementById("yearSection").value;
    let violation = document.getElementById("violation").value;
    let date = document.getElementById("date").value || new Date().toISOString().split("T")[0];  // Default to today

    let table = document.getElementById("studentTableBody");

    let row = document.createElement("tr");
    row.classList.add('row-item');  // Add class to identify rows
    row.innerHTML = `
        <td>${studentNumber}</td>
        <td>${lastName}</td>
        <td>${firstName}</td>
        <td>${middleName}</td>
        <td>${program}</td>
        <td>${yearSection}</td>
        <td>${violation}</td>
        <td>${date}</td>
    `;

    table.appendChild(row);
    document.getElementById("modal").style.display = "none";
});

// Row click to display Edit and Delete buttons
document.getElementById("studentTableBody").addEventListener("click", function(event) {
    let target = event.target;
    let row = target.closest('tr');

    if (row && row.classList.contains('row-item')) {
        // Add selected-row class for hover effect
        document.querySelectorAll('.row-item').forEach(item => item.classList.remove('selected-row'));
        row.classList.add('selected-row');

        // Show Edit and Delete buttons
        document.getElementById("editRowBtn").style.display = "inline-block";
        document.getElementById("deleteRowBtn").style.display = "inline-block";

        // Edit button functionality
        document.getElementById("editRowBtn").onclick = function() {
            let cells = row.querySelectorAll('td');
            document.getElementById("studentNumber").value = cells[0].innerText;
            document.getElementById("lastName").value = cells[1].innerText;
            document.getElementById("firstName").value = cells[2].innerText;
            document.getElementById("middleName").value = cells[3].innerText;
            document.getElementById("program").value = cells[4].innerText;
            document.getElementById("yearSection").value = cells[5].innerText;
            document.getElementById("violation").value = cells[6].innerText;
            document.getElementById("date").value = cells[7].innerText;

            document.getElementById("modal").style.display = "block";
            document.getElementById("editRowBtn").style.display = "none";
            document.getElementById("deleteRowBtn").style.display = "none";
        };

        // Delete button functionality
        document.getElementById("deleteRowBtn").onclick = function() {
            row.remove();
            document.getElementById("editRowBtn").style.display = "none";
            document.getElementById("deleteRowBtn").style.display = "none";
        };
    }
});
