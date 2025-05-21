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

//Updated JS 21/05/25 -------------------------------------------------------------------------
const categorySelect = document.getElementById("violationCategory");
const typeSelect = document.getElementById("violationType");
const otherCategoryInput = document.getElementById("otherCategoryInput");
const otherTypeInput = document.getElementById("otherTypeInput");
const successMsg = document.createElement("div");
successMsg.classList.add("success-msg");
document.body.appendChild(successMsg);

// Violation mapping
const violationMap = {
    "ID": ["Not wearing ID", "Loss ID", "Late Validation of ID"],
    "Registration Card": ["Loss Registration Card", "Late Validation of COR"],
    "Prohibited Clothes": ["Wearing Croptop", "Wearing Sando", "Wearing Rip Jeans", "Wearing Leggings", "Wearing Crocs", "Wearing Slippers"],
    "Hair": ["Hair Color"]
};

// Populate violation type based on category
categorySelect.addEventListener("change", function () {
    const selected = this.value;
    typeSelect.innerHTML = "";
    otherCategoryInput.style.display = selected === "Others" ? "block" : "none";
    otherTypeInput.style.display = selected === "Others" ? "block" : "none";
    typeSelect.style.display = selected === "Others" ? "none" : "block";

    if (violationMap[selected]) {
        violationMap[selected].forEach(type => {
            const option = document.createElement("option");
            option.value = type;
            option.textContent = type;
            typeSelect.appendChild(option);
        });
    }
});

// Trigger success message
function showSuccessMessage(text, isEdit = false) {
    successMsg.textContent = text;
    successMsg.classList.toggle("edit", isEdit);
    successMsg.style.display = "block";
    setTimeout(() => successMsg.style.display = "none", 2000);
}

// History Modal
document.getElementById("historyBtn").addEventListener("click", () => {
    document.getElementById("historyModal").style.display = "block";
});
document.getElementById("closeHistory").addEventListener("click", () => {
    document.getElementById("historyModal").style.display = "none";
});

// Close the history modal when clicking outside the modal content
window.addEventListener("click", function(event) {
    const historyModal = document.getElementById("historyModal");
    const historyContent = document.getElementById("historyModalContent");

    if (event.target === historyModal) {
        historyModal.style.display = "none";
    }
});


// Updated Form Submission
document.getElementById("studentForm").addEventListener("submit", function (event) {
    event.preventDefault();

    const table = document.getElementById("studentTableBody");
    const studentNumber = document.getElementById("studentNumber").value;
    const lastName = document.getElementById("lastName").value;
    const firstName = document.getElementById("firstName").value;
    const middleName = document.getElementById("middleName").value;
    const program = document.getElementById("program").value;
    const yearSection = document.getElementById("yearSection").value;
    const date = document.getElementById("date").value || new Date().toISOString().split("T")[0];
    const category = categorySelect.value === "Others" ? otherCategoryInput.value : categorySelect.value;
    const types = categorySelect.value === "Others" ? [otherTypeInput.value] : Array.from(typeSelect.selectedOptions).map(opt => opt.value);
    const remarks = document.getElementById("remarks").value;

    const row = document.createElement("tr");
    row.classList.add('row-item');
    row.innerHTML = `
        <td>${studentNumber}</td>
        <td>${lastName}</td>
        <td>${firstName}</td>
        <td>${middleName}</td>
        <td>${program}</td>
        <td>${yearSection}</td>
        <td>${category}</td>
        <td>${types.join(", ")}</td>
        <td>1st</td>
        <td>${date}</td>
        <td>${remarks}</td>
    `;

    table.appendChild(row);
    document.getElementById("modal").style.display = "none";
    this.reset();
    showSuccessMessage("✔ Successfully added");
});
