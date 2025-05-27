// Updated showToast function
function showToast(message, type = 'success', duration = 3000) {
    const toast = document.getElementById('toast-notification');
    if (!toast) {
        console.error('Toast element not found!');
        return;
    }

    toast.textContent = message;
    toast.className = 'toast';
    toast.classList.add(type);

    toast.classList.remove('show');
    void toast.offsetWidth;
    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.remove('show');
    }, duration);
}

document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("modal");
    const addViolationBtn = document.getElementById("addViolationBtn");
    const closeModalBtn = document.getElementById("closeModal");
    const violationForm = document.getElementById("violationForm");
    const modalMessageDiv = document.getElementById("modalMessage");
    const refreshTableBtn = document.getElementById('refreshTableBtn');
    const tableSpinner = document.getElementById('tableSpinner');
    const filterForm = document.getElementById('filter-form');
    const closeModalInHeader = document.querySelector(".modal .head-modal .close-modal-button");

    // New elements for Violation Configuration Tab
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');
    const configViolationModal = document.getElementById('configViolationModal');
    const addConfigViolationBtn = document.getElementById('addConfigViolationBtn');
    const closeConfigModalBtn = document.getElementById('closeConfigModal');
    const configCloseModalButtonSpan = document.querySelector('#configViolationModal .head-modal .close-modal-button'); // Specific for config modal
    const configViolationForm = document.getElementById('configViolationForm');
    const configModalMessage = document.getElementById('configModalMessage');
    const configViolationTypeInput = document.getElementById('configViolationType');
    const configModalTitle = document.getElementById('configModalTitle');
    const configSubmitBtnText = document.getElementById('configSubmitBtnText');
    const configViolationTypeIdInput = document.getElementById('configViolationTypeId');
    const configResolutionInput = document.getElementById('configResolution');
    const configViolationCategorySelect = document.getElementById('configViolationCategory');


    function showTableSpinner() {
        if (tableSpinner) {
            tableSpinner.style.display = 'flex';
        }
    }

    function displayModalErrorMessage(message) {
        if (modalMessageDiv) {
            modalMessageDiv.textContent = message;
            modalMessageDiv.className = 'modal-message error-message';
            modalMessageDiv.style.display = 'block';
        } else {
            alert(message);
        }
    }

    function clearModalMessage() {
        if (modalMessageDiv) {
            modalMessageDiv.textContent = '';
            modalMessageDiv.style.display = 'none';
        }
    }

    if (addViolationBtn) {
        addViolationBtn.addEventListener("click", () => {
            if (modal) {
                modal.style.display = "block";
                if (violationForm) violationForm.reset();
                clearModalMessage();
                const studentNumberInput = document.getElementById("studentNumber");
                if (studentNumberInput) studentNumberInput.focus();
            }
        });
    }

    function closeModal() {
        if (modal) modal.style.display = "none";
        if (violationForm) violationForm.reset();
        clearModalMessage();
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener("click", closeModal);
    }

    if (closeModalInHeader) {
        closeModalInHeader.addEventListener("click", closeModal);
    }

    window.addEventListener("click", (event) => {
        if (event.target === modal) {
            closeModal();
        }
        // Also close the config modal if clicked outside
        if (event.target === configViolationModal) {
            configViolationModal.style.display = 'none';
        }
    });

    if (violationForm) {
        violationForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            clearModalMessage();

            const studentNumberInput = document.getElementById("studentNumber");
            const violationTypeInput = document.getElementById("violationType");

            if (studentNumberInput && !studentNumberInput.value.trim()) {
                displayModalErrorMessage("Please enter Student Number.");
                return;
            }
            if (violationTypeInput && !violationTypeInput.value) {
                displayModalErrorMessage("Please select a Violation Type.");
                return;
            }

            const formData = new FormData(violationForm);

            const submitButton = violationForm.querySelector('button[type="submit"]');
            const originalButtonContent = submitButton ? submitButton.innerHTML : '';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            }

            try {
                const response = await fetch(violationForm.action, {
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
                    closeModal();
                    showToast(result.message, 'success', 3000);
                    // Reload the page after successful submission to update the table
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    displayModalErrorMessage(result.message || "An error occurred.");
                }

            } catch (error) {
                console.error('Form submission error:', error);
                displayModalErrorMessage("Submission failed: " + error.message);
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonContent;
                }
            }
        });
    }

    if (refreshTableBtn) {
        refreshTableBtn.addEventListener('click', () => {
            showTableSpinner();
            window.location.reload(true);
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

    // --- JavaScript for Violation Configuration Tab ---

    // Tab switching logic
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(tc => tc.classList.remove('active'));

            this.classList.add('active');
            document.getElementById(this.dataset.tab).classList.add('active');
        });
    });

    // Handle "Add New Violation Type" button click (for config tab)
    if (addConfigViolationBtn) {
        addConfigViolationBtn.addEventListener('click', function() {
            if (configViolationModal) {
                configViolationModal.style.display = 'block';
                configModalTitle.textContent = 'Add New Violation Type';
                configSubmitBtnText.textContent = 'Add';
                configViolationForm.reset(); // Clear previous data
                configViolationTypeIdInput.value = ''; // Ensure ID is empty for new entry
                configModalMessage.style.display = 'none'; // Hide any previous messages
                configModalMessage.className = 'modal-message'; // Reset message class
            }
        });
    }

    // Close config modal using button or span
    if (closeConfigModalBtn) {
        closeConfigModalBtn.addEventListener('click', function() {
            if (configViolationModal) configViolationModal.style.display = 'none';
        });
    }
    if (configCloseModalButtonSpan) {
        configCloseModalButtonSpan.addEventListener('click', function() {
            if (configViolationModal) configViolationModal.style.display = 'none';
        });
    }

    // Ensure violation type input is uppercase
    if (configViolationTypeInput) {
        configViolationTypeInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }

    // Handle form submission for adding/editing violation types in config
    if (configViolationForm) {
        configViolationForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(configViolationForm);
            // Use the global currentPageUrl variable
            const url = currentPageUrl;

            const submitButton = configViolationForm.querySelector('button[type="submit"]');
            const originalButtonContent = submitButton ? submitButton.innerHTML : '';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }

            try {
                const response = await fetch(url, {
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

                const data = await response.json();

                if (data.success) {
                    configModalMessage.textContent = data.message;
                    configModalMessage.classList.remove('error-message');
                    configModalMessage.classList.add('success-message');
                    configModalMessage.style.display = 'block';
                    showToast(data.message, 'success');
                    // Reload the page to show updated configuration data
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    configModalMessage.textContent = data.message;
                    configModalMessage.classList.remove('success-message');
                    configModalMessage.classList.add('error-message');
                    configModalMessage.style.display = 'block';
                    showToast(data.message, 'error');
                }
            } catch (error) {
                console.error('Config form submission error:', error);
                configModalMessage.textContent = 'An error occurred during submission.';
                configModalMessage.classList.remove('success-message');
                configModalMessage.classList.add('error-message');
                configModalMessage.style.display = 'block';
                showToast('An error occurred during submission.', 'error');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonContent;
                }
            }
        });
    }

    // Handle Edit button clicks for configuration violations
    document.querySelectorAll('.edit-config-violation-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const resolution = this.dataset.resolution;
            const type = this.dataset.type;
            const category = this.dataset.category;

            configModalTitle.textContent = 'Edit Violation Type';
            configSubmitBtnText.textContent = 'Update';
            configViolationTypeIdInput.value = id;
            configResolutionInput.value = resolution;
            configViolationTypeInput.value = type;
            configViolationCategorySelect.value = category; // Set the dropdown value
            configModalMessage.style.display = 'none'; // Hide any previous messages
            configModalMessage.className = 'modal-message'; // Reset message class
            configViolationModal.style.display = 'block';
        });
    });

    // Handle Delete button clicks for configuration violations (AJAX)
    document.querySelectorAll('.delete-config-violation-btn').forEach(button => {
        button.addEventListener('click', function() {
            const violationTypeId = this.dataset.id;
            if (confirm('Are you sure you want to delete this violation type? This action cannot be undone.')) {
                fetch('delete_violation_type.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'violation_type_id=' + encodeURIComponent(violationTypeId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => {
                            location.reload(); // Reload to reflect changes
                        }, 1500);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred during deletion.', 'error');
                });
            }
        });
    });

    // Dropdown functionality for violation categories in config tab
    document.querySelectorAll('.category-dropdown-btn').forEach(button => {
        button.addEventListener('click', function() {
            const dropdownContent = this.nextElementSibling;
            const icon = this.querySelector('i');
            dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
            icon.classList.toggle('fa-chevron-down');
            icon.classList.toggle('fa-chevron-up');
        });
    });

});