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

            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            tabContents.forEach(content => {
                content.style.display = (content.id === targetTab) ? 'block' : 'none';
            });

            // Update URL to reflect active tab
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('tab', targetTab);
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
    
    // Generic display message function for modals
    const displayModalMessage = (modalMessageDiv, message, type = 'error') => {
        if (modalMessageDiv) {
            modalMessageDiv.textContent = message;
            modalMessageDiv.className = `modal-message ${type}-message`;
            modalMessageDiv.style.display = 'block';
        }
    };

    // --- Global Modal Close Logic ---
    document.body.addEventListener('click', function(e) {
        // Close via close button
        const closeBtn = e.target.closest('.close-modal-button');
        if (closeBtn) {
            const modalId = closeBtn.dataset.modal;
            const modalElement = document.getElementById(modalId);
            if (modalElement) closeModal(modalElement);
        }
        // Close by clicking overlay
        if (e.target.classList.contains('modal')) {
            closeModal(e.target);
        }
    });

    // --- MANAGE SANCTION REQUEST MODAL (FORMERLY VIEW DETAILS) ---
    const viewSanctionModal = document.getElementById('viewSanctionDetailsModal');
    const approveSanctionForm = document.getElementById('approveSanctionForm');
    const approveSanctionMsgDiv = document.getElementById('approveSanctionModalMessage');

    document.querySelector('#sanction-request').addEventListener('click', (e) => {
        const viewBtn = e.target.closest('.view-manage-btn');
        if (viewBtn) {
            // Populate static info
            document.getElementById('detailStudentNumber').textContent = viewBtn.dataset.studentNumber;
            document.getElementById('detailStudentName').textContent = viewBtn.dataset.studentName;
            document.getElementById('detailViolationType').textContent = viewBtn.dataset.violationType;
            
            // Populate form fields
            document.getElementById('approveStudentNumber').value = viewBtn.dataset.studentNumber;
            document.getElementById('approveViolationId').value = viewBtn.dataset.violationId;
            
            // Set minimum date for deadline to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('deadlineDate').setAttribute('min', today);

            openModal(viewSanctionModal);
        }
    });

    // AJAX for Approving Sanction
    if (approveSanctionForm) {
        approveSanctionForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitButton = approveSanctionForm.querySelector('button[type="submit"]');
            const originalBtnHTML = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Approving...';

            try {
                const formData = new FormData(approveSanctionForm);
                const response = await fetch(approveSanctionForm.action, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();

                if (result.success) {
                    closeModal(viewSanctionModal);
                    showToast(result.message, 'success');
                    setTimeout(() => window.location.reload(), 1500); // Reload to see changes
                } else {
                    displayModalMessage(approveSanctionMsgDiv, result.message || 'An unknown error occurred.', 'error');
                }
            } catch (error) {
                displayModalMessage(approveSanctionMsgDiv, 'A network error occurred. Please try again.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalBtnHTML;
            }
        });
    }


    // --- SANCTION CONFIGURATION CRUD ---

    // Add Sanction Type
    const addSanctionTypeModal = document.getElementById('addSanctionTypeModal');
    const addSanctionTypeForm = document.getElementById('addSanctionTypeForm');
    document.getElementById('addSanctionTypeBtn')?.addEventListener('click', () => openModal(addSanctionTypeModal));
    
    // Step logic for Add modal
    document.getElementById('nextToAddSanctionStep2')?.addEventListener('click', () => {
        document.getElementById('summarySanctionName').textContent = document.getElementById('newSanctionName').value.toUpperCase();
        document.getElementById('summaryHoursRequired').textContent = document.getElementById('newHoursRequired').value;
        document.getElementById('addSanctionStep1').style.display = 'none';
        document.getElementById('addSanctionStep2').style.display = 'block';
    });
    document.getElementById('backToAddSanctionStep1')?.addEventListener('click', () => {
        document.getElementById('addSanctionStep2').style.display = 'none';
        document.getElementById('addSanctionStep1').style.display = 'block';
    });

    if (addSanctionTypeForm) {
        addSanctionTypeForm.addEventListener('submit', handleFormSubmit.bind(null, addSanctionTypeForm, addSanctionTypeModal, 'addSanctionTypeModalMessage'));
    }

    // Edit Sanction Type
    const editSanctionTypeModal = document.getElementById('editSanctionTypeModal');
    const editSanctionTypeForm = document.getElementById('editSanctionTypeForm');
    document.querySelector('#sanction-config').addEventListener('click', e => {
        const editBtn = e.target.closest('.edit-sanction-type-btn');
        if (editBtn) {
            document.getElementById('editSanctionId').value = editBtn.dataset.id;
            document.getElementById('editSanctionName').value = editBtn.dataset.name;
            document.getElementById('editHoursRequired').value = editBtn.dataset.hours;
            openModal(editSanctionTypeModal);
        }
    });
    if (editSanctionTypeForm) {
        editSanctionTypeForm.addEventListener('submit', handleFormSubmit.bind(null, editSanctionTypeForm, editSanctionTypeModal, 'editSanctionTypeModalMessage'));
    }

    // Delete Sanction Type
    const deleteSanctionTypeModal = document.getElementById('deleteSanctionTypeModal');
    let sanctionIdToDelete = null;
    document.querySelector('#sanction-config').addEventListener('click', e => {
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

    // --- Universal Form Submission Handler for Add/Edit Sanction Type ---
    async function handleFormSubmit(form, modal, messageDivId, event) {
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
    }
});