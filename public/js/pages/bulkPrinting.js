document.addEventListener('DOMContentLoaded', function() {
    const lockedOverlay = document.querySelector('.feature-lock-overlay');
    if (lockedOverlay) {
        lockedOverlay.addEventListener('click', () => {
            Swal.fire({
                icon: 'warning',
                title: 'Feature Locked',
                text: 'Please upload your signature from the Profile page to enable this feature.',
                confirmButtonText: 'Go to Profile',
                showCancelButton: true,
                cancelButtonText: 'Close'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `${BASE_URL}profile`;
                }
            });
        });
    }

    const pageContainer = document.querySelector('.tab-container');
    if (!pageContainer) return;

    const tabs = pageContainer.querySelectorAll('.tab-link');
    const contents = document.querySelectorAll('.tab-content');
    const bulkPrintForm = document.getElementById('bulkPrintForm');

    // Tab switching logic
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(item => item.classList.remove('active'));
            contents.forEach(item => item.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById(tab.dataset.tab).classList.add('active');
        });
    });

    // Form submission logic
    bulkPrintForm.addEventListener('submit', function(event) {
        const activeTab = document.querySelector('.tab-content.active');
        const select = activeTab.querySelector('select');
        const filterBy = activeTab.id.replace('tab_', '');
        const filterValue = select.value;

        if (!filterValue) {
            event.preventDefault();
            Swal.fire('Selection Required', 'Please select an item from the dropdown.', 'warning');
            return;
        }

        document.getElementById('filter_by').value = filterBy;
        document.getElementById('filter_value').value = filterValue;
    });
});