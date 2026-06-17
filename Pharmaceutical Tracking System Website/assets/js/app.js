/**
 * app.js
 * Core Interactive Logic
 * Handles sidebar controls, live table search filters, automatic age calculation,
 * and dynamic medical record autofilling.
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. COLLAPSIBLE SIDEBAR LOGIC
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const toggleBtn = document.getElementById('sidebarToggle');
    
    if (toggleBtn && sidebar && mainContent) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Save state to localStorage for persistence across pages
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });

        // Restore sidebar state from previous page loads
        const sidebarState = localStorage.getItem('sidebarCollapsed');
        if (sidebarState === 'true') {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        }
    }

    // 2. LIVE CLIENT-SIDE TABLE FILTER/SEARCH
    const searchInput = document.getElementById('tableSearch');
    const searchTable = document.getElementById('searchableTable');
    
    if (searchInput && searchTable) {
        searchInput.addEventListener('keyup', function() {
            const filter = searchInput.value.toLowerCase().trim();
            const rows = searchTable.getElementsByTagName('tr');
            
            // Loop through all table rows, except the header (index 0)
            for (let i = 1; i < rows.length; i++) {
                let rowText = rows[i].textContent.toLowerCase();
                if (rowText.includes(filter)) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        });
    }

    // 3. DATE OF BIRTH TO AGE AUTO-CALCULATION
    const dobInput = document.getElementById('date_birth');
    const ageInput = document.getElementById('age');
    
    if (dobInput && ageInput) {
        dobInput.addEventListener('change', function() {
            const dob = new Date(dobInput.value);
            if (!isNaN(dob.getTime())) {
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();
                
                // Adjust if birthday hasn't occurred yet this year
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                
                ageInput.value = age >= 0 ? age : 0;
            }
        });
    }

    // 4. DIAGNOSIS/VISIT DYNAMIC PATIENT NAME AUTOFILL
    const patientSelect = document.getElementById('patient_id_select');
    const patientNameInput = document.getElementById('patient_name_autofill');
    
    if (patientSelect && patientNameInput) {
        patientSelect.addEventListener('change', function() {
            const selectedOption = patientSelect.options[patientSelect.selectedIndex];
            const patientName = selectedOption.getAttribute('data-name');
            patientNameInput.value = patientName ? patientName : '';
        });
    }

    // 5. DIAGNOSIS/VISIT DYNAMIC DOCTOR DEPT AUTOFILL
    const doctorSelect = document.getElementById('doctor_id_select');
    const doctorNameInput = document.getElementById('doctor_name_autofill');
    
    if (doctorSelect && doctorNameInput) {
        doctorSelect.addEventListener('change', function() {
            const selectedOption = doctorSelect.options[doctorSelect.selectedIndex];
            const doctorName = selectedOption.getAttribute('data-name');
            doctorNameInput.value = doctorName ? doctorName : '';
        });
    }

    // 6. CLEAR/RESET FORM HELPER
    const clearBtns = document.querySelectorAll('.btn-clear-form');
    clearBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = btn.closest('form');
            if (form) {
                form.reset();
                // Clear any read-only/autofilled text fields as well
                const readonlyInputs = form.querySelectorAll('input[readonly]');
                readonlyInputs.forEach(input => {
                    input.value = '';
                });
                
                // Remove primary key values or action modes stored in hidden inputs
                const hiddenInputs = form.querySelectorAll('input[type="hidden"]');
                hiddenInputs.forEach(input => {
                    if (input.name !== 'action' && input.name !== 'csrf_token') {
                        input.value = '';
                    }
                });
                
                // Reset form button states if toggleable
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i>Save';
                }
            }
        });
    });

    // 7. SECURE OPERATION CONFIRMATION
    const deleteActionBtns = document.querySelectorAll('.btn-delete-confirm');
    deleteActionBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to permanently delete this clinical record? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

});

// 8. PRINTING ENGINE UTILITY
function printMedicalRecord(divId) {
    if (divId) {
        const printContent = document.getElementById(divId).innerHTML;
        const originalContent = document.body.innerHTML;
        
        // Temporarily isolate print container, trigger print, and restore
        document.body.innerHTML = printContent;
        window.print();
        document.body.innerHTML = originalContent;
        window.location.reload(); // Reload to restore standard JS event bindings
    } else {
        window.print();
    }
}
