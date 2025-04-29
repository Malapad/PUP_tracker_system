document.addEventListener("DOMContentLoaded", function () {
    let studentTable = document.getElementById("student-table");
    let editModal = document.getElementById("edit-modal");

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

    function submitFilters() {
        document.getElementById("filter-form").submit();
    }
});
