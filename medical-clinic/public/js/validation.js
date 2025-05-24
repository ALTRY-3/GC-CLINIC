// This file contains JavaScript functions for form validation.

document.addEventListener('DOMContentLoaded', function () {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        form.addEventListener('submit', function (event) {
            if (!validateForm(form)) {
                event.preventDefault();
            }
        });
    });

    function validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input, select, textarea');

        inputs.forEach(input => {
            if (!input.checkValidity()) {
                isValid = false;
                showError(input);
            } else {
                clearError(input);
            }
        });

        return isValid;
    }

    function showError(input) {
        const error = document.createElement('div');
        error.className = 'error-message';
        error.textContent = input.validationMessage;
        input.classList.add('is-invalid');
        input.parentNode.insertBefore(error, input.nextSibling);
    }

    function clearError(input) {
        input.classList.remove('is-invalid');
        const error = input.parentNode.querySelector('.error-message');
        if (error) {
            error.remove();
        }
    }
});