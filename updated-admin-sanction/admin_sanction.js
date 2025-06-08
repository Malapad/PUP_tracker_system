// Function to show toast notifications
function showToast(message, type = 'success', duration = 3000) {
    const toast = document.getElementById('toast-notification');
    if (!toast) return;

    toast.textContent = message;
    toast.className = 'toast show';
    toast.classList.add(type, 'top-center');

    setTimeout(() => {
        toast.classList.remove('show');
    }, duration);
}

document.addEventListener("DOMContentLoaded", () => {
    // --- Tab Switching Logic ---
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetTab = tab.dataset.tab;

            // Do not switch tab content if a history view is active
            const currentUrl = new URL(window.location.href);
            if (currentUrl.searchParams.get('view') === 'history') {
                window.location.href = `?tab=${targetTab}`;
                return;
            }
            
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            tabContents.forEach(content => {
                content.style.display = (content.id === targetTab) ? 'block' : 'none';
            });

            // Update URL to reflect active tab
            currentUrl.searchParams.set('tab', targetTab);
            currentUrl.searchParams.delete('view'); // Remove view param when switching tabs
            window.history.pushState({ path: currentUrl.href }, '', currentUrl.href);
        });
    });

    // --- Common Modal Functions ---
    const openModal = (modalElement) => {
        if (modalElement) modalElement.style.display = "block";
    };

    const closeModal = (modalElement) => {
        if (modalElement) {
            modalElement.style.display = "none";
            const messageDiv = modalElement.querySelector('.modal-message');
            if (messageDiv) {
                messageDiv.style.display = 'none';
                messageDiv.textContent = '';
            }
            const form = modalElement.querySelector('form');
            if (form) form.reset();
        }
    };
    
    const displayModalMessage = (modalMessageDiv, message, type = 'error') => {
        if (modalMessageDiv) {
            modalMessageDiv.textContent = message;
            modalMessageDiv.className = `modal-message ${type}-message`;
            modalMessageDiv.style.display = 'block';
        }
    };

    // --- Global Modal Close Logic ---
    document.body.addEventListener('click', function(e) {
        const closeBtn = e.target.closest('.close-modal-button');
        if (closeBtn) {
            const modalId = closeBtn.dataset.modal;
            const modalElement = document.getElementById(modalId);
            if (modalElement) closeModal(modalElement);
        }
        if (e.target.classList.contains('modal')) {
            closeModal(e.target);
        }
    });

    // --- MANAGE SANCTION REQUEST MODAL ---
    const viewSanctionModal = document.getElementById('viewSanctionDetailsModal');
    const approveSanctionForm = document.getElementById('approveSanctionForm');
    const approveSanctionMsgDiv = document.getElementById('approveSanctionModalMessage');

    document.querySelector('#sanction-request')?.addEventListener('click', (e) => {
        const viewBtn = e.target.closest('.view-manage-btn');
        if (viewBtn) {
            document.getElementById('detailStudentNumber').textContent = viewBtn.dataset.studentNumber;
            document.getElementById('detailStudentName').textContent = viewBtn.dataset.studentName;
            document.getElementById('detailViolationType').textContent = viewBtn.dataset.violationType;
            document.getElementById('approveStudentNumber').value = viewBtn.dataset.studentNumber;
            document.getElementById('approveViolationId').value = viewBtn.dataset.violationId;
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('deadlineDate').setAttribute('min', today);
            openModal(viewSanctionModal);
        }
    });

    if (approveSanctionForm) {
        approveSanctionForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitButton = approveSanctionForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Approving...';
            try {
                const formData = new FormData(approveSanctionForm);
                const response = await fetch(approveSanctionForm.action, { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    closeModal(viewSanctionModal);
                    showToast(result.message, 'success');
                    setTimeout(() => window.location.href = window.location.pathname + '?tab=sanction-compliance', 1500);
                } else {
                    displayModalMessage(approveSanctionMsgDiv, result.message || 'An unknown error occurred.', 'error');
                }
            } catch (error) {
                displayModalMessage(approveSanctionMsgDiv, 'A network error occurred. Please try again.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-check"></i> Approve';
            }
        });
    }

    // --- NEW: SANCTION COMPLIANCE STATUS UPDATE ---
    document.querySelector('#sanction-compliance')?.addEventListener('click', async (e) => {
        const updateBtn = e.target.closest('.update-status-btn');
        if (!updateBtn) return;

        e.preventDefault();
        const recordId = updateBtn.dataset.recordId;
        const newStatus = updateBtn.dataset.newStatus;
        const studentNumber = updateBtn.dataset.studentNumber;
        const originalBtnHTML = updateBtn.innerHTML;

        updateBtn.disabled = true;
        updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

        try {
            const formData = new FormData();
            formData.append('update_sanction_status', '1');
            formData.append('record_id', recordId);
            formData.append('new_status', newStatus);
            formData.append('student_number', studentNumber);

            const response = await fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
                // Optimistically remove the row from the UI
                updateBtn.closest('tr').style.opacity = '0';
                setTimeout(() => {
                    updateBtn.closest('tr').remove();
                    // Check if table is empty
                    const tableBody = document.querySelector('#sanction-compliance tbody');
                    if (tableBody && tableBody.rows.length === 0) {
                        const colCount = document.querySelector('#sanction-compliance thead th').length;
                        const statusFilter = new URL(window.location.href).searchParams.get('status_filter') || 'pending';
                        tableBody.innerHTML = `<tr><td colspan="${colCount}" class="no-records-cell">No ${statusFilter} sanctions found.</td></tr>`;
                    }
                }, 400);
            } else {
                showToast(result.message, 'error');
                updateBtn.disabled = false;
                updateBtn.innerHTML = originalBtnHTML;
            }
        } catch (error) {
            showToast('A network error occurred.', 'error');
            updateBtn.disabled = false;
            updateBtn.innerHTML = originalBtnHTML;
        }
    });


    // --- SANCTION CONFIGURATION CRUD ---
    const addSanctionTypeModal = document.getElementById('addSanctionTypeModal');
    const addSanctionTypeForm = document.getElementById('addSanctionTypeForm');
    document.getElementById('addSanctionTypeBtn')?.addEventListener('click', () => openModal(addSanctionTypeModal));
    
    document.getElementById('nextToAddSanctionStep2')?.addEventListener('click', () => {
        document.getElementById('summarySanctionName').textContent = document.getElementById('newSanctionName').value.toUpperCase();
        document.getElementById('summaryHoursRequired').textContent = document.getElementById('newHoursRequired').value || '0';
        document.getElementById('addSanctionStep1').style.display = 'none';
        document.getElementById('addSanctionStep2').style.display = 'block';
    });
    document.getElementById('backToAddSanctionStep1')?.addEventListener('click', () => {
        document.getElementById('addSanctionStep2').style.display = 'none';
        document.getElementById('addSanctionStep1').style.display = 'block';
    });

    if (addSanctionTypeForm) {
        handleFormSubmit(addSanctionTypeForm, addSanctionTypeModal, 'addSanctionTypeModalMessage');
    }

    const editSanctionTypeModal = document.getElementById('editSanctionTypeModal');
    const editSanctionTypeForm = document.getElementById('editSanctionTypeForm');
    document.querySelector('#sanction-config')?.addEventListener('click', e => {
        const editBtn = e.target.closest('.edit-sanction-type-btn');
        if (editBtn) {
            document.getElementById('editSanctionId').value = editBtn.dataset.id;
            document.getElementById('editSanctionName').value = editBtn.dataset.name;
            document.getElementById('editHoursRequired').value = editBtn.dataset.hours;
            openModal(editSanctionTypeModal);
        }
    });
    if (editSanctionTypeForm) {
        handleFormSubmit(editSanctionTypeForm, editSanctionTypeModal, 'editSanctionTypeModalMessage');
    }

    const deleteSanctionTypeModal = document.getElementById('deleteSanctionTypeModal');
    let sanctionIdToDelete = null;
    document.querySelector('#sanction-config')?.addEventListener('click', e => {
        const deleteBtn = e.target.closest('.delete-sanction-type-btn');
        if (deleteBtn) {
            sanctionIdToDelete = deleteBtn.dataset.id;
            document.getElementById('deleteSanctionTypeDisplay').textContent = deleteBtn.dataset.name;
            openModal(deleteSanctionTypeModal);
        }
    });

    document.getElementById('confirmDeleteSanctionTypeBtn')?.addEventListener('click', async () => {
        if (!sanctionIdToDelete) return;
        const formData = new FormData();
        formData.append('delete_sanction_id', sanctionIdToDelete);
        try {
            const response = await fetch(window.location.pathname, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                closeModal(deleteSanctionTypeModal);
                showToast(result.message, 'success');
                setTimeout(() => window.location.href = window.location.pathname + '?tab=sanction-config', 1500);
            } else {
                displayModalMessage(document.getElementById('deleteSanctionTypeModalMessage'), result.message, 'error');
            }
        } catch (err) {
            displayModalMessage(document.getElementById('deleteSanctionTypeModalMessage'), 'A network error occurred.', 'error');
        }
    });

    async function handleFormSubmit(form, modal, messageDivId) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submitButton = form.querySelector('button[type="submit"]');
            const originalBtnHTML = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            try {
                const response = await fetch(form.action, { method: 'POST', body: new FormData(form) });
                const result = await response.json();
                if (result.success) {
                    closeModal(modal);
                    showToast(result.message, 'success');
                    setTimeout(() => window.location.href = window.location.pathname + '?tab=sanction-config', 1500);
                } else {
                    displayModalMessage(document.getElementById(messageDivId), result.message, 'error');
                }
            } catch (error) {
                displayModalMessage(document.getElementById(messageDivId), 'A network error occurred.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalBtnHTML;
            }
        });
    }
});