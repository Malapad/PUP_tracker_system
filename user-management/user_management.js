document.addEventListener("DOMContentLoaded", function () {
    const tabs = document.querySelectorAll(".tab");
    const tabContents = document.querySelectorAll(".tab-content");

    const studentTable = document.getElementById("student-table");
    const editStudentModal = document.getElementById("edit-student-modal");
    const originalStudentNumberInput = document.getElementById("original-student-number");
    const addStudentModal = document.getElementById("add-student-modal");
    const openAddStudentModalBtn = document.getElementById("open-add-student-modal-btn");
    const addStudentForm = document.getElementById("add-student-form");
    const editStudentForm = document.getElementById("edit-student-form");
    const refreshStudentListBtn = document.getElementById("refresh-student-list-btn");

    const adminTable = document.getElementById("admin-table");
    const addAdminModal = document.getElementById("add-admin-modal");
    const openAddAdminModalBtn = document.getElementById("open-add-admin-modal-btn");
    const addAdminForm = document.getElementById("add-admin-form");
    const editAdminModal = document.getElementById("edit-admin-modal");
    const editAdminForm = document.getElementById("edit-admin-form");
    const refreshAdminListBtn = document.getElementById("refresh-admin-list-btn");

    const deleteConfirmModal = document.getElementById("delete-confirm-modal");
    const closeDeleteConfirmModalBtn = deleteConfirmModal.querySelector(".close-delete-confirm");
    const confirmDeleteActionBtn = document.getElementById("confirm-delete-action-btn");
    const cancelDeleteActionBtn = document.getElementById("cancel-delete-action-btn");
    
    const deleteItemTypePlaceholder = document.getElementById("delete-item-type-placeholder");
    const deleteItemIdentifierPlaceholder = document.getElementById("delete-item-identifier-placeholder");

    let itemToDeleteId = null;
    let itemTypeToDelete = null;

    const allCloseModalBtns = document.querySelectorAll(".close-modal");

    function showToastNotification(message, type = 'success') {
        const toast = document.getElementById('custom-toast-notification');
        const toastMessage = document.getElementById('toast-notification-message');
        if (!toast || !toastMessage) {
            console.error("Toast notification HTML elements not found!");
            alert(message);
            return;
        }
        toastMessage.textContent = message;
        toast.className = 'toast-notification';
        toast.classList.add(type);
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    function openModal(modalElement) {
        if (modalElement) {
            modalElement.classList.add("show");
        }
    }

    function closeModal(modalElement) {
        if (modalElement) {
            modalElement.classList.remove("show");
        }
    }
    
    allCloseModalBtns.forEach(btn => {
        btn.addEventListener("click", function () {
            closeModal(this.closest(".modal"));
        });
    });

    window.addEventListener("click", function(event) {
        if (event.target.classList.contains("modal")) {
            closeModal(event.target);
        }
    });
    
    function refreshPageForTab(tabName) {
        window.location.href = window.location.pathname + '?tab=' + tabName;
    }

    tabs.forEach(tab => {
        tab.addEventListener("click", () => {
            const targetTab = tab.dataset.tab;

            tabs.forEach(t => t.classList.remove("active"));
            tab.classList.add("active");

            tabContents.forEach(content => {
                content.classList.remove("active");
                if (content.id === `${targetTab}-content`) {
                    content.classList.add("active");
                }
            });
            const url = new URL(window.location);
            url.searchParams.set('tab', targetTab);
            window.history.pushState({}, '', url);
        });
    });

    const urlParams = new URLSearchParams(window.location.search);
    const activeTabFromUrl = urlParams.get('tab');
    if (activeTabFromUrl) {
        const tabToActivate = document.querySelector(`.tab[data-tab="${activeTabFromUrl}"]`);
        if (tabToActivate) {
            tabToActivate.click();
        }
    } else {
        const defaultTab = document.querySelector('.tab[data-tab="students"]');
        if (defaultTab && !defaultTab.classList.contains('active')) {
             defaultTab.click();
        } else if (!document.querySelector('.tab.active')) {
            tabs[0]?.click();
        }
    }

    if (openAddStudentModalBtn) {
        openAddStudentModalBtn.addEventListener("click", () => {
            if (addStudentForm) addStudentForm.reset();
            openModal(addStudentModal);
        });
    }

    if (addStudentForm) {
        addStudentForm.addEventListener("submit", function(event) {
            event.preventDefault();
            const formData = new FormData(addStudentForm);
            fetch('add_student.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToastNotification('Student added successfully.', 'success');
                    closeModal(addStudentModal);
                    setTimeout(() => refreshPageForTab('students'), 1500);
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
    
    if (editStudentForm) {
        editStudentForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch("edit_student.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToastNotification("Student updated successfully.", "success");
                    closeModal(editStudentModal);
                    setTimeout(() => refreshPageForTab('students'), 1500);
                } else {
                    showToastNotification("Update failed: " + (data.error || "Unknown error."), "error");
                }
            })
            .catch(error => {
                console.error("Update error:", error);
                showToastNotification("An error occurred: " + error.message, "error");
            });
        });
    }

    if (studentTable) {
        studentTable.addEventListener("click", function (e) {
            const editBtn = e.target.closest(".student-edit-btn");
            const deleteTriggerBtn = e.target.closest(".student-delete-trigger-btn");

            if (editBtn) {
                const student = JSON.parse(editBtn.getAttribute("data-student"));
                document.getElementById("original-student-number").value = student.student_number;
                document.getElementById("edit-student-number").value = student.student_number;
                document.getElementById("edit-student-first-name").value = student.first_name;
                document.getElementById("edit-student-middle-name").value = student.middle_name || "";
                document.getElementById("edit-student-last-name").value = student.last_name;
                document.getElementById("edit-student-email").value = student.email;
                document.getElementById("edit-student-course").value = student.course_id;
                document.getElementById("edit-student-year").value = student.year_id;
                document.getElementById("edit-student-section").value = student.section_id;
                document.getElementById("edit-student-status").value = student.status_id;
                openModal(editStudentModal);
            } else if (deleteTriggerBtn) {
                itemToDeleteId = deleteTriggerBtn.dataset.id;
                itemTypeToDelete = deleteTriggerBtn.dataset.type;
                const studentIdentifier = deleteTriggerBtn.dataset.name; 

                if(deleteItemTypePlaceholder) deleteItemTypePlaceholder.textContent = itemTypeToDelete;
                if(deleteItemIdentifierPlaceholder) deleteItemIdentifierPlaceholder.textContent = studentIdentifier;
                openModal(deleteConfirmModal);
            }
        });
    }
    
    if(refreshStudentListBtn) {
        refreshStudentListBtn.addEventListener("click", () => refreshPageForTab('students'));
    }

    if (openAddAdminModalBtn) {
        openAddAdminModalBtn.addEventListener("click", () => {
            if (addAdminForm) addAdminForm.reset();
            openModal(addAdminModal);
        });
    }

    if (addAdminForm) {
        addAdminForm.addEventListener("submit", function(event) {
            event.preventDefault();
            const formData = new FormData(addAdminForm);
            fetch('add_admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => { throw new Error("Server error: " + response.status + ". Response: " + text) });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToastNotification('Admin added successfully.', 'success');
                    closeModal(addAdminModal);
                    setTimeout(() => refreshPageForTab('admins'), 1500);
                } else {
                    showToastNotification('Failed to add admin: ' + (data.error || "Unknown error"), 'error');
                }
            })
            .catch(error => {
                console.error('Error adding admin:', error);
                showToastNotification('An error occurred trying to add admin: ' + error.message, 'error');
            });
        });
    }

    if (editAdminForm) {
        editAdminForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch("edit_admin.php", {
                method: "POST",
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => { throw new Error("Server error: " + response.status + ". Response: " + text) });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToastNotification("Admin updated successfully.", "success");
                    closeModal(editAdminModal);
                    setTimeout(() => refreshPageForTab('admins'), 1500);
                } else {
                    showToastNotification("Admin update failed: " + (data.error || "Unknown error."), "error");
                }
            })
            .catch(error => {
                console.error("Admin update error:", error);
                showToastNotification("An error occurred trying to update admin: " + error.message, "error");
            });
        });
    }
    
    if (adminTable) {
        adminTable.addEventListener("click", function (e) {
            const editBtn = e.target.closest(".admin-edit-btn");
            const deleteTriggerBtn = e.target.closest(".admin-delete-trigger-btn");

            if (editBtn) {
                const admin = JSON.parse(editBtn.getAttribute("data-admin"));
                document.getElementById("edit-admin-id").value = admin.admin_id;
                document.getElementById("edit-admin-first-name").value = admin.first_name;
                document.getElementById("edit-admin-middle-name").value = admin.middle_name || "";
                document.getElementById("edit-admin-last-name").value = admin.last_name;
                document.getElementById("edit-admin-position").value = admin.position;
                document.getElementById("edit-admin-email").value = admin.email;
                document.getElementById("edit-admin-password").value = ""; 
                document.getElementById("edit-admin-status").value = admin.status_id;
                openModal(editAdminModal);
            } else if (deleteTriggerBtn) {
                itemToDeleteId = deleteTriggerBtn.dataset.id;
                itemTypeToDelete = deleteTriggerBtn.dataset.type;
                const adminName = deleteTriggerBtn.dataset.name;

                if(deleteItemTypePlaceholder) deleteItemTypePlaceholder.textContent = itemTypeToDelete;
                if(deleteItemIdentifierPlaceholder) deleteItemIdentifierPlaceholder.textContent = adminName;
                openModal(deleteConfirmModal);
            }
        });
    }

    if(refreshAdminListBtn) {
        refreshAdminListBtn.addEventListener("click", () => refreshPageForTab('admins'));
    }

    if (closeDeleteConfirmModalBtn) {
        closeDeleteConfirmModalBtn.addEventListener("click", function() {
            closeModal(deleteConfirmModal);
            itemToDeleteId = null;
            itemTypeToDelete = null;
        });
    }

    if (cancelDeleteActionBtn) {
        cancelDeleteActionBtn.addEventListener("click", function() {
            closeModal(deleteConfirmModal);
            itemToDeleteId = null;
            itemTypeToDelete = null;
        });
    }

    if (confirmDeleteActionBtn) {
        confirmDeleteActionBtn.addEventListener("click", function() {
            if (itemToDeleteId && itemTypeToDelete) {
                const formData = new FormData();
                let url = '';

                if (itemTypeToDelete === 'student') {
                    formData.append('student_number', itemToDeleteId);
                    url = 'delete_student.php';
                } else if (itemTypeToDelete === 'admin') {
                    formData.append('admin_id', itemToDeleteId);
                    url = 'delete_admin.php';
                } else {
                    showToastNotification('Invalid item type for deletion.', 'error');
                    return;
                }

                fetch(url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                         return response.text().then(text => { throw new Error("Server error: " + response.status + ". Response: " + text) });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showToastNotification(`${itemTypeToDelete.charAt(0).toUpperCase() + itemTypeToDelete.slice(1)} deleted successfully.`, 'success');
                        setTimeout(() => refreshPageForTab(itemTypeToDelete === 'student' ? 'students' : 'admins'), 1500);
                    } else {
                        showToastNotification(`Failed to delete ${itemTypeToDelete}: ` + (data.error || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToastNotification('An error occurred trying to delete: ' + error.message, 'error');
                })
                .finally(() => {
                    closeModal(deleteConfirmModal);
                    itemToDeleteId = null;
                    itemTypeToDelete = null;
                });
            }
        });
    }
});