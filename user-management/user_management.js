document.addEventListener("DOMContentLoaded", function () {
    let studentForm = document.getElementById("student-form");
    let studentTable = document.getElementById("student-table");
    let modal = document.getElementById("modal");

    document.getElementById("add-student").addEventListener("click", function () {
        modal.style.display = "block";
    });

    document.querySelector(".close").addEventListener("click", function () {
        modal.style.display = "none";
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

    for (let pair of formData.entries()) {
        console.log(pair[0] + ": " + pair[1]);
    }

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
                    <button class='edit-btn'>Edit</button>
                    <button class='delete-btn'>Delete</button>
                </td>
            `;
            studentTable.appendChild(newRow);

            modal.style.display = "none";
            studentForm.reset();
        } else {
            alert("Error: " + data.error);
        }
    })
    .catch(error => {
        console.error("Fetch Error:", error);
        alert("Network error: Check console for details.");
    });

    // Maintain search input value after form submission
    const urlParams = new URLSearchParams(window.location.search);
    const searchValue = urlParams.get('search');
    if (searchValue) {
        document.getElementById("search-input").value = searchValue;
    }

});

});
