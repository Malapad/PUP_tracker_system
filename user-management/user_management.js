document.addEventListener("DOMContentLoaded", function () {
    let studentTable = document.getElementById("student-table");
    let editModal = document.getElementById("edit-modal");
    let originalStudentNumberInput = document.getElementById("original-student-number");

    let addStudentModal = document.getElementById("add-student-modal");
    let openAddStudentModalBtn = document.getElementById("open-add-student-modal-btn");
    let closeAddModalBtn = document.querySelector("#add-student-modal .close-add");
    let addStudentForm = document.getElementById("add-student-form");

    let deleteConfirmModal = document.getElementById("delete-confirm-modal");
    let closeDeleteConfirmModalBtn = document.querySelector("#delete-confirm-modal .close-delete-confirm");
    let confirmDeleteActionBtn = document.getElementById("confirm-delete-action-btn");
    let cancelDeleteActionBtn = document.getElementById("cancel-delete-action-btn");
    let studentNumberToDelete = null;
    let deleteStudentIdDisplay = document.getElementById("delete-student-id-display");


    function showToastNotification(message, type = 'success') {
        const toast = document.getElementById('custom-toast-notification');
        const toastMessage = document.getElementById('toast-notification-message');

        if (!toast || !toastMessage) {
            console.error("Toast notification HTML elements not found!");
            alert(message);
            return;
        }

        toastMessage.textContent = message;
        toast.classList.remove('success', 'error');
        toast.classList.add(type);
        toast.classList.add('show');

        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    if (openAddStudentModalBtn) {
        openAddStudentModalBtn.addEventListener("click", function () {
            if (addStudentModal) {
                addStudentModal.classList.add("show");
                if (addStudentForm) {
                    addStudentForm.reset();
                }
            }
        });
    }

    if (closeAddModalBtn) {
        closeAddModalBtn.addEventListener("click", function () {
            if (addStudentModal) {
                addStudentModal.classList.remove("show");
            }
        });
    }

    if (studentTable) {
        studentTable.addEventListener("click", function (e) {
            let editBtn = e.target.closest(".edit-btn:not(#cancel-delete-action-btn)");
            let deleteTriggerBtn = e.target.closest(".delete-trigger-btn");

            if (editBtn) {
                const student = JSON.parse(editBtn.getAttribute("data-student"));
                document.getElementById("edit-student-number").value = student.student_number;
                document.getElementById("edit-first-name").value = student.first_name;
                document.getElementById("edit-middle-name").value = student.middle_name || "";
                document.getElementById("edit-last-name").value = student.last_name;
                document.getElementById("edit-email").value = student.email;
                document.getElementById("edit-course").value = student.course_id;
                document.getElementById("edit-year").value = student.year_id;
                document.getElementById("edit-section").value = student.section_id;
                document.getElementById("edit-status").value = student.status_id;

                if (originalStudentNumberInput) {
                    originalStudentNumberInput.value = student.student_number;
                }
                if (editModal) {
                    editModal.classList.add("show");
                }
            } else if (deleteTriggerBtn) {
                studentNumberToDelete = deleteTriggerBtn.dataset.studentNumber;
                if(deleteStudentIdDisplay) {
                    deleteStudentIdDisplay.textContent = studentNumberToDelete;
                }
                if (deleteConfirmModal) {
                    deleteConfirmModal.classList.add("show");
                }
            }
        });
    }


    const closeEditModalBtn = document.querySelector("#edit-modal .close-edit");
    if (closeEditModalBtn) {
        closeEditModalBtn.addEventListener("click", function () {
            if (editModal) {
                editModal.classList.remove("show");
            }
        });
    }
    
    if (closeDeleteConfirmModalBtn) {
        closeDeleteConfirmModalBtn.addEventListener("click", function() {
            if (deleteConfirmModal) {
                deleteConfirmModal.classList.remove("show");
                studentNumberToDelete = null; 
            }
        });
    }

    if (cancelDeleteActionBtn) {
        cancelDeleteActionBtn.addEventListener("click", function() {
            if (deleteConfirmModal) {
                deleteConfirmModal.classList.remove("show");
                studentNumberToDelete = null;
            }
        });
    }

    if (confirmDeleteActionBtn) {
        confirmDeleteActionBtn.addEventListener("click", function() {
            if (studentNumberToDelete && deleteConfirmModal) {
                const formData = new FormData();
                formData.append('student_number', studentNumberToDelete);

                fetch('delete_student.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToastNotification('Student deleted successfully.', 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showToastNotification('Failed to delete student: ' + (data.error || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToastNotification('An error occurred: ' + error.message, 'error');
                })
                .finally(() => {
                    if(deleteConfirmModal){
                        deleteConfirmModal.classList.remove("show");
                    }
                    studentNumberToDelete = null;
                });
            }
        });
    }
    
    window.addEventListener("click", function(event) {
        if (editModal && event.target === editModal) {
            editModal.classList.remove("show");
        }
        if (addStudentModal && event.target === addStudentModal) {
            addStudentModal.classList.remove("show");
        }
        if (deleteConfirmModal && event.target === deleteConfirmModal) {
            deleteConfirmModal.classList.remove("show");
            studentNumberToDelete = null;
        }
    });

    const editStudentForm = document.getElementById("edit-student-form");
    if (editStudentForm) {
        editStudentForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch("edit_student.php", {
                method: "POST",
                body: formData
            })
            .then(res => {
                if (!res.ok) {
                    return res.text().then(text => { throw new Error("Server responded with " + res.status + ": " + text); });
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    showToastNotification("Student updated successfully.", "success");
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToastNotification("Update failed: " + (data.error || "Unknown error from server."), "error");
                }
            })
            .catch(error => {
                console.error("Update error:", error);
                showToastNotification("An error occurred: " + error.message, "error");
            });
        });
    }


    if (addStudentForm) {
        addStudentForm.addEventListener("submit", function(event) {
            event.preventDefault();
            const formData = new FormData(addStudentForm);

            fetch('../user-management/add_student.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToastNotification('Student added successfully.', 'success');
                    if (addStudentModal) {
                        addStudentModal.classList.remove("show");
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToastNotification('Failed to add student: ' + (data.error || "Unknown error"), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToastNotification('An error occurred: ' + error.message, 'error');
            });
        });
    }
});