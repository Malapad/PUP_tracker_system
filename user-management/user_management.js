document.addEventListener("DOMContentLoaded", function () {
    const tabs = document.querySelectorAll(".tab");
    const tabContents = document.querySelectorAll(".tab-content");

    const studentListView = document.getElementById('student-list-view');
    const studentHistoryView = document.getElementById('student-history-view');
    const toggleStudentHistoryBtn = document.getElementById('toggle-student-history-btn');
    const backToStudentListBtn = document.getElementById('back-to-student-list-btn');

    const adminListView = document.getElementById('admin-list-view');
    const adminHistoryView = document.getElementById('admin-history-view');
    const toggleAdminHistoryBtn = document.getElementById('toggle-admin-history-btn');
    const backToAdminListBtn = document.getElementById('back-to-admin-list-btn');

    const securityListView = document.getElementById('security-list-view');
    const securityHistoryView = document.getElementById('security-history-view');
    const toggleSecurityHistoryBtn = document.getElementById('toggle-security-history-btn');
    const backToSecurityListBtn = document.getElementById('back-to-security-list-btn');
    
    const studentTable = document.getElementById("student-table");
    const addStudentModal = document.getElementById("add-student-modal");
    const openAddStudentModalBtn = document.getElementById("open-add-student-modal-btn");
    const addStudentForm = document.getElementById("add-student-form");
    const editStudentModal = document.getElementById("edit-student-modal");
    const editStudentForm = document.getElementById("edit-student-form");
    const refreshStudentListBtn = document.getElementById("refresh-student-list-btn");

    const adminTable = document.getElementById("admin-table");
    const addAdminModal = document.getElementById("add-admin-modal");
    const openAddAdminModalBtn = document.getElementById("open-add-admin-modal-btn");
    const addAdminForm = document.getElementById("add-admin-form");
    const editAdminModal = document.getElementById("edit-admin-modal");
    const editAdminForm = document.getElementById("edit-admin-form");
    const refreshAdminListBtn = document.getElementById("refresh-admin-list-btn");

    const securityTable = document.getElementById("security-table");
    const addSecurityModal = document.getElementById("add-security-modal");
    const openAddSecurityModalBtn = document.getElementById("open-add-security-modal-btn");
    const addSecurityForm = document.getElementById("add-security-form");
    const editSecurityModal = document.getElementById("edit-security-modal");
    const editSecurityForm = document.getElementById("edit-security-form");
    const refreshSecurityListBtn = document.getElementById("refresh-security-list-btn");

    const deleteConfirmModal = document.getElementById("delete-confirm-modal");
    const deleteItemTypePlaceholder = document.getElementById("delete-item-type-placeholder");
    const deleteItemIdentifierPlaceholder = document.getElementById("delete-item-identifier-placeholder");
    const confirmDeleteActionBtn = document.getElementById("confirm-delete-action-btn");
    const cancelDeleteActionBtn = document.getElementById("cancel-delete-action-btn");
    const allCloseModalBtns = document.querySelectorAll(".close-modal");
    let itemToDeleteId = null;
    let itemTypeToDelete = null;

    function showToastNotification(message, type = 'success') {
        const toast = document.getElementById('custom-toast-notification');
        const toastMessage = document.getElementById('toast-notification-message');
        if (!toast || !toastMessage) { return; }
        toastMessage.textContent = message;
        toast.className = 'toast-notification show ' + type;

        setTimeout(() => { 
            toast.classList.remove('show'); 
        }, 3000); 
    }

    function openModal(modalElement) { if (modalElement) { modalElement.style.display = "flex"; } }
    function closeModal(modalElement) { if (modalElement) { modalElement.style.display = "none"; } }
    
    function refreshPageForTab(tabName) {
        window.location.href = window.location.pathname + '?tab=' + tabName;
    }

    tabs.forEach(tab => {
        tab.addEventListener("click", () => {
            const targetTab = tab.dataset.tab;
            tabs.forEach(t => t.classList.remove("active"));
            tab.classList.add("active");
            tabContents.forEach(content => { content.classList.remove("active"); });
            document.getElementById(`${targetTab}-content`).classList.add("active");
            
            const url = new URL(window.location);
            url.searchParams.set('tab', targetTab);
            url.searchParams.delete('search');
            url.searchParams.delete('admin_search');
            url.searchParams.delete('security_search');
            window.history.pushState({}, '', url);
        });
    });

    const urlParams = new URLSearchParams(window.location.search);
    const activeTabFromUrl = urlParams.get('tab') || 'students';
    const tabToActivate = document.querySelector(`.tab[data-tab="${activeTabFromUrl}"]`);
    if (tabToActivate) {
        tabs.forEach(t => t.classList.remove("active"));
        tabToActivate.classList.add("active");
        tabContents.forEach(content => { content.classList.remove("active"); });
        document.getElementById(`${activeTabFromUrl}-content`).classList.add("active");
    }

    if (toggleStudentHistoryBtn) {
        toggleStudentHistoryBtn.addEventListener('click', () => {
            studentListView.style.display = 'none';
            studentHistoryView.style.display = 'block';
        });
    }
    if (backToStudentListBtn) {
        backToStudentListBtn.addEventListener('click', () => {
            studentListView.style.display = 'block';
            studentHistoryView.style.display = 'none';
        });
    }

    if (toggleAdminHistoryBtn) {
        toggleAdminHistoryBtn.addEventListener('click', () => {
            adminListView.style.display = 'none';
            adminHistoryView.style.display = 'block';
        });
    }
    if (backToAdminListBtn) {
        backToAdminListBtn.addEventListener('click', () => {
            adminListView.style.display = 'block';
            adminHistoryView.style.display = 'none';
        });
    }

    if (toggleSecurityHistoryBtn) {
        toggleSecurityHistoryBtn.addEventListener('click', () => {
            securityListView.style.display = 'none';
            securityHistoryView.style.display = 'block';
        });
    }
    if (backToSecurityListBtn) {
        backToSecurityListBtn.addEventListener('click', () => {
            securityListView.style.display = 'block';
            securityHistoryView.style.display = 'none';
        });
    }
    
    allCloseModalBtns.forEach(btn => {
        btn.addEventListener("click", () => closeModal(btn.closest(".modal")));
    });
    window.addEventListener("click", (event) => {
        if (event.target.classList.contains("modal")) closeModal(event.target);
    });

    if(refreshStudentListBtn) refreshStudentListBtn.addEventListener("click", () => refreshPageForTab('students'));
    if(refreshAdminListBtn) refreshAdminListBtn.addEventListener("click", () => refreshPageForTab('admins'));
    if(refreshSecurityListBtn) refreshSecurityListBtn.addEventListener("click", () => refreshPageForTab('security'));

    if (openAddStudentModalBtn) {
        openAddStudentModalBtn.addEventListener("click", () => {
            if (addStudentForm) addStudentForm.reset();
            openModal(addStudentModal);
        });
    }
    if (openAddAdminModalBtn) {
        openAddAdminModalBtn.addEventListener("click", () => {
            if (addAdminForm) addAdminForm.reset();
            openModal(addAdminModal);
        });
    }
    if (openAddSecurityModalBtn) {
        openAddSecurityModalBtn.addEventListener("click", () => {
            if (addSecurityForm) addSecurityForm.reset();
            openModal(addSecurityModal);
        });
    }
    
    if (addStudentForm) { addStudentForm.addEventListener("submit", handleFormSubmit('add_student.php', 'students', addStudentModal)); }
    if (editStudentForm) { editStudentForm.addEventListener("submit", handleFormSubmit('edit_student.php', 'students', editStudentModal)); }
    if (addAdminForm) { addAdminForm.addEventListener("submit", handleFormSubmit('add_admin.php', 'admins', addAdminModal)); }
    if (editAdminForm) { editAdminForm.addEventListener("submit", handleFormSubmit('edit_admin.php', 'admins', editAdminModal)); }
    if (addSecurityForm) { addSecurityForm.addEventListener("submit", handleFormSubmit('add_security.php', 'security', addSecurityModal)); }
    if (editSecurityForm) { editSecurityForm.addEventListener("submit", handleFormSubmit('edit_security.php', 'security', editSecurityModal)); }

    function handleFormSubmit(url, tabToRefresh, modal) {
        return function(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            fetch(url, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToastNotification(data.message || 'Action completed successfully.', 'success');
                    closeModal(modal);
                    setTimeout(() => refreshPageForTab(tabToRefresh), 1500);
                } else {
                    showToastNotification('Error: ' + (data.error || "Unknown error"), 'error');
                }
            })
            .catch(error => showToastNotification('A network error occurred: ' + error.message, 'error'));
        }
    }

    if(studentTable) studentTable.addEventListener("click", handleTableClick);
    if(adminTable) adminTable.addEventListener("click", handleTableClick);
    if(securityTable) securityTable.addEventListener("click", handleTableClick);
    
    function handleTableClick(e) {
        const editBtn = e.target.closest(".edit-btn");
        const deleteTriggerBtn = e.target.closest(".delete-btn");

        if (editBtn) {
            if(editBtn.classList.contains('student-edit-btn')) {
                const student = JSON.parse(editBtn.getAttribute("data-student"));
                editStudentForm.querySelector("#original-student-number").value = student.student_number;
                editStudentForm.querySelector("#edit-student-number").value = student.student_number;
                editStudentForm.querySelector("#edit-student-first-name").value = student.first_name;
                editStudentForm.querySelector("#edit-student-middle-name").value = student.middle_name || "";
                editStudentForm.querySelector("#edit-student-last-name").value = student.last_name;
                editStudentForm.querySelector("#edit-student-email").value = student.email;
                editStudentForm.querySelector("#edit-student-course").value = student.course_id;
                editStudentForm.querySelector("#edit-student-year").value = student.year_id;
                editStudentForm.querySelector("#edit-student-section").value = student.section_id;
                editStudentForm.querySelector("#edit-student-status").value = student.status_id;
                openModal(editStudentModal);
            } else if (editBtn.classList.contains('admin-edit-btn')) {
                const admin = JSON.parse(editBtn.getAttribute("data-admin"));
                editAdminForm.querySelector("#edit-admin-id").value = admin.admin_id;
                editAdminForm.querySelector("#edit-admin-first-name").value = admin.first_name;
                editAdminForm.querySelector("#edit-admin-middle-name").value = admin.middle_name || "";
                editAdminForm.querySelector("#edit-admin-last-name").value = admin.last_name;
                editAdminForm.querySelector("#edit-admin-position").value = admin.position;
                editAdminForm.querySelector("#edit-admin-email").value = admin.email;
                editAdminForm.querySelector("#edit-admin-password").value = ""; 
                editAdminForm.querySelector("#edit-admin-status").value = admin.status_id;
                openModal(editAdminModal);
            } else if (editBtn.classList.contains('security-edit-btn')) {
                const security = JSON.parse(editBtn.getAttribute("data-security"));
                editSecurityForm.querySelector("#edit-security-id").value = security.security_id;
                editSecurityForm.querySelector("#edit-security-first-name").value = security.first_name;
                editSecurityForm.querySelector("#edit-security-middle-name").value = security.middle_name || "";
                editSecurityForm.querySelector("#edit-security-last-name").value = security.last_name;
                editSecurityForm.querySelector("#edit-security-position").value = security.position;
                editSecurityForm.querySelector("#edit-security-email").value = security.email;
                editSecurityForm.querySelector("#edit-security-password").value = ""; 
                editSecurityForm.querySelector("#edit-security-status").value = security.status_id;
                openModal(editSecurityModal);
            }
        } else if (deleteTriggerBtn) {
            itemToDeleteId = deleteTriggerBtn.dataset.id;
            itemTypeToDelete = deleteTriggerBtn.dataset.type;
            deleteItemTypePlaceholder.textContent = itemTypeToDelete;
            deleteItemIdentifierPlaceholder.textContent = deleteTriggerBtn.dataset.name;
            openModal(deleteConfirmModal);
        }
    }

    if (confirmDeleteActionBtn) {
        confirmDeleteActionBtn.addEventListener("click", function() {
            if (!itemToDeleteId || !itemTypeToDelete) return;
            
            const formData = new FormData();
            let url = '';
            let tabToRefresh = '';
            if (itemTypeToDelete === 'student') {
                formData.append('student_number', itemToDeleteId);
                url = './delete_student.php';
                tabToRefresh = 'students';
            } else if (itemTypeToDelete === 'admin') {
                formData.append('admin_id', itemToDeleteId);
                url = './delete_admin.php';
                tabToRefresh = 'admins';
            } else if (itemTypeToDelete === 'security') {
                formData.append('security_id', itemToDeleteId);
                url = './delete_security.php';
                tabToRefresh = 'security';
            }
            
            fetch(url, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToastNotification(`${itemTypeToDelete.charAt(0).toUpperCase() + itemTypeToDelete.slice(1)} deleted successfully.`, 'success');
                    setTimeout(() => refreshPageForTab(tabToRefresh), 1500);
                } else {
                    showToastNotification(`Failed to delete: ` + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => showToastNotification('A network error occurred: ' + error.message, 'error'))
            .finally(() => {
                closeModal(deleteConfirmModal);
                itemToDeleteId = null;
                itemTypeToDelete = null;
            });
        });
    }
    if(cancelDeleteActionBtn) cancelDeleteActionBtn.addEventListener("click", () => closeModal(deleteConfirmModal));
});