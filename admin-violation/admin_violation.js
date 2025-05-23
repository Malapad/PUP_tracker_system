function showToast(message, type = 'success', duration = 3000) {
    const toast = document.getElementById('toast-notification');
    if (!toast) {
        console.error('Toast element not found!');
        return;
    }

    toast.textContent = message;
    toast.className = 'toast';
    toast.classList.add(type); 

    toast.style.display = 'block'; 
    toast.offsetHeight; 

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

    function showTableSpinner() {
        if (tableSpinner) {
            tableSpinner.style.display = 'flex';
        }
    }

    function hideTableSpinner() { 
        if (tableSpinner) {
            tableSpinner.style.display = 'none';
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
                if(studentNumberInput) studentNumberInput.focus();
            }
        });
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener("click", () => {
            if (modal) modal.style.display = "none";
            if (violationForm) violationForm.reset();
            clearModalMessage();
        });
    }

    window.addEventListener("click", (event) => {
        if (event.target === modal) {
            if (modal) modal.style.display = "none";
            if (violationForm) violationForm.reset();
            clearModalMessage();
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
            formData.append('ajax_submit', '1');

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
                        if(errorData && errorData.message) errorMsg = errorData.message;
                    } catch (jsonError) { /* Ignore */ }
                    throw new Error(errorMsg);
                }

                const result = await response.json();

                if (result.success) {
                    if (modal) modal.style.display = "none"; 
                    violationForm.reset(); 
                    showToast(result.message, 'success', 2500); 
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
        filterForm.addEventListener('submit', () => {
            showTableSpinner(); 
        });
    }
});