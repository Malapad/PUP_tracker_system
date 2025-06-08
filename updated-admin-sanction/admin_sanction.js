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
    // --- Tab Switching ---
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetTab = tab.dataset.tab;

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

            currentUrl.searchParams.set('tab', targetTab);
            currentUrl.searchParams.delete('view'); 
            window.history.pushState({ path: currentUrl.href }, '', currentUrl.href);
        });
    });

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

                updateBtn.closest('tr').style.opacity = '0';
                setTimeout(() => {
                    updateBtn.closest('tr').remove();

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

});