document.addEventListener("DOMContentLoaded", function () {
    let studentForm = document.getElementById("student-form");
    let studentTable = document.getElementById("student-table");
    let modal = document.getElementById("modal");
    let editModal = document.getElementById("edit-modal");

    document.getElementById("add-student").addEventListener("click", function () {
        modal.classList.add("show");
    });

    document.querySelector(".close").addEventListener("click", function () {
        modal.classList.remove("show");
    });

    studentForm.addEventListener("submit", function (event) {
        event.preventDefault();

        let password = document.getElementById("password").value;
        let confirmPassword = document.getElementById("confirm-password").value;
        
        if (password !== confirmPassword) {
            alert("Passwords do not match!");
            return;
        }

        let formData = new FormData(this);

        fetch("add_student.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log("Server Response:", data);

            if (data.success) {
                let newRow = document.createElement("tr");
                newRow.innerHTML = `
                    <td>${data.student_number}</td>
                    <td>${data.last_name}</td>
                    <td>${data.first_name}</td>
                    <td>${data.middle_name}</td>
                    <td>${data.email}</td>
                    <td>${data.course}</td>
                    <td>${data.year}</td>
                    <td>${data.section}</td>
                    <td>${data.gender}</td>
                    <td>${data.status}</td>
                    <td>
                        <button class='edit-btn' data-student='${JSON.stringify(data)}'>Edit</button>
                        <button class='delete-btn'>Delete</button>
                    </td>
                `;
                studentTable.appendChild(newRow);

                modal.classList.remove("show");
                studentForm.reset();
            } else {
                alert("Error: " + data.error);
            }
        })
        .catch(error => {
            console.error("Fetch Error:", error);
            alert("Network error: Check console for details.");
        });
    });

    studentTable.addEventListener("click", function (e) {
        if (e.target && e.target.classList.contains("edit-btn")) {
            const btn = e.target;
            const student = JSON.parse(btn.getAttribute("data-student"));
    
            document.getElementById("edit-student-number").value = student.student_number;
            document.getElementById("edit-first-name").value = student.first_name;
            document.getElementById("edit-middle-name").value = student.middle_name;
            document.getElementById("edit-last-name").value = student.last_name;
            document.getElementById("edit-email").value = student.email;
            document.getElementById("edit-course").value = student.course_id;
            document.getElementById("edit-year").value = student.year_id;
            document.getElementById("edit-section").value = student.section_id;
    
            editModal.classList.add("show");
        }
    });

    document.querySelector(".close-edit").addEventListener("click", function () {
        editModal.classList.remove("show");
    });

    document.getElementById("edit-student-form").addEventListener("submit", function (e) {
        e.preventDefault();
    
        const formData = new FormData(this);
        console.log("Submitting form data:", Object.fromEntries(formData));
    
        fetch("edit_student.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            console.log("Server Response:", data);
            if (data.success) {
                alert("Student updated successfully.");
                location.reload();
            } else {
                alert("Update failed: " + data.error);
            }
        })
        .catch(error => {
            console.error("Update error:", error);
        });
    });
});
