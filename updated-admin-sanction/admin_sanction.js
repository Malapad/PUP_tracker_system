function showToast(message, type = 'success', duration = 3000, position = 'top-center') {
    const toast = document.getElementById('toast-notification');
    if (!toast) {
        console.error('Toast element not found!');
        return;
    }
    toast.textContent = message;
    toast.className = 'toast'; // Reset classes
    toast.classList.add(type);
    toast.classList.remove('top-center', 'bottom-center'); // Clear old position
    if (position === 'bottom-center') {
        toast.classList.add('bottom-center');
    } else {
        toast.classList.add('top-center');
    }
    toast.classList.remove('show'); // Remove show class to re-trigger animation
    void toast.offsetWidth; // Trigger reflow
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, duration);
}

document.addEventListener("DOMContentLoaded", () => {
    // Existing elements (from initial admin_sanction.php context)
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');

    const viewSanctionDetailsModal = document.getElementById('viewSanctionDetailsModal');
    const approveSanctionForm = document.getElementById('approveSanctionForm');
    const approveSanctionModalMessage = document.getElementById('approveSanctionModalMessage');

    let activeSanctionComplianceRowActionButtonsContainer = null; // for sanction-compliance table
    let activeDisciplinarySanctionRowActionButtonsContainer = null; // for sanction-config table within accordion

    // NEW: Sanction Configuration Accordion Elements
    const sanctionConfigAccordionHeaders = document.querySelectorAll('#sanction-config .accordion-header');
    const viewConfigHistoryBtn = document.querySelector('#sanction-config .view-history-btn');

    // NEW: Add Sanction Modal elements
    const addSanctionModal = document.getElementById('addSanctionModal');
    const addSanctionForm = document.getElementById('addSanctionForm');
    const addSanctionModalMessage = document.getElementById('addSanctionModalMessage');
    const closeAddSanctionButtons = document.querySelectorAll('.close-modal-add-sanction-button');
    const sanctionViolationTypeNameDisplay = document.getElementById('sanctionViolationTypeNameDisplay');
    const sanctionViolationTypeIdInput = document.getElementById('sanctionViolationTypeId');
    const sanctionViolationTypeNameHidden = document.getElementById('sanctionViolationTypeNameHidden'); // Hidden input for logging
    const offenseLevelSanctionModal = document.getElementById('offenseLevelSanctionModal');
    const disciplinarySanctionText = document.getElementById('disciplinarySanctionText');

    // NEW: Edit Sanction Modal elements
    const editSanctionModal = document.getElementById('editSanctionModal');
    const editSanctionForm = document.getElementById('editSanctionForm');
    const editSanctionModalMessage = document.getElementById('editSanctionModalMessage');
    const closeEditSanctionButtons = document.querySelectorAll('.close-modal-edit-sanction-button');
    const editSanctionViolationTypeNameDisplay = document.getElementById('editSanctionViolationTypeNameDisplay');
    const editDisciplinarySanctionId = document.getElementById('editDisciplinarySanctionId');
    const editSanctionViolationTypeId = document.getElementById('editSanctionViolationTypeId');
    const editSanctionViolationTypeNameHidden = document.getElementById('editSanctionViolationTypeNameHidden'); // Hidden input for logging
    const editOffenseLevelSanctionModal = document.getElementById('editOffenseLevelSanctionModal');
    const editDisciplinarySanctionText = document.getElementById('editDisciplinarySanctionText');

    // NEW: Delete Sanction Modal elements
    const deleteSanctionModal = document.getElementById('deleteSanctionModal');
    const deleteSanctionModalMessage = document.getElementById('deleteSanctionModalMessage');
    const closeDeleteSanctionButtons = document.querySelectorAll('.close-modal-delete-sanction-button');
    const deleteSanctionViolationTypeNameDisplay = document.getElementById('deleteSanctionViolationTypeNameDisplay');
    const deleteSanctionOffenseLevelDisplay = document.getElementById('deleteSanctionOffenseLevelDisplay');
    const deleteSanctionTextDisplay = document.getElementById('deleteSanctionTextDisplay');
    const confirmDeleteSanctionBtn = document.getElementById('confirmDeleteSanctionBtn');
    let currentSanctionIdToDelete = null;

    // NEW: Hidden inputs for delete confirmation logging
    const deleteSanctionViolationTypeIdHidden = document.getElementById('deleteSanctionViolationTypeIdHidden');
    const deleteSanctionViolationTypeNameHidden = document.getElementById('deleteSanctionViolationTypeNameHidden');
    const deleteSanctionOffenseLevelHidden = document.getElementById('deleteSanctionOffenseLevelHidden');
    const deleteSanctionTextHidden = document.getElementById('deleteSanctionTextHidden');

    // NEW: Search bar elements for Sanction Configuration
    const violationTypeSearchInput = document.getElementById('violation-type-search');
    const violationTypeAccordionItems = document.querySelectorAll('.accordion-item.violation-type-item');
    const searchButton = document.querySelector('.sanction-config-search-bar .search-button'); // Get the search button


    // --- Helper Functions ---
    const openModal = (modalElement) => {
        if (modalElement) modalElement.style.display = "block";
    };

    const closeModal = (modalElement, messageDiv = null, formElement = null) => {
        if (modalElement) modalElement.style.display = "none";
        if (messageDiv) {
            messageDiv.textContent = '';
            messageDiv.style.display = 'none';
        }
        if (formElement) formElement.reset();
    };

    const displayModalMessage = (modalMessageDiv, message, type = 'error') => {
        if (modalMessageDiv) {
            modalMessageDiv.textContent = message;
            modalMessageDiv.className = `modal-message ${type}-message`;
            modalMessageDiv.style.display = 'block';
        }
    };

    // --- Global Modal Close Listener (for clicks outside modal and close buttons) ---
    document.body.addEventListener('click', function(e) {
        // Handle all close buttons
        const closeBtn = e.target.closest('[class^="close-modal-"]');
        if (closeBtn) {
            const modalId = closeBtn.dataset.modal || closeBtn.closest('.modal')?.id;
            const modalElement = document.getElementById(modalId);
            if (modalElement) {
                // Determine which modal and associated message/form to clear
                if (modalElement.id === 'viewSanctionDetailsModal') closeModal(modalElement, approveSanctionModalMessage, approveSanctionForm);
                else if (modalElement.id === 'addSanctionModal') closeModal(modalElement, addSanctionModalMessage, addSanctionForm);
                else if (modalElement.id === 'editSanctionModal') closeModal(modalElement, editSanctionModalMessage, editSanctionForm);
                else if (modalElement.id === 'deleteSanctionModal') closeModal(modalElement, deleteSanctionModalMessage, null);
            }
            return; // Prevent fall-through to generic modal close
        }

        // Handle clicks outside the modal content
        if (e.target.classList.contains('modal')) {
            if (e.target.id === 'viewSanctionDetailsModal') closeModal(e.target, approveSanctionModalMessage, approveSanctionForm);
            else if (e.target.id === 'addSanctionModal') closeModal(e.target, addSanctionModalMessage, addSanctionForm);
            else if (e.target.id === 'editSanctionModal') closeModal(e.target, editSanctionModalMessage, editSanctionForm);
            else if (e.target.id === 'deleteSanctionModal') closeModal(e.target, deleteSanctionModalMessage, null);
        }
    });


    // --- Tab Switching ---
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetTab = tab.dataset.tab;

            const currentUrl = new URL(window.location.href);
            // If currently in a history view, redirect to main tab view
            if (currentUrl.searchParams.get('view') === 'history' || currentUrl.searchParams.get('view') === 'sanction_config_history') {
                window.location.href = `?tab=${targetTab}`;
                return;
            }

            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            tabContents.forEach(content => {
                content.style.display = (content.id === targetTab) ? 'block' : 'none';
            });

            currentUrl.searchParams.set('tab', targetTab);
            currentUrl.searchParams.delete('view'); // Clear view param when switching main tabs
            window.history.pushState({ path: currentUrl.href }, '', currentUrl.href);
        });
    });

    // --- Sanction Request Tab Logic ---
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
            openModal(viewSanctionDetailsModal);
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
                    closeModal(viewSanctionDetailsModal, approveSanctionModalMessage, approveSanctionForm);
                    showToast(result.message, 'success');
                    setTimeout(() => window.location.href = window.location.pathname + '?tab=sanction-compliance', 1500);
                } else {
                    displayModalMessage(approveSanctionModalMessage, result.message || 'An unknown error occurred.', 'error');
                }
            } catch (error) {
                displayModalMessage(approveSanctionModalMessage, 'A network error occurred. Please try again.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-check"></i> Approve';
            }
        });
    }

    // --- Sanction Compliance Tab Logic ---
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

                // Visual removal/update
                const row = updateBtn.closest('tr');
                if (row) {
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        // Check if table is empty after removal
                        const tableBody = document.querySelector('#sanction-compliance tbody');
                        if (tableBody && tableBody.rows.length === 0) {
                            const colCount = document.querySelector('#sanction-compliance thead th').length;
                            const statusFilter = new URL(window.location.href).searchParams.get('status_filter') || 'Pending';
                            tableBody.innerHTML = `<tr><td colspan="${colCount}" class="no-records-cell">No ${statusFilter} sanctions found.</td></tr>`;
                        }
                    }, 400);
                }
            } else {
                showToast(result.message, 'error');
                updateBtn.disabled = false;
                updateBtn.innerHTML = originalButtonContent;
            }
        } catch (error) {
            showToast('A network error occurred.', 'error');
            updateBtn.disabled = false;
            updateBtn.innerHTML = originalButtonContent;
        }
    });

    // --- NEW: Sanction Configuration Tab Logic (Accordion-based) ---

    // Function to handle uppercase input
    function handleInputUppercase() { this.value = this.value.toUpperCase(); }

    // Function to add caps lock listeners to relevant inputs
    function addInputListenersForCapslock() {
        const inputsToApplyCapslock = [
            offenseLevelSanctionModal,
            disciplinarySanctionText,
            editOffenseLevelSanctionModal,
            editDisciplinarySanctionText
        ];
        inputsToApplyCapslock.forEach(input => {
            if (input) {
                input.removeEventListener('input', handleInputUppercase); // Remove existing to prevent duplicates
                input.addEventListener('input', handleInputUppercase);
            }
        });
    }

    // Call it on DOM load
    addInputListenersForCapslock();

    // Accordion Logic for Violation Types
    sanctionConfigAccordionHeaders.forEach(header => {
        header.addEventListener('click', async () => {
            const accordionItem = header.parentElement;
            const accordionContent = header.nextElementSibling;
            const violationTypeId = header.dataset.violationTypeId;
            const sanctionTableBody = accordionContent.querySelector('.sanction-table-body');

            // Close other open accordions
            document.querySelectorAll('#sanction-config .accordion-item.active').forEach(openItem => {
                if (openItem !== accordionItem) {
                    openItem.classList.remove('active');
                    openItem.querySelector('.accordion-content').style.maxHeight = null;
                }
            });

            // Toggle current accordion
            accordionItem.classList.toggle('active');
            if (accordionContent.style.maxHeight) {
                accordionContent.style.maxHeight = null;
            } else {
                accordionContent.style.maxHeight = accordionContent.scrollHeight + "px";
                
                // Load sanctions only when opening and if not already loaded
                if (sanctionTableBody.dataset.loaded !== 'true') {
                    sanctionTableBody.innerHTML = `<tr><td colspan='3' class='no-records-cell'><i class="fas fa-spinner fa-spin"></i> Loading sanctions...</td></tr>`;
                    try {
                        const response = await fetch(`${window.location.pathname}?action=get_sanctions_for_violation_type&violation_type_id=${violationTypeId}`);
                        const result = await response.json();

                        if (result.success && result.sanctions) {
                            renderDisciplinarySanctionTable(sanctionTableBody, result.sanctions);
                            sanctionTableBody.dataset.loaded = 'true'; // Mark as loaded
                        } else {
                            sanctionTableBody.innerHTML = `<tr><td colspan='3' class='no-records-cell'>${result.message || 'Error loading sanctions.'}</td></tr>`;
                        }
                    } catch (error) {
                        console.error('Error fetching sanctions:', error);
                        sanctionTableBody.innerHTML = `<tr><td colspan='3' class='no-records-cell'>Network error: Could not load sanctions.</td></td></tr>`;
                    }
                }
            }
        });
    });


    // Initial state: ensure active accordion content height is set on load
    document.querySelectorAll('#sanction-config .accordion-item.active .accordion-content').forEach(content => {
        content.style.maxHeight = content.scrollHeight + "px";
        // Also trigger load for pre-opened accordions
        const sanctionTableBody = content.querySelector('.sanction-table-body');
        const violationTypeId = content.previousElementSibling.dataset.violationTypeId;
        if (sanctionTableBody && violationTypeId && sanctionTableBody.dataset.loaded !== 'true') {
            (async () => {
                sanctionTableBody.innerHTML = `<tr><td colspan='3' class='no-records-cell'><i class="fas fa-spinner fa-spin"></i> Loading sanctions...</td></tr>`;
                try {
                    const response = await fetch(`${window.location.pathname}?action=get_sanctions_for_violation_type&violation_type_id=${violationTypeId}`);
                    const result = await response.json();
                    if (result.success && result.sanctions) {
                        renderDisciplinarySanctionTable(sanctionTableBody, result.sanctions);
                        sanctionTableBody.dataset.loaded = 'true';
                    } else {
                        sanctionTableBody.innerHTML = `<tr><td colspan='3' class='no-records-cell'>${result.message || 'Error loading sanctions.'}</td></tr>`;
                    }
                } catch (error) {
                    console.error('Error fetching sanctions for pre-opened accordion:', error);
                    sanctionTableBody.innerHTML = `<tr><td colspan='3' class='no-records-cell'>Network error: Could not load sanctions.</td></td></tr>`;
                }
            })();
        }
    });


    function renderDisciplinarySanctionTable(tableBodyElement, sanctions) {
        tableBodyElement.innerHTML = ''; // Clear existing rows
        if (sanctions.length === 0) {
            tableBodyElement.innerHTML = `<tr><td colspan='3' class='no-records-cell'>No disciplinary sanctions found for this violation type.</td></tr>`;
            return;
        }

        sanctions.forEach(sanction => {
            const row = document.createElement('tr');
            row.classList.add('disciplinary-sanction-row');
            row.dataset.id = sanction.disciplinary_sanction_id; // Set data-id for selection

            row.innerHTML = `
                <td>${sanction.offense_level || 'N/A'}</td>
                <td>${sanction.disciplinary_sanction || 'N/A'}</td>
                <td class="action-buttons-cell">
                    <div class="action-buttons-container" style="display: none;">
                        <button class='edit-sanction-btn btn-secondary' data-id='${sanction.disciplinary_sanction_id}'><i class='fas fa-edit'></i> Update</button>
                        <button class='delete-sanction-btn btn-danger' data-id='${sanction.disciplinary_sanction_id}'><i class='fas fa-trash-alt'></i> Delete</button>
                    </div>
                </td>
            `;
            tableBodyElement.appendChild(row);
        });
    }

    // NEW: Add Sanction Modal Handlers
    document.querySelectorAll('.add-sanction-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const violationTypeId = this.dataset.violationTypeId;
            const violationTypeName = this.dataset.violationTypeName;

            if (!violationTypeId) { // Should not happen with current setup, but good for safety
                showToast("Cannot add sanction: Violation Type ID is missing.", 'error');
                return;
            }

            sanctionViolationTypeIdInput.value = violationTypeId;
            sanctionViolationTypeNameHidden.value = violationTypeName; // Set hidden input for logging
            sanctionViolationTypeNameDisplay.textContent = violationTypeName;

            closeModal(addSanctionModal, addSanctionModalMessage, addSanctionForm); // Clear form and message
            openModal(addSanctionModal);
            offenseLevelSanctionModal.focus();
            addInputListenersForCapslock(); // Ensure caps lock listener is applied
        });
    });

    closeAddSanctionButtons.forEach(btn => {
        btn.addEventListener('click', () => closeModal(addSanctionModal, addSanctionModalMessage, addSanctionForm));
    });

    if (addSanctionForm) {
        addSanctionForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearModalMessage(addSanctionModalMessage);

            const formData = new FormData(addSanctionForm);
            const submitButton = addSanctionForm.querySelector('button[type="submit"]');
            const originalButtonContent = submitButton ? submitButton.innerHTML : '';

            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

            try {
                const response = await fetch(addSanctionForm.action, { method: 'POST', body: formData });
                if (!response.ok) {
                    let errorMsg = `Server error: ${response.status}`;
                    try {
                        const errorData = await response.json();
                        if (errorData && errorData.message) errorMsg = errorData.message;
                    } catch (jsonError) {}
                    throw new Error(errorMsg);
                }
                const result = await response.json();

                if (result.success) {
                    closeModal(addSanctionModal, addSanctionModalMessage, addSanctionForm);
                    showToast(result.message, 'success', 3000, 'bottom-center');
                    // Refresh the specific sanction table within the accordion
                    const currentViolationTypeId = sanctionViolationTypeIdInput.value;
                    const sanctionTableBody = document.getElementById(`sanction-table-body-${currentViolationTypeId}`);
                    if (sanctionTableBody) {
                        sanctionTableBody.dataset.loaded = 'false'; // Mark as stale
                        const accordionHeader = sanctionTableBody.closest('.accordion-content').previousElementSibling;
                        if (accordionHeader.parentElement.classList.contains('active')) {
                            // If accordion is open, force reload by simulating click to close then reopen
                            accordionHeader.click(); 
                            setTimeout(() => accordionHeader.click(), 50); // Re-open shortly after closing
                        }
                    }
                } else {
                    displayModalMessage(addSanctionModalMessage, result.message || 'An error occurred.', 'error');
                }
            } catch (error) {
                console.error('Add sanction form submission error:', error);
                displayModalMessage(addSanctionModalMessage, 'Submission failed: ' + error.message, 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonContent;
            }
        });
    }

    // NEW: Edit Sanction Modal Handlers
    function openEditSanctionModal(details) {
        if (editSanctionModal && details) {
            editDisciplinarySanctionId.value = details.disciplinary_sanction_id;
            editSanctionViolationTypeId.value = details.violation_type_id;
            editSanctionViolationTypeNameHidden.value = details.violation_type_name; // For logging
            editSanctionViolationTypeNameDisplay.textContent = details.violation_type_name || 'N/A';
            editOffenseLevelSanctionModal.value = details.offense_level || '';
            editDisciplinarySanctionText.value = details.disciplinary_sanction || '';

            closeModal(editSanctionModal, editSanctionModalMessage, editSanctionForm);
            openModal(editSanctionModal);
            addInputListenersForCapslock(); // Ensure caps lock listener is applied
            editOffenseLevelSanctionModal.focus();
        }
    }

    closeEditSanctionButtons.forEach(btn => {
        btn.addEventListener('click', () => closeModal(editSanctionModal, editSanctionModalMessage, editSanctionForm));
    });

    if (editSanctionForm) {
        editSanctionForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearModalMessage(editSanctionModalMessage);

            const formData = new FormData(editSanctionForm);
            const submitButton = editSanctionForm.querySelector('button[type="submit"]');
            const originalButtonContent = submitButton ? submitButton.innerHTML : '';

            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            try {
                const response = await fetch(editSanctionForm.action, { method: 'POST', body: formData });
                if (!response.ok) {
                    let errorMsg = `Server error: ${response.status}`;
                    try {
                        const errorData = await response.json();
                        if (errorData && errorData.message) errorMsg = errorData.message;
                    } catch (jsonError) {}
                    throw new Error(errorMsg);
                }
                const result = await response.json();

                if (result.success) {
                    closeModal(editSanctionModal, editSanctionModalMessage, editSanctionForm);
                    showToast(result.message, 'success', 3000, 'bottom-center');
                    // Refresh the specific sanction table within the accordion
                    const currentViolationTypeId = editSanctionViolationTypeId.value;
                    const sanctionTableBody = document.getElementById(`sanction-table-body-${currentViolationTypeId}`);
                    if (sanctionTableBody) {
                        sanctionTableBody.dataset.loaded = 'false'; // Mark as stale
                        const accordionHeader = sanctionTableBody.closest('.accordion-content').previousElementSibling;
                        if (accordionHeader.parentElement.classList.contains('active')) {
                            accordionHeader.click();
                            setTimeout(() => accordionHeader.click(), 50);
                        }
                    }
                } else {
                    displayModalMessage(editSanctionModalMessage, result.message || 'An error occurred.', 'error');
                }
            } catch (error) {
                console.error('Edit sanction form submission error:', error);
                displayModalMessage(editSanctionModalMessage, 'Submission failed: ' + error.message, 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonContent;
            }
            }
        );
    }

    // NEW: Delete Sanction Modal Handlers
    function openDeleteSanctionModal(details) {
        if (deleteSanctionModal && details) {
            currentSanctionIdToDelete = details.disciplinary_sanction_id;

            deleteSanctionViolationTypeIdHidden.value = details.violation_type_id;
            deleteSanctionViolationTypeNameHidden.value = details.violation_type_name; 
            deleteSanctionOffenseLevelHidden.value = details.offense_level;
            deleteSanctionTextHidden.value = details.disciplinary_sanction;

            deleteSanctionViolationTypeNameDisplay.textContent = details.violation_type_name || 'N/A';
            deleteSanctionOffenseLevelDisplay.textContent = details.offense_level || 'N/A';
            deleteSanctionTextDisplay.textContent = details.disciplinary_sanction || 'N/A';
            
            closeModal(deleteSanctionModal, deleteSanctionModalMessage);
            openModal(deleteSanctionModal);
        }
    }

    closeDeleteSanctionButtons.forEach(btn => {
        btn.addEventListener('click', () => closeModal(deleteSanctionModal, deleteSanctionModalMessage));
    });

    if (confirmDeleteSanctionBtn) {
        confirmDeleteSanctionBtn.addEventListener('click', async () => {
            if (currentSanctionIdToDelete) {
                const submitButton = confirmDeleteSanctionBtn;
                const originalButtonContent = submitButton ? submitButton.innerHTML : '';

                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

                try {
                    const formData = new FormData();
                    formData.append('delete_disciplinary_sanction_id', currentSanctionIdToDelete);
                    formData.append('violation_type_id_hidden', deleteSanctionViolationTypeIdHidden.value); // For logging
                    formData.append('violation_type_name_hidden', deleteSanctionViolationTypeNameHidden.value); // For logging
                    formData.append('offense_level_hidden', deleteSanctionOffenseLevelHidden.value); // For logging
                    formData.append('sanction_details_hidden', deleteSanctionTextHidden.value); // For logging

                    const response = await fetch(window.location.pathname, { method: 'POST', body: formData });
                    if (!response.ok) { throw new Error(`Server error: ${response.status}`); }
                    const result = await response.json();

                    if (result.success) {
                        closeModal(deleteSanctionModal, deleteSanctionModalMessage);
                        showToast(result.message, 'success', 3000, 'bottom-center');
                        // Refresh the specific sanction table within the accordion
                        const currentViolationTypeId = deleteSanctionViolationTypeIdHidden.value;
                        const sanctionTableBody = document.getElementById(`sanction-table-body-${currentViolationTypeId}`);
                        if (sanctionTableBody) {
                            sanctionTableBody.dataset.loaded = 'false'; // Mark as stale
                            const accordionHeader = sanctionTableBody.closest('.accordion-content').previousElementSibling;
                            if (accordionHeader.parentElement.classList.contains('active')) {
                                accordionHeader.click();
                                setTimeout(() => accordionHeader.click(), 50);
                            }
                        }
                    } else {
                        displayModalMessage(deleteSanctionModalMessage, result.message || 'Failed to delete.', 'error');
                    }
                } catch (error) {
                    console.error('Delete sanction submission error:', error);
                    displayModalMessage(deleteSanctionModalMessage, 'Deletion failed: ' + error.message, 'error');
                } finally {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonContent;
                }
            }
        });
    }

    // Event listener for action buttons in the new disciplinary sanctions table (delegated to parent container)
    document.querySelectorAll('.sanction-table-container').forEach(container => {
        container.addEventListener('click', async (e) => {
            const editBtn = e.target.closest('.edit-sanction-btn');
            const deleteBtn = e.target.closest('.delete-sanction-btn');
            const sanctionRow = e.target.closest('.disciplinary-sanction-row');

            // Close other open action button containers for sanctions
            if (activeDisciplinarySanctionRowActionButtonsContainer && activeDisciplinarySanctionRowActionButtonsContainer !== sanctionRow?.querySelector('.action-buttons-container')) {
                activeDisciplinarySanctionRowActionButtonsContainer.style.display = 'none';
            }

            // Toggle action buttons for sanctions
            if (sanctionRow && !editBtn && !deleteBtn && !e.target.closest('.action-buttons-container')) { // Ensure click is not on buttons themselves
                const actionContainer = sanctionRow.querySelector('.action-buttons-container');
                if (actionContainer) {
                    actionContainer.style.display = actionContainer.style.display === 'flex' ? 'none' : 'flex';
                    activeDisciplinarySanctionRowActionButtonsContainer = actionContainer.style.display === 'flex' ? actionContainer : null;
                }
            }

            if (editBtn) {
                const sanctionId = editBtn.dataset.id;
                try {
                    const response = await fetch(`${window.location.pathname}?action=get_disciplinary_sanction_details&id=${sanctionId}`);
                    if (!response.ok) { throw new Error(`Server error: ${response.status}`); }
                    const result = await response.json();
                    if (result.success && result.data) { openEditSanctionModal(result.data); } else { showToast(result.message || "Failed to fetch sanction details for editing.", 'error'); }
                } catch (error) { console.error('Error fetching sanction details:', error); showToast("Error fetching details: " + error.message, 'error'); }
            }
            if (deleteBtn) {
                const sanctionId = deleteBtn.dataset.id;
                try {
                    const response = await fetch(`${window.location.pathname}?action=get_disciplinary_sanction_details&id=${sanctionId}`);
                    if (!response.ok) { throw new Error(`Server error: ${response.status}`); }
                    const result = await response.json();
                    if (result.success && result.data) { openDeleteSanctionModal(result.data); } else { showToast(result.message || "Failed to fetch sanction details for deletion.", 'error'); }
                } catch (error) { console.error('Error fetching sanction details:', error); showToast("Error fetching details: " + error.message, 'error'); }
            }
        });
    });

    // Event listener for "View History" button in Sanction Configuration
    if (viewConfigHistoryBtn) {
        viewConfigHistoryBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('tab', 'sanction-config');
            currentUrl.searchParams.set('view', 'sanction_config_history');
            window.location.href = currentUrl.href;
        });
    }

    // NEW: Search functionality for accordion items
    if (violationTypeSearchInput) {
        // Function to perform the search filter
        const performSearchFilter = () => {
            const searchTerm = violationTypeSearchInput.value.toLowerCase();
            violationTypeAccordionItems.forEach(item => {
                const violationTypeName = item.dataset.violationTypeName.toLowerCase();
                if (violationTypeName.includes(searchTerm)) {
                    item.style.display = ''; // Show the item
                } else {
                    item.style.display = 'none'; // Hide the item
                }
            });
        };

        // Event listener for real-time typing
        violationTypeSearchInput.addEventListener('input', performSearchFilter);

        // Event listener for search button click
        if (searchButton) {
            searchButton.addEventListener('click', performSearchFilter);
        }

        // Event listener for 'Enter' key press on the search input
        violationTypeSearchInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter' || event.keyCode === 13) {
                event.preventDefault(); // Prevent form submission if any
                performSearchFilter();
                this.blur(); // Optional: remove focus from the input after pressing Enter
            }
        });
    }
});