// Function to show toast notifications
function showToast(message, type = 'success', duration = 3000, position = 'top-center') {
    const toast = document.getElementById('toast-notification');
    if (!toast) {
        console.error('Toast element not found!');
        return;
    }

    toast.textContent = message;
    toast.className = 'toast'; // Reset classes
    toast.classList.add(type);

    // Position handling
    toast.classList.remove('top-center', 'bottom-center'); // Clear previous positions
    if (position === 'bottom-center') {
        toast.classList.add('bottom-center');
    } else {
        toast.classList.add('top-center');
    }

    toast.classList.remove('show');
    void toast.offsetWidth; // Trigger reflow for animation reset
    toast.classList.add('show');

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
                if (content.id === targetTab) {
                    content.style.display = 'block';
                } else {
                    content.style.display = 'none';
                }
            });

            // Update URL to reflect active tab for refresh/bookmarking
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('tab', targetTab);
            window.history.pushState({ path: currentUrl.href }, '', currentUrl.href);
        });
    });

    // --- Common Modal Functions ---
    function openModal(modalElement) {
        if (modalElement) {
            modalElement.style.display = "block";
        }
    }

    function closeModal(modalElement) {
        if (modalElement) {
            modalElement.style.display = "none";
            // Find and clear any message divs within this modal
            const messageDiv = modalElement.querySelector('.modal-message');
            if (messageDiv) {
                clearModalMessage(messageDiv);
            }
            // Reset forms if applicable
            const form = modalElement.querySelector('form');
            if (form) {
                form.reset();
            }
            // Reset modal steps if applicable (for addSanctionTypeModal)
            if (modalElement.id === 'addSanctionTypeModal') {
                document.getElementById('addSanctionStep1').style.display = 'block';
                document.getElementById('addSanctionStep2').style.display = 'none';
            }
        }
    }

    // Generic display message function for modals
    function displayModalMessage(modalMessageDiv, message, type = 'error') {
        if (modalMessageDiv) {
            modalMessageDiv.textContent = message;
            modalMessageDiv.className = `modal-message ${type}-message`;
            modalMessageDiv.style.display = 'block';
        } else {
            console.error("Modal message div not found for message:", message);
            alert(message);
        }
    }

    // Generic clear message function for modals
    function clearModalMessage(modalMessageDiv) {
        if (modalMessageDiv) {
            modalMessageDiv.textContent = '';
            modalMessageDiv.style.display = 'none';
        }
    }

    // --- Modals Global Close Logic (Clicking outside or close button) ---
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        // Close when clicking outside modal content
        modal.addEventListener('click', function(event) {
            if (event.target === this) { // Only close if clicking the modal overlay itself
                closeModal(this);
            }
        });
    });

    // Close buttons within modals (using event delegation for efficiency)
    document.body.addEventListener('click', function(e) {
        const closeBtn = e.target.closest('.close-modal-button');
        if (closeBtn) {
            const modalId = closeBtn.dataset.modal;
            const modalElement = document.getElementById(modalId);
            closeModal(modalElement);
        }
    });

    // --- Sanction Configuration Tab Logic ---

    // Add Sanction Type Modal
    const addSanctionTypeModal = document.getElementById('addSanctionTypeModal');
    const addSanctionTypeBtn = document.getElementById('addSanctionTypeBtn'); // Button to open modal
    const addSanctionTypeForm = document.getElementById('addSanctionTypeForm');
    const addSanctionTypeModalMessageDiv = document.getElementById('addSanctionTypeModalMessage');

    const addSanctionStep1 = document.getElementById('addSanctionStep1');
    const addSanctionStep2 = document.getElementById('addSanctionStep2');
    const newSanctionNameInput = document.getElementById('newSanctionName');
    const newHoursRequiredInput = document.getElementById('newHoursRequired');
    const nextToAddSanctionStep2Btn = document.getElementById('nextToAddSanctionStep2');
    const backToAddSanctionStep1Btn = document.getElementById('backToAddSanctionStep1');

    if (addSanctionTypeBtn) {
        addSanctionTypeBtn.addEventListener('click', () => {
            closeModal(addSanctionTypeModal); // Ensures reset
            openModal(addSanctionTypeModal);
            newSanctionNameInput.focus();
            addInputListenersForCapslock(); // Re-add listener for uppercase inputs
        });
    }

    if (nextToAddSanctionStep2Btn) {
        nextToAddSanctionStep2Btn.addEventListener('click', () => {
            const sanctionName = newSanctionNameInput.value.trim();
            const hoursRequired = newHoursRequiredInput.value; // Value is string, but number input validates
            let isValid = true;
            let errorMessage = '';

            if (sanctionName === '') {
                errorMessage += 'Sanction Type Name is required. ';
                isValid = false;
            }
            if (hoursRequired === '' || isNaN(parseFloat(hoursRequired)) || parseFloat(hoursRequired) < 0) {
                errorMessage += 'Hours must be a non-negative number. ';
                isValid = false;
            }

            if (!isValid) {
                displayModalMessage(addSanctionTypeModalMessageDiv, errorMessage, 'error');
                return;
            }

            document.getElementById('summarySanctionName').textContent = sanctionName.toUpperCase();
            document.getElementById('summaryHoursRequired').textContent = hoursRequired;
            clearModalMessage(addSanctionTypeModalMessageDiv);
            addSanctionStep1.style.display = 'none';
            addSanctionStep2.style.display = 'block';
        });
    }

    if (backToAddSanctionStep1Btn) {
        backToAddSanctionStep1Btn.addEventListener('click', () => {
            clearModalMessage(addSanctionTypeModalMessageDiv);
            addSanctionStep2.style.display = 'none';
            addSanctionStep1.style.display = 'block';
            newSanctionNameInput.focus();
        });
    }

    // Add Sanction Type Form Submission (AJAX)
    if (addSanctionTypeForm) {
        addSanctionTypeForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearModalMessage(addSanctionTypeModalMessageDiv);

            const submitButton = addSanctionTypeForm.querySelector('button[type="submit"]');
            const originalButtonContent = submitButton ? submitButton.innerHTML : '';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';
            }

            const formData = new FormData(addSanctionTypeForm);

            try {
                const response = await fetch(addSanctionTypeForm.action, {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    let errorMsg = `Server error: ${response.status}`;
                    try {
                        const errorData = await response.json();
                        if (errorData && errorData.message) errorMsg = errorData.message;
                    } catch (jsonError) { /* Ignore */ }
                    throw new Error(errorMsg);
                }

                const result = await response.json();

                if (result.success) {
                    closeModal(addSanctionTypeModal);
                    showToast(result.message, 'success', 3000, 'bottom-center');
                    // Refresh the Sanction Configuration tab to show new entry
                    window.location.href = window.location.pathname + '?tab=sanction-config';
                } else {
                    displayModalMessage(addSanctionTypeModalMessageDiv, result.message || "An error occurred.", 'error');
                }

            } catch (error) {
                console.error('Add sanction type form submission error:', error);
                displayModalMessage(addSanctionTypeModalMessageDiv, "Submission failed: " + error.message, 'error');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonContent;
                }
            }
        });
    }


    // Edit Sanction Type Modal
    const editSanctionTypeModal = document.getElementById('editSanctionTypeModal');
    const editSanctionTypeForm = document.getElementById('editSanctionTypeForm');
    const editSanctionTypeModalMessageDiv = document.getElementById('editSanctionTypeModalMessage');
    const editSanctionNameInput = document.getElementById('editSanctionName');
    const editHoursRequiredInput = document.getElementById('editHoursRequired');


    // Handle clicks on "Update" buttons in the sanction config table (using event delegation)
    document.querySelector('#sanction-config').addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-sanction-type-btn');
        if (editBtn) {
            const sanctionId = editBtn.dataset.id;
            const sanctionName = editBtn.dataset.name;
            const hoursRequired = editBtn.dataset.hours;

            // Handle default (hardcoded) sanctions separately
            if (sanctionId.startsWith('default_')) {
                showToast("Default sanction types cannot be updated.", 'error', 3000, 'bottom-center');
                return;
            }

            document.getElementById('editSanctionId').value = sanctionId;
            document.getElementById('editSanctionName').value = sanctionName;
            document.getElementById('editHoursRequired').value = hoursRequired; // Set hours in input

            clearModalMessage(editSanctionTypeModalMessageDiv);
            openModal(editSanctionTypeModal);
            editSanctionNameInput.focus();
            addInputListenersForCapslock(); // Re-add listener for uppercase inputs
        }
    });

    // Edit Sanction Type Form Submission (AJAX)
    if (editSanctionTypeForm) {
        editSanctionTypeForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearModalMessage(editSanctionTypeModalMessageDiv);

            const submitButton = editSanctionTypeForm.querySelector('button[type="submit"]');
            const originalButtonContent = submitButton ? submitButton.innerHTML : '';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            }

            const formData = new FormData(editSanctionTypeForm);

            try {
                const response = await fetch(editSanctionTypeForm.action, {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    let errorMsg = `Server error: ${response.status}`;
                    try {
                        const errorData = await response.json();
                        if (errorData && errorData.message) errorMsg = errorData.message;
                    } catch (jsonError) { /* Ignore */ }
                    throw new Error(errorMsg);
                }

                const result = await response.json();

                if (result.success) {
                    closeModal(editSanctionTypeModal);
                    showToast(result.message, 'success', 3000, 'bottom-center');
                    // Refresh the Sanction Configuration tab
                    window.location.href = window.location.pathname + '?tab=sanction-config';
                } else {
                    displayModalMessage(editSanctionTypeModalMessageDiv, result.message || "An error occurred.", 'error');
                }

            } catch (error) {
                console.error('Edit sanction type form submission error:', error);
                displayModalMessage(editSanctionTypeModalMessageDiv, "Submission failed: " + error.message, 'error');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonContent;
                }
            }
        });
    }

    // Delete Sanction Type Modal
    const deleteSanctionTypeModal = document.getElementById('deleteSanctionTypeModal');
    const confirmDeleteSanctionTypeBtn = document.getElementById('confirmDeleteSanctionTypeBtn');
    const deleteSanctionTypeModalMessageDiv = document.getElementById('deleteSanctionTypeModalMessage');
    let currentSanctionTypeIdToDelete = null;

    // Handle clicks on "Delete" buttons in the sanction config table (using event delegation)
    document.querySelector('#sanction-config').addEventListener('click', async (e) => {
        const deleteBtn = e.target.closest('.delete-sanction-type-btn');
        if (deleteBtn) {
            const sanctionId = deleteBtn.dataset.id;
            const sanctionName = deleteBtn.dataset.name;

            // Handle default (hardcoded) sanctions separately
            if (sanctionId.startsWith('default_')) {
                showToast("Default sanction types cannot be deleted.", 'error', 3000, 'bottom-center');
                return;
            }

            currentSanctionTypeIdToDelete = sanctionId;
            document.getElementById('deleteSanctionTypeDisplay').textContent = sanctionName;
            clearModalMessage(deleteSanctionTypeModalMessageDiv);
            openModal(deleteSanctionTypeModal);
        }
    });

    // Confirm Delete button handler
    if (confirmDeleteSanctionTypeBtn) {
        confirmDeleteSanctionTypeBtn.addEventListener('click', async () => {
            if (currentSanctionTypeIdToDelete) {
                const submitButton = confirmDeleteSanctionTypeBtn;
                const originalButtonContent = submitButton ? submitButton.innerHTML : '';
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
                }

                const formData = new FormData();
                formData.append('delete_sanction_id', currentSanctionTypeIdToDelete);

                try {
                    const response = await fetch(window.location.pathname, {
                        method: 'POST',
                        body: formData
                    });

                    if (!response.ok) {
                        let errorMsg = `Server error: ${response.status}`;
                        try {
                            const errorData = await response.json();
                            if (errorData && errorData.message) errorMsg = errorData.message;
                        } catch (jsonError) { /* Ignore */ }
                        throw new Error(errorMsg);
                    }

                    const result = await response.json();

                    if (result.success) {
                        closeModal(deleteSanctionTypeModal);
                        showToast(result.message, 'success', 3000, 'bottom-center');
                        window.location.href = window.location.pathname + '?tab=sanction-config'; // Refresh tab
                    } else {
                        displayModalMessage(deleteSanctionTypeModalMessageDiv, result.message || "Failed to delete.", 'error');
                    }
                } catch (error) {
                    console.error('Delete submission error:', error);
                    displayModalMessage(deleteSanctionTypeModalMessageDiv, "Deletion failed: " + error.message, 'error');
                } finally {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonContent;
                    }
                }
            }
        });
    }


    // View Sanction Details Modal (for Sanction Request table "View/Manage" button)
    const viewSanctionDetailsModal = document.getElementById('viewSanctionDetailsModal');

    // Event delegation for "View/Manage" buttons in Sanction Request tab
    document.querySelector('#sanction-request').addEventListener('click', (e) => {
        const viewBtn = e.target.closest('.view-sanction-details-btn');
        if (viewBtn) {
            // Get all data from the data attributes
            const studentNumber = viewBtn.dataset.studentNumber;
            const studentName = viewBtn.dataset.studentName;
            const course = viewBtn.dataset.course;
            const year = viewBtn.dataset.year;
            const dateSubmitted = viewBtn.dataset.dateSubmitted;
            const violationType = viewBtn.dataset.violationType;
            const offense = viewBtn.dataset.offense;
            const sanction = viewBtn.dataset.sanction;
            const deadline = viewBtn.dataset.deadline;
            const statusText = viewBtn.dataset.statusText;
            const statusClass = viewBtn.dataset.statusClass; // This will already be like "status-pending"

            // Populate viewSanctionDetailsModal with student-specific sanction data
            document.getElementById('detailStudentNumber').textContent = studentNumber;
            document.getElementById('detailStudentName').textContent = studentName;
            document.getElementById('detailCourse').textContent = course;
            document.getElementById('detailYear').textContent = year;
            document.getElementById('detailDateSubmitted').textContent = dateSubmitted;
            document.getElementById('detailViolationType').textContent = violationType;
            document.getElementById('detailOffense').textContent = offense;
            document.getElementById('detailSanction').textContent = sanction;
            document.getElementById('detailDeadline').textContent = deadline;
            
            // Set status text and class
            const detailSanctionStatusElement = document.getElementById('detailSanctionStatus');
            detailSanctionStatusElement.textContent = statusText;
            detailSanctionStatusElement.className = 'status-badge ' + statusClass; // Ensure base class 'status-badge' is always there


            openModal(viewSanctionDetailsModal);
        }
    });

    // --- Force uppercase for relevant inputs ---
    function addInputListenersForCapslock() {
        const inputsToForceUppercase = [
            document.getElementById('newSanctionName'),
            document.getElementById('editSanctionName')
        ];

        inputsToForceUppercase.forEach(input => {
            if (input) {
                input.removeEventListener('input', handleInputUppercase); // Prevent duplicate listeners
                input.addEventListener('input', handleInputUppercase);
            }
        });
    }

    function handleInputUppercase() {
        this.value = this.value.toUpperCase();
    }

    // Initial call to set up listeners for static inputs (e.g., if a modal is pre-filled)
    addInputListenersForCapslock();
});