/**
 * VARUNA System - ID Card Admin Page Script (Final, Reliable Preview)
 * Current Time: Wednesday, June 18, 2025 at 4:18 PM IST
 * Location: Kalyan, Maharashtra, India
 */
document.addEventListener('DOMContentLoaded', function() {
    const adminForm = document.getElementById('style_form');
    if (!adminForm) return;

    // --- Get All Elements ---
    const contractTypeSelect = document.getElementById('style_contract_type');
    const styleFieldsContainer = document.getElementById('style_form_fields');
    const previewIframe = document.getElementById('id_preview_iframe');
    const colorInputs = adminForm.querySelectorAll('input[type="color"]');

    // --- Main Functions ---

    // This function builds the URL with all color parameters and reloads the iframe
    function updatePreview() {
        const params = new URLSearchParams();
        colorInputs.forEach(input => {
            // Send the hex code, removing the leading #
            params.append(input.name, input.value.substring(1));
        });
        previewIframe.src = `${BASE_URL}id_card_preview.php?${params.toString()}`;
    }

    // --- Event Listeners ---

    // When the contract type dropdown changes
    contractTypeSelect.addEventListener('change', function() {
        const selectedType = this.value;
        if (!selectedType) {
            styleFieldsContainer.classList.add('hidden');
            previewIframe.src = 'about:blank';
            return;
        }

        // Fetch the saved styles for the selected type
        fetch(`${BASE_URL}api/get_id_styles.php?contract_type=${encodeURIComponent(selectedType)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.styles) {
                    // Populate the color pickers with the fetched values
                    colorInputs.forEach(input => {
                        input.value = data.styles[input.name];
                    });
                    styleFieldsContainer.classList.remove('hidden');
                    // Update the preview with the loaded styles
                    updatePreview();
                }
            });
    });

    // When a color picker value is CHANGED (after user releases the mouse)
    // This prevents too many requests while dragging the color picker.
    colorInputs.forEach(input => {
        input.addEventListener('change', updatePreview);
    });

    // When the "Save Style" button is clicked
    adminForm.addEventListener('submit', function(event) {
        event.preventDefault();
        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to save these style changes?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, save it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData(adminForm);
                fetch(adminForm.action, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                         if(data.success && data.new_csrf_token){
                           document.querySelector('meta[name="csrf-token"]').setAttribute('content', data.new_csrf_token);
                           adminForm.querySelector('input[name="csrf_token"]').value = data.new_csrf_token;
                        }
                        Swal.fire({
                            toast: true, position: 'top-end', timer: 3000, timerProgressBar: true,
                            icon: data.success ? 'success' : 'error',
                            title: data.message
                        });
                    });
            }
        });
    });
});