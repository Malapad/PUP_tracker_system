/* document.addEventListener("DOMContentLoaded", function () {
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
}); */




document.getElementById("addStudentBtn").addEventListener("click", function() {
    document.getElementById("modal").style.display = "block";
});

document.getElementById("closeModal").addEventListener("click", function() {
    document.getElementById("modal").style.display = "none";
});

document.getElementById("studentForm").addEventListener("submit", function(event) {
    event.preventDefault();

    let studentNumber = document.getElementById("studentNumber").value;
    let lastName = document.getElementById("lastName").value;
    let firstName = document.getElementById("firstName").value;
    let middleName = document.getElementById("middleName").value;
    let program = document.getElementById("program").value;
    let yearSection = document.getElementById("yearSection").value;
    let violation = document.getElementById("violation").value;
    let date = document.getElementById("date").value;

    let table = document.getElementById("studentTableBody");

    let row = document.createElement("tr");
    row.innerHTML = `
        <td>${studentNumber}</td>
        <td>${lastName}</td>
        <td>${firstName}</td>
        <td>${middleName}</td>
        <td>${program}</td>
        <td>${yearSection}</td>
        <td>${violation}</td>
        <td>${date}</td>
        <td>
            <button class="edit">Edit</button>
            <button class="save" disabled>Save</button>
        </td>
    `;

    table.appendChild(row);
    document.getElementById("modal").style.display = "none";
});

// Edit and Save functionality
document.getElementById("studentTableBody").addEventListener("click", function(event) {
    let target = event.target;

    if (target.classList.contains("edit")) {
        let row = target.parentElement.parentElement;
        let cells = row.querySelectorAll("td:not(:last-child)");
        
        cells.forEach((cell, index) => {
            if (index > 0) {
                let input = document.createElement("input");
                input.type = "text";
                input.value = cell.innerText;
                cell.innerHTML = "";
                cell.appendChild(input);
            }
        });

        let saveButton = row.querySelector(".save");
        saveButton.disabled = false;
    }

    if (target.classList.contains("save")) {
        let row = target.parentElement.parentElement;
        let inputs = row.querySelectorAll("input");

        let isConfirmed = confirm("Are you sure you want to save?");
        if (isConfirmed) {
            inputs.forEach((input, index) => {
                let cell = input.parentElement;
                cell.innerText = input.value;
            });

            target.disabled = true;
        }
    }
});