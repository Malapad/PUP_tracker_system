// Updated showToast function to handle center-bottom position and specific success styling
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
    // --- Existing Student Violation Modal Elements ---
    const studentViolationModal = document.getElementById("modal");
    const addStudentViolationBtn = document.getElementById("addViolationBtn");
    const closeStudentViolationModalBtn = document.getElementById("closeModal");
    const studentViolationForm = document.getElementById("violationForm");
    const studentModalMessageDiv = document.getElementById("modalMessage");
    const closeStudentModalInHeader = document.querySelector("#modal .head-modal .close-modal-button");

    // --- New Violation Type Configuration Modals Elements ---

    // Add New Violation Category + Type Modal (Multi-step RE-INTRODUCED)
    const addViolationCategoryModal = document.getElementById("addViolationCategoryModal");
    const addViolationCategoryBtn = document.getElementById("addViolationCategoryBtn"); // Global button
    const closeAddViolationCategoryModalBtn = document.querySelector("#addViolationCategoryModal .close-modal-category-button");
    const addViolationCategoryForm = document.getElementById("addViolationCategoryForm");
    const addViolationCategoryModalMessageDiv = document.getElementById("addViolationCategoryModalMessage");

    const addCategoryStep1 = document.getElementById("addCategoryStep1");
    const addCategoryStep2 = document.getElementById("addCategoryStep2");
    const nextToCategoryStep2Btn = document.getElementById("nextToCategoryStep2");
    const backToCategoryStep1Btn = document.getElementById("backToCategoryStep1");
    const cancelCategoryStep1Btn = document.getElementById("cancelCategoryStep1");
    const cancelCategoryStep2Btn = document.getElementById("cancelCategoryStep2");


    // Add Violation Type to Existing Category Modal (Single-step REVISED)
    const addTypeToCategoryModal = document.getElementById("addTypeToCategoryModal");
    const closeAddTypeToCategoryModalBtn = document.getElementById("closeAddTypeToCategoryModal");
    const addTypeToCategoryForm = document.getElementById("addTypeToCategoryForm");
    const addTypeToCategoryModalMessageDiv = document.getElementById("addTypeToCategoryModalMessage");
    const closeAddTypeToCategoryModalInHeader = document.querySelector("#addTypeToCategoryModal .head-modal .close-modal-add-type-button");


    // Edit Violation Type Modal
    const editViolationTypeModal = document.getElementById("editViolationTypeModal");
    const editViolationTypeForm = document.getElementById("editViolationTypeForm");
    const editViolationTypeModalMessageDiv = document.getElementById("editViolationTypeModalMessage");
    const closeEditViolationTypeModalBtn = document.getElementById("cancelEditViolationTypeModal");
    const closeEditModalInHeader = document.querySelector("#editViolationTypeModal .head-modal .close-modal-edit-button");

    // Delete Violation Type Modal
    const deleteViolationTypeModal = document.getElementById("deleteViolationTypeModal");
    const deleteViolationTypeModalMessageDiv = document.getElementById("deleteViolationTypeModalMessage");
    const closeDeleteViolationTypeModalBtn = document.getElementById("cancelDeleteViolationTypeModal");
    const closeDeleteModalInHeader = document.querySelector("#deleteViolationTypeModal .head-modal .close-modal-delete-button");
    const confirmDeleteViolationTypeBtn = document.getElementById("confirmDeleteViolationTypeBtn");

    // --- Common Elements ---
    const refreshTableBtn = document.getElementById('refreshTableBtn');
    const tableSpinner = document.getElementById('tableSpinner'); // Student table spinner
    const filterForm = document.getElementById('filter-form');
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');

    // --- Accordion Elements ---
    const accordionHeaders = document.querySelectorAll('.accordion-header');

    // --- Variables for Edit/Delete Operations ---
    let currentViolationTypeIdToDelete = null;
    let activeRowActionButtonsContainer = null; // To keep track of the currently visible action buttons


    // Function to show spinner for student table
    function showTableSpinner() {
        if (tableSpinner) {
            tableSpinner.style.display = 'flex';
        }
    }

    // Generic display message function for modals
    function displayModalMessage(modalMessageDiv, message, type = 'error') {
        if (modalMessageDiv) {
            modalMessageDiv.textContent = message;
            modalMessageDiv.className = `modal-message ${type}-message`;
            modalMessageDiv.style.display = 'block';
        } else {
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


    // --- Tab Switching Logic ---
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

            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('tab', targetTab);
            window.history.pushState({ path: currentUrl.href }, '', currentUrl.href);
        });
    });

    // --- Accordion Logic ---
    accordionHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const accordionItem = header.parentElement;
            const accordionContent = header.nextElementSibling;

            // Close other open accordions
            document.querySelectorAll('.accordion-item.active').forEach(openItem => {
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
            }
        });
    });

    // Handle initial active accordion
    document.querySelectorAll('.accordion-item.active .accordion-content').forEach(content => {
        content.style.maxHeight = content.scrollHeight + "px";
    });


    // --- Student Violation Modal Open/Close (Existing) ---
    if (addStudentViolationBtn) {
        addStudentViolationBtn.addEventListener("click", () => {
            if (studentViolationModal) {
                studentViolationModal.style.display = "block";
                studentViolationForm.reset();
                clearModalMessage(studentModalMessageDiv);
                document.getElementById("studentNumber").focus();
            }
        });
    }

    function closeStudentViolationModal() {
        if (studentViolationModal) studentViolationModal.style.display = "none";
        studentViolationForm.reset();
        clearModalMessage(studentModalMessageDiv);
    }

    if (closeStudentViolationModalBtn) {
        closeStudentViolationModalBtn.addEventListener("click", closeStudentViolationModal);
    }
    if (closeStudentModalInHeader) {
        closeStudentModalInHeader.addEventListener("click", closeStudentViolationModal);
    }

    // --- Add New Violation Category Modal Multi-Step Logic (NEW) ---
    if (addViolationCategoryBtn) {
        addViolationCategoryBtn.addEventListener("click", () => {
            if (addViolationCategoryModal) {
                addViolationCategoryModal.style.display = "block";
                addViolationCategoryForm.reset();
                clearModalMessage(addViolationCategoryModalMessageDiv);
                // Reset to step 1
                addCategoryStep1.style.display = 'block';
                addCategoryStep2.style.display = 'none';
                document.getElementById("newCategoryName").focus();
                addInputListenersForCapslock(); // Re-apply for new inputs
            }
        });
    }

    function closeAddViolationCategoryModal() {
        if (addViolationCategoryModal) addViolationCategoryModal.style.display = "none";
        addViolationCategoryForm.reset();
        clearModalMessage(addViolationCategoryModalMessageDiv);
        // Ensure reset to step 1 state
        addCategoryStep1.style.display = 'block';
        addCategoryStep2.style.display = 'none';
    }

    if (closeAddViolationCategoryModalBtn) {
        closeAddViolationCategoryModalBtn.addEventListener("click", closeAddViolationCategoryModal);
    }
    if (cancelCategoryStep1Btn) {
        cancelCategoryStep1Btn.addEventListener("click", closeAddViolationCategoryModal);
    }
    if (cancelCategoryStep2Btn) {
        cancelCategoryStep2Btn.addEventListener("click", closeAddViolationCategoryModal);
    }

    if (nextToCategoryStep2Btn) {
        nextToCategoryStep2Btn.addEventListener("click", () => {
            const newCategoryNameInput = document.getElementById("newCategoryName");
            if (!newCategoryNameInput.value.trim()) {
                displayModalMessage(addViolationCategoryModalMessageDiv, "Please enter Violation Category Name.", 'error');
                return;
            }
            clearModalMessage(addViolationCategoryModalMessageDiv);
            addCategoryStep1.style.display = 'none';
            addCategoryStep2.style.display = 'block';
            document.getElementById("newResolutionNumberCatModal").focus();
        });
    }

    if (backToCategoryStep1Btn) {
        backToCategoryStep1Btn.addEventListener("click", () => {
            clearModalMessage(addViolationCategoryModalMessageDiv);
            addCategoryStep2.style.display = 'none';
            addCategoryStep1.style.display = 'block';
            document.getElementById("newCategoryName").focus();
        });
    }

    // --- Add Violation Type to Existing Category Modal Logic (NEW) ---
    // Event delegation for "Add Type" buttons inside accordions
    document.querySelector('#Configuration').addEventListener('click', (e) => {
        const addTypeBtn = e.target.closest('.add-type-to-category-btn');
        if (addTypeBtn) {
            if (addTypeToCategoryModal) {
                addTypeToCategoryModal.style.display = "block";
                addTypeToCategoryForm.reset();
                clearModalMessage(addTypeToCategoryModalMessageDiv);

                // Pre-fill and disable the category input
                const categoryName = addTypeBtn.dataset.categoryName;
                document.getElementById("existingCategoryName").value = categoryName;

                document.getElementById("newResolutionNumberTypeModal").focus();
                addInputListenersForCapslock(); // Apply capslock listeners
            }
        }
    });

    function closeAddTypeToCategoryModal() {
        if (addTypeToCategoryModal) addTypeToCategoryModal.style.display = "none";
        addTypeToCategoryForm.reset();
        clearModalMessage(addTypeToCategoryModalMessageDiv);
    }

    if (closeAddTypeToCategoryModalBtn) {
        closeAddTypeToCategoryModalBtn.addEventListener("click", closeAddTypeToCategoryModal);
    }
    if (closeAddTypeToCategoryModalInHeader) {
        closeAddTypeToCategoryModalInHeader.addEventListener("click", closeAddTypeToCategoryModal);
    }


    // --- Edit Violation Type Modal Logic (EXISTING) ---
    function openEditViolationTypeModal(details) {
        if (editViolationTypeModal && details) {
            document.getElementById("editViolationTypeId").value = details.violation_type_id;
            document.getElementById("editResolutionNumberConfig").value = details.resolution_number || '';
            document.getElementById("editViolationCategoryConfig").value = details.category_name || '';
            document.getElementById("editViolationTypeConfig").value = details.violation_type || '';
            document.getElementById("editViolationDescriptionConfig").value = details.violation_description || '';

            clearModalMessage(editViolationTypeModalMessageDiv);
            editViolationTypeModal.style.display = "block";
            document.getElementById("editResolutionNumberConfig").focus();
            addInputListenersForCapslock(); // Apply capslock listeners to edit modal inputs
        }
    }

    function closeEditViolationTypeModal() {
        if (editViolationTypeModal) editViolationTypeModal.style.display = "none";
        editViolationTypeForm.reset();
        clearModalMessage(editViolationTypeModalMessageDiv);
    }

    if (closeEditViolationTypeModalBtn) {
        closeEditViolationTypeModalBtn.addEventListener("click", closeEditViolationTypeModal);
    }
    if (closeEditModalInHeader) {
        closeEditModalInHeader.addEventListener("click", closeEditViolationTypeModal);
    }


    // --- Delete Violation Type Modal Logic (EXISTING) ---
    function openDeleteViolationTypeModal(details) {
        if (deleteViolationTypeModal && details) {
            currentViolationTypeIdToDelete = details.violation_type_id;
            document.getElementById("deleteViolationCategoryDisplay").textContent = details.category_name || 'N/A';
            document.getElementById("deleteViolationTypeDisplay").textContent = details.violation_type || 'N/A';
            document.getElementById("deleteViolationDescriptionDisplay").textContent = details.violation_description || 'N/A';

            clearModalMessage(deleteViolationTypeModalMessageDiv);
            deleteViolationTypeModal.style.display = "block";
        }
    }

    function closeDeleteViolationTypeModal() {
        if (deleteViolationTypeModal) deleteViolationTypeModal.style.display = "none";
        currentViolationTypeIdToDelete = null; // Clear ID
        clearModalMessage(deleteViolationTypeModalMessageDiv);
    }

    if (closeDeleteViolationTypeModalBtn) {
        closeDeleteViolationTypeModalBtn.addEventListener("click", closeDeleteViolationTypeModal);
    }
    if (closeDeleteModalInHeader) {
        closeDeleteModalInHeader.addEventListener("click", closeDeleteViolationTypeModal);
    }
    if (confirmDeleteViolationTypeBtn) {
        confirmDeleteViolationTypeBtn.addEventListener("click", async () => {
            if (currentViolationTypeIdToDelete) {
                const submitButton = confirmDeleteViolationTypeBtn;
                const originalButtonContent = submitButton ? submitButton.innerHTML : '';
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
                }

                try {
                    const formData = new FormData();
                    formData.append('delete_violation_type_id', currentViolationTypeIdToDelete);

                    const response = await fetch(window.location.pathname, { // Post to same PHP file
                        method: 'POST',
                        body: formData
                    });

                    if (!response.ok) {
                        throw new Error(`Server error: ${response.status}`);
                    }

                    const result = await response.json();

                    if (result.success) {
                        closeDeleteViolationTypeModal();
                        showToast(result.message, 'success', 3000, 'bottom-center');
                        window.location.href = window.location.pathname + '?tab=Configuration'; // Refresh tab
                    } else {
                        displayModalMessage(deleteViolationTypeModalMessageDiv, result.message || "Failed to delete.", 'error');
                    }
                } catch (error) {
                    console.error('Delete submission error:', error);
                    displayModalMessage(deleteViolationTypeModalMessageDiv, "Deletion failed: " + error.message, 'error');
                } finally {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonContent;
                    }
                }
            }
        });
    }

    // Close all modals when clicking outside
    window.addEventListener("click", (event) => {
        if (event.target === studentViolationModal) {
            closeStudentViolationModal();
        }
        if (event.target === addViolationCategoryModal) {
            closeAddViolationCategoryModal();
        }
        if (event.target === addTypeToCategoryModal) {
            closeAddTypeToCategoryModal();
        }
        if (event.target === editViolationTypeModal) {
            closeEditViolationTypeModal();
        }
        if (event.target === deleteViolationTypeModal) {
            closeDeleteViolationTypeModal();
        }
    });

    // --- Event Listeners for Edit/Delete Buttons on Table Rows ---
    // Use event delegation for dynamically added rows/buttons
    document.querySelector('#Configuration').addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-violation-type-btn');
        const deleteBtn = e.target.closest('.delete-violation-type-btn');
        const violationRow = e.target.closest('.violation-type-row');

        // Hide any previously active action buttons
        if (activeRowActionButtonsContainer && activeRowActionButtonsContainer !== violationRow?.querySelector('.action-buttons-container')) {
            activeRowActionButtonsContainer.style.display = 'none';
        }

        // Toggle action buttons visibility on row click
        if (violationRow && !editBtn && !deleteBtn) {
            const actionContainer = violationRow.querySelector('.action-buttons-container');
            if (actionContainer) {
                actionContainer.style.display = actionContainer.style.display === 'flex' ? 'none' : 'flex';
                activeRowActionButtonsContainer = actionContainer.style.display === 'flex' ? actionContainer : null;
            }
        }

        if (editBtn) {
            const violationTypeId = editBtn.dataset.id;
            try {
                const response = await fetch(`${window.location.pathname}?action=get_violation_type_details&id=${violationTypeId}`);
                if (!response.ok) {
                    throw new Error(`Server error: ${response.status}`);
                }
                const result = await response.json();
                if (result.success && result.data) {
                    openEditViolationTypeModal(result.data);
                } else {
                    showToast(result.message || "Failed to fetch violation details for editing.", 'error');
                }
            } catch (error) {
                console.error('Error fetching violation details:', error);
                showToast("Error fetching details: " + error.message, 'error');
            }
        }

        if (deleteBtn) {
            const violationTypeId = deleteBtn.dataset.id;
            try {
                const response = await fetch(`${window.location.pathname}?action=get_violation_type_details&id=${violationTypeId}`);
                if (!response.ok) {
                    throw new Error(`Server error: ${response.status}`);
                }
                const result = await response.json();
                if (result.success && result.data) {
                    openDeleteViolationTypeModal(result.data);
                } else {
                    showToast(result.message || "Failed to fetch violation details for deletion.", 'error');
                }
            } catch (error) {
                console.error('Error fetching violation details:', error);
                showToast("Error fetching details: " + error.message, 'error');
            }
        }
    });


    // --- Student Violation Form Submission (Existing) ---
    if (studentViolationForm) {
        studentViolationForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            clearModalMessage(studentModalMessageDiv);

            const studentNumberInput = document.getElementById("studentNumber");
            const violationTypeInput = document.getElementById("violationType");

            if (!studentNumberInput.value.trim()) {
                displayModalMessage(studentModalMessageDiv, "Please enter Student Number.", 'error');
                return;
            }
            if (!violationTypeInput.value) {
                displayModalMessage(studentModalMessageDiv, "Please select a Violation Type.", 'error');
                return;
            }

            const formData = new FormData(studentViolationForm);
            const submitButton = studentViolationForm.querySelector('button[type="submit"]');
            const originalButtonContent = submitButton ? submitButton.innerHTML : '';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            }

            try {
                const response = await fetch(studentViolationForm.action, {
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
                    closeStudentViolationModal();
                    showToast(result.message, 'success', 3000);
                    showTableSpinner();
                    window.location.href = window.location.pathname + '?tab=Violation';
                } else {
                    displayModalMessage(studentModalMessageDiv, result.message || "An error occurred.", 'error');
                }

            } catch (error) {
                console.error('Student violation form submission error:', error);
                displayModalMessage(studentModalMessageDiv, "Submission failed: " + error.message, 'error');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonContent;
                }
            }
        });
    }

    // --- Add New Violation Category + Type Form Submission (NEW Multi-Step) ---
    if (addViolationCategoryForm) {
        addViolationCategoryForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            clearModalMessage(addViolationCategoryModalMessageDiv);

            const newCategoryName = document.getElementById("newCategoryName").value.trim();
            const newResolutionNumber = document.getElementById("newResolutionNumberCatModal").value.trim();
            const newViolationType = document.getElementById("newViolationTypeCatModal").value.trim();
            const newViolationDescription = document.getElementById("newViolationDescriptionCatModal").value.trim();

            if (!newCategoryName || !newResolutionNumber || !newViolationType || !newViolationDescription) {
                displayModalMessage(addViolationCategoryModalMessageDiv, "All fields are required.", 'error');
                return;
            }

            const formData = new FormData();
            formData.append('add_new_category_and_type', '1');
            formData.append('new_category_name', newCategoryName);
            formData.append('new_resolution_number_cat_modal', newResolutionNumber);
            formData.append('new_violation_type_cat_modal', newViolationType);
            formData.append('new_violation_description_cat_modal', newViolationDescription);


            const submitButton = addViolationCategoryForm.querySelector('button[type="submit"]');
            const originalButtonContent = submitButton ? submitButton.innerHTML : '';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';
            }

            try {
                const response = await fetch(addViolationCategoryForm.action, {
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
                    closeAddViolationCategoryModal();
                    showToast(result.message, 'success', 3000, 'bottom-center');
                    window.location.href = window.location.pathname + '?tab=Configuration'; // Refresh tab
                } else {
                    displayModalMessage(addViolationCategoryModalMessageDiv, result.message || "An error occurred.", 'error');
                }

            } catch (error) {
                console.error('Add category+type form submission error:', error);
                displayModalMessage(addViolationCategoryModalMessageDiv, "Submission failed: " + error.message, 'error');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonContent;
                }
            }
        });
    }

    // --- Add Violation Type to Existing Category Form Submission (NEW Single-Step) ---
    if (addTypeToCategoryForm) {
        addTypeToCategoryForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            clearModalMessage(addTypeToCategoryModalMessageDiv);

            const newResolutionNumber = document.getElementById("newResolutionNumberTypeModal").value.trim();
            const newViolationType = document.getElementById("newViolationTypeTypeModal").value.trim();
            const newViolationDescription = document.getElementById("newViolationDescriptionTypeModal").value.trim();
            const existingCategoryName = document.getElementById("existingCategoryName").value.trim(); // Get from readonly input

            if (!newResolutionNumber || !newViolationType || !newViolationDescription || !existingCategoryName) {
                displayModalMessage(addTypeToCategoryModalMessageDiv, "All fields are required.", 'error');
                return;
            }

            const formData = new FormData();
            formData.append('add_type_to_existing_category', '1');
            formData.append('new_resolution_number_type_modal', newResolutionNumber);
            formData.append('new_violation_type_type_modal', newViolationType);
            formData.append('new_violation_description_type_modal', newViolationDescription);
            formData.append('existing_category_name', existingCategoryName);


            const submitButton = addTypeToCategoryForm.querySelector('button[type="submit"]');
            const originalButtonContent = submitButton ? submitButton.innerHTML : '';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';
            }

            try {
                const response = await fetch(addTypeToCategoryForm.action, {
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
                    closeAddTypeToCategoryModal();
                    showToast(result.message, 'success', 3000, 'bottom-center');
                    window.location.href = window.location.pathname + '?tab=Configuration'; // Refresh tab
                } else {
                    displayModalMessage(addTypeToCategoryModalMessageDiv, result.message || "An error occurred.", 'error');
                }

            } catch (error) {
                console.error('Add type to category form submission error:', error);
                displayModalMessage(addTypeToCategoryModalMessageDiv, "Submission failed: " + error.message, 'error');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonContent;
                }
            }
        });
    }

    // --- Edit Violation Type Form Submission (EXISTING) ---
    if (editViolationTypeForm) {
        editViolationTypeForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            clearModalMessage(editViolationTypeModalMessageDiv);

            const violationTypeId = document.getElementById("editViolationTypeId").value;
            const newResolutionNumber = document.getElementById("editResolutionNumberConfig").value.trim();
            const newViolationCategory = document.getElementById("editViolationCategoryConfig").value.trim();
            const newViolationType = document.getElementById("editViolationTypeConfig").value.trim();
            const newViolationDescription = document.getElementById("editViolationDescriptionConfig").value.trim();

            if (!violationTypeId || !newResolutionNumber || !newViolationCategory || !newViolationType || !newViolationDescription) {
                displayModalMessage(editViolationTypeModalMessageDiv, "All fields are required.", 'error');
                return;
            }

            const formData = new FormData(editViolationTypeForm);
            formData.set('violation_type_id', violationTypeId);
            formData.set('edit_resolution_number_config', newResolutionNumber);
            formData.set('edit_violation_category_config', newViolationCategory);
            formData.set('edit_violation_type_config', newViolationType);
            formData.set('edit_violation_description_config', newViolationDescription);


            const submitButton = editViolationTypeForm.querySelector('button[type="submit"]');
            const originalButtonContent = submitButton ? submitButton.innerHTML : '';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            }

            try {
                const response = await fetch(editViolationTypeForm.action, {
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
                    closeEditViolationTypeModal();
                    showToast(result.message, 'success', 3000, 'bottom-center');
                    window.location.href = window.location.pathname + '?tab=Configuration'; // Refresh tab
                } else {
                    displayModalMessage(editViolationTypeModalMessageDiv, result.message || "An error occurred.", 'error');
                }

            } catch (error) {
                console.error('Edit violation type form submission error:', error);
                displayModalMessage(editViolationTypeModalMessageDiv, "Submission failed: " + error.message, 'error');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonContent;
                }
            }
        });
    }


    // --- Force uppercase for relevant inputs ---
    function addInputListenersForCapslock() {
        // Add New Violation Category Modal inputs
        const inputsToAddCategoryCapslock = [
            document.getElementById('newCategoryName'),
            document.getElementById('newResolutionNumberCatModal'),
            document.getElementById('newViolationTypeCatModal')
        ];
        inputsToAddCategoryCapslock.forEach(input => {
            if (input) {
                input.removeEventListener('input', handleInputUppercase); // Prevent duplicate listeners
                input.addEventListener('input', handleInputUppercase);
            }
        });

        // Add Violation Type to Existing Category Modal inputs
        const inputsToAddTypeCapslock = [
            document.getElementById('newResolutionNumberTypeModal'),
            document.getElementById('newViolationTypeTypeModal')
        ];
        inputsToAddTypeCapslock.forEach(input => {
            if (input) {
                input.removeEventListener('input', handleInputUppercase);
                input.addEventListener('input', handleInputUppercase);
            }
        });

        // Edit Violation Type Modal inputs
        const inputsToEditCapslock = [
            document.getElementById('editResolutionNumberConfig'),
            document.getElementById('editViolationCategoryConfig'),
            document.getElementById('editViolationTypeConfig')
        ];
        inputsToEditCapslock.forEach(input => {
            if (input) {
                input.removeEventListener('input', handleInputUppercase);
                input.addEventListener('input', handleInputUppercase);
            }
        });
    }

    function handleInputUppercase() {
        this.value = this.value.toUpperCase();
    }

    // Initial call to set up listeners for static inputs
    addInputListenersForCapslock();


    // --- Table Refresh and Filter Logic (Existing) ---
    if (refreshTableBtn) {
        refreshTableBtn.addEventListener('click', () => {
            showTableSpinner();
            window.location.href = window.location.pathname + '?tab=Violation'; // Ensure we refresh the Student tab
        });
    }

    if (filterForm) {
        const selects = filterForm.querySelectorAll('select');
        selects.forEach(select => {
            select.addEventListener('change', () => {
                showTableSpinner();
                filterForm.submit();
            });
        });
        filterForm.addEventListener('submit', () => {
            showTableSpinner();
        });
    }
});