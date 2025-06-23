/**
 * VARUNA System - Add Licensee Page Script
 * Current Time: Monday, June 16, 2025 at 11:39 AM IST
 * Location: Kalyan, Maharashtra, India
 */

document.addEventListener('DOMContentLoaded', function() {
    const licenseeForm = document.getElementById('addLicenseeForm');

    // Only run if the licensee form is present on the page
    if (licenseeForm) {
        const mobileInput = document.getElementById('mobile_number');
        const mobileError = document.getElementById('mobile_error');
        const mobilePattern = /^[1-9][0-9]{9}$/;

        licenseeForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Stop form from submitting immediately
            let isValid = true;

            // Validate the mobile number
            if (!mobilePattern.test(mobileInput.value)) {
                mobileError.textContent = 'Must be 10 digits and cannot start with 0.';
                mobileError.style.display = 'block';
                mobileInput.classList.add('is-invalid');
                isValid = false;
            } else {
                mobileError.style.display = 'none';
                mobileInput.classList.remove('is-invalid');
            }

            // If validation passes, submit the form
            if (isValid) {
                licenseeForm.submit();
            }
        });

        // Add real-time feedback to clear the error as the user types
        mobileInput.addEventListener('input', function() {
            if (mobileInput.classList.contains('is-invalid')) {
                mobileError.style.display = 'none';
                mobileInput.classList.remove('is-invalid');
            }
        });
    }
});