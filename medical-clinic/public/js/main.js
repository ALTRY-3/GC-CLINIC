// File: /medical-clinic/medical-clinic/public/js/main.js
document.addEventListener('DOMContentLoaded', function() {
    // Initialize any necessary components or event listeners here

    // Example: Toggle sidebar
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');

    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('expanded');
        toggleBtn.classList.toggle('expanded');
    });

    // Example: Form submission handling
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            // Perform validation or other actions before submission
            if (!validateForm(form)) {
                event.preventDefault();
                alert('Please fill out all required fields correctly.');
            }
        });
    });

    // Example: Function to validate forms
    function validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input[required], select[required]');
        inputs.forEach(input => {
            if (!input.value) {
                isValid = false;
                input.classList.add('is-invalid');
            } else {
                input.classList.remove('is-invalid');
            }
        });
        return isValid;
    }
});