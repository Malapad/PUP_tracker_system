function showToast(message, type = 'success', duration = 3000, position = 'top-center') {
    const toast = document.getElementById('toast-notification');
    if (!toast) return;
    toast.textContent = message;
    toast.className = 'toast';
    toast.classList.add(type, position);
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, duration);
}

document.addEventListener("DOMContentLoaded", () => {
    const studentViolationModal = document.getElementById("modal");
    const addStudentViolationBtn = document.getElementById("addViolationBtn");
    const closeStudentViolationModalBtn = document.querySelector("#modal .close-modal-button");
    const closeModalButtonInForm = document.getElementById("closeModalBtn");
    const studentViolationForm = document.getElementById("violationForm");
    const studentModalMessageDiv = document.getElementById("modalMessage");
    const searchStudentStepDiv = document.getElementById("searchStudentStep");
    const studentNumberSearchInput = document.getElementById("studentNumberSearchInput");
    const executeStudentSearchBtn = document.getElementById("executeStudentSearchBtn");
    const studentSearchResultArea = document.getElementById("studentSearchResultArea");
    const searchLoadingIndicator = document.getElementById("searchLoadingIndicator");
    const confirmedStudentInfoDiv = document.getElementById("confirmedStudentInfo");
    const studentNumberInputForForm = document.getElementById("studentNumber");
    const violationCategorySelect = document.getElementById("violationCategory");
    const violationTypeSelect = document.getElementById("violationType");
    const violationRemarksTextarea = document.getElementById("violationRemarks");
    const changeStudentBtn = document.getElementById("changeStudentBtn");
    const filterForm = document.getElementById('filter-form');
    const violationTableBody = document.getElementById('violationTableBody');
    
    function showTableSpinner() {
        const spinner = document.getElementById('tableSpinner');
        if (spinner) spinner.style.display = 'flex';
    }

    function displayModalMessage(message, type = 'error') {
        if (studentModalMessageDiv) {
            studentModalMessageDiv.textContent = message;
            studentModalMessageDiv.className = `modal-message ${type}-message`;
            studentModalMessageDiv.style.display = 'block';
        }
    }

    function clearModalMessage() {
        if (studentModalMessageDiv) {
            studentModalMessageDiv.style.display = 'none';
        }
    }
    
    function showSearchStep() {
        searchStudentStepDiv.style.display = "block";
        studentViolationForm.style.display = "none";
        clearModalMessage();
        studentNumberSearchInput.value = "";
        studentSearchResultArea.innerHTML = "";
        studentSearchResultArea.style.display = "none";
        searchLoadingIndicator.style.display = "none";
        studentNumberSearchInput.focus();
    }

    function showViolationFormStep(student) {
        searchStudentStepDiv.style.display = "none";
        studentViolationForm.style.display = "block";
        clearModalMessage();
        confirmedStudentInfoDiv.innerHTML = `
            <p><strong>Student:</strong> ${student.first_name} ${student.middle_name || ''} ${student.last_name}</p>
            <p><strong>Number:</strong> ${student.student_number}</p>
            <p><strong>Course:</strong> ${student.course_name || 'N/A'} | <strong>Section:</strong> ${student.section_name || 'N/A'}</p>
        `;
        studentNumberInputForForm.value = student.student_number;
        violationCategorySelect.value = "";
        violationTypeSelect.innerHTML = '<option value="">Select Category First</option>';
        violationTypeSelect.disabled = true;
        if (violationRemarksTextarea) violationRemarksTextarea.value = "";
        violationCategorySelect.focus();
    }

    if (addStudentViolationBtn) {
        addStudentViolationBtn.addEventListener("click", () => {
            if (studentViolationModal) {
                studentViolationModal.style.display = "flex";
                showSearchStep();
            }
        });
    }

    function closeEntireStudentViolationModal() {
        if (studentViolationModal) studentViolationModal.style.display = "none";
        studentViolationForm.reset();
        clearModalMessage();
    }

    if (closeStudentViolationModalBtn) closeStudentViolationModalBtn.addEventListener("click", closeEntireStudentViolationModal);
    if (closeModalButtonInForm) closeModalButtonInForm.addEventListener("click", closeEntireStudentViolationModal);
    if (changeStudentBtn) changeStudentBtn.addEventListener("click", showSearchStep);
    
    async function performStudentSearch() {
        const studentSearchNumber = studentNumberSearchInput.value.trim();
        if (!studentSearchNumber) {
            displayModalMessage("Please enter a Student Number.", 'error');
            return;
        }
        clearModalMessage();
        studentSearchResultArea.style.display = "none";
        searchLoadingIndicator.style.display = "block";
        executeStudentSearchBtn.disabled = true;
        try {
            const response = await fetch(`${window.location.pathname}?action=search_student_for_violation&student_search_number=${encodeURIComponent(studentSearchNumber)}`);
            const result = await response.json();
            if (result.success && result.student) {
                studentSearchResultArea.innerHTML = `
                    <h4>Student Found:</h4>
                    <p><strong>Number:</strong> ${result.student.student_number}</p>
                    <p><strong>Name:</strong> ${result.student.first_name} ${result.student.middle_name || ''} ${result.student.last_name}</p>
                    <button type="button" id="confirmStudentSelectionBtn" class="modal-button-confirm" data-student-json='${JSON.stringify(result.student)}'>
                        <i class="fas fa-user-check"></i> Use This Student
                    </button>`;
                studentSearchResultArea.style.display = "block";
                document.getElementById("confirmStudentSelectionBtn").addEventListener("click", function() {
                    showViolationFormStep(JSON.parse(this.dataset.studentJson));
                });
            } else {
                displayModalMessage(result.message || "Could not find student.", 'error');
            }
        } catch (error) {
            displayModalMessage("Search failed: " + error.message, 'error');
        } finally {
            searchLoadingIndicator.style.display = "none";
            executeStudentSearchBtn.disabled = false;
        }
    }

    if (executeStudentSearchBtn) executeStudentSearchBtn.addEventListener("click", performStudentSearch);
    if (studentNumberSearchInput) studentNumberSearchInput.addEventListener('keypress', e => e.key === 'Enter' && (e.preventDefault(), performStudentSearch()));

    if (violationCategorySelect) {
        violationCategorySelect.addEventListener('change', async function() {
            const categoryId = this.value;
            violationTypeSelect.innerHTML = '<option value="">Loading...</option>';
            violationTypeSelect.disabled = true;
            if (!categoryId) {
                violationTypeSelect.innerHTML = '<option value="">Select Category First</option>';
                return;
            }
            try {
                const response = await fetch(`${window.location.pathname}?action=get_violation_types_for_category&category_id=${categoryId}`);
                const result = await response.json();
                violationTypeSelect.innerHTML = '<option value="">Select Violation Type</option>';
                if (result.success && result.types.length > 0) {
                    result.types.forEach(type => violationTypeSelect.add(new Option(type.violation_type, type.violation_type_id)));
                    violationTypeSelect.disabled = false;
                } else {
                    violationTypeSelect.innerHTML = '<option value="">No types in this category</option>';
                }
            } catch (error) {
                displayModalMessage('Network error: Could not load violation types.', 'error');
            }
        });
    }

    if (studentViolationForm) {
        studentViolationForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const submitButton = studentViolationForm.querySelector('button[type="submit"]');
            const originalButtonContent = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            try {
                const response = await fetch(studentViolationForm.action, { method: 'POST', body: new FormData(studentViolationForm) });
                const result = await response.json();
                if (result.success) {
                    closeEntireStudentViolationModal();
                    showToast(result.message, 'success');
                    showTableSpinner();
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    displayModalMessage(result.message || "An error occurred.", 'error');
                }
            } catch (error) {
                displayModalMessage("Submission failed: " + error.message, 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonContent;
            }
        });
    }

    window.addEventListener("click", e => e.target === studentViolationModal && closeEntireStudentViolationModal());

    if (filterForm) {
        filterForm.querySelectorAll('select').forEach(el => {
            el.addEventListener('change', () => {
                showTableSpinner();
                filterForm.submit();
            });
        });
        filterForm.addEventListener('submit', showTableSpinner);
    }
    
    if (violationTableBody) {
        violationTableBody.addEventListener('click', function (e) {
            const summaryRow = e.target.closest('.student-summary-row');
            if (summaryRow) {
                summaryRow.classList.toggle('expanded');
                const detailRow = document.getElementById(summaryRow.dataset.target);
                if (detailRow) detailRow.classList.toggle('active');
            }
        });
    }
});