document.addEventListener("DOMContentLoaded", function() {
    const modal = document.getElementById("modal");
    const addStudentBtn = document.getElementById("add-student");
    const closeModal = document.querySelector(".close");
    const studentForm = document.getElementById("student-form");
    const studentTable = document.getElementById("student-table");

    addStudentBtn.addEventListener("click", function() {
        modal.style.display = "flex";
    });

    closeModal.addEventListener("click", function() {
        modal.style.display = "none";
    });

    studentForm.addEventListener("submit", function(event) {
        event.preventDefault();

        const studentNumber = document.getElementById("student-number").value;
        const firstName = document.getElementById("first-name").value;
        const middleName = document.getElementById("middle-name").value;
        const lastName = document.getElementById("last-name").value;
        const email = document.getElementById("email").value;
        const course = document.getElementById("course").value;
        const year = document.getElementById("year").value;
        const section = document.getElementById("section").value;

        const newRow = document.createElement("tr");
        newRow.innerHTML = `
            <td>${studentNumber}</td>
            <td>${lastName}</td>
            <td>${firstName}</td>
            <td>${middleName}</td>
            <td>${email}</td>
            <td>${course}</td>
            <td>${year}</td>
            <td>${section}</td>
            <td>Inactive</td>
            <td>
                <button class="edit-btn">Edit</button>
                <button class="save-btn" disabled>Save</button>
            </td>
        `;

        studentTable.appendChild(newRow);
        modal.style.display = "none";
        alert("Student successfully added!");
    });

    studentTable.addEventListener("click", function(event) {
        if (event.target.classList.contains("edit-btn")) {
            const row = event.target.parentElement.parentElement;
            const saveBtn = row.querySelector(".save-btn");
            saveBtn.disabled = false;
        }

        if (event.target.classList.contains("save-btn")) {
            event.target.disabled = true;
            alert("Changes saved successfully!");
        }
    });

    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    };
});
