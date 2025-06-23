document.addEventListener('DOMContentLoaded', function() {
    // --- 1. SETUP & INITIALIZATION ---
    let staffStatusChart;
    const licenseeTable = $('#licensee_breakdown_table');
    const licenseeCountEl = document.getElementById('licensee_count');
    const contractCountEl = document.getElementById('contract_count');
    const staffCountEl = document.getElementById('staff_count');
    const chartCanvas = document.getElementById('staffStatusChart');

    // --- 2. DATA FETCHING AND RENDERING ---
    fetch(`${BASE_URL}api/get_dashboard_data.php`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) { throw new Error(data.error); }
            
            licenseeCountEl.textContent = data.stats.licensees;
            contractCountEl.textContent = data.stats.contracts;
            staffCountEl.textContent = data.stats.staff;
            renderStaffStatusChart(data.staff_status_chart);

            licenseeTable.DataTable({
                data: data.licensee_breakdown,
                columns: [
                    { "data": "licensee_name" },
                    { "data": "contract_count" },
                    { "data": "staff_count" }
                ],
                "pageLength": 5,
                "lengthChange": false
            });
        })
        .catch(error => {
            console.error("Dashboard Error:", error);
            Swal.fire('Error', 'Could not load dashboard data.', 'error');
        });

    // --- 3. CHART RENDERING FUNCTION ---
    function renderStaffStatusChart(chartData) {
        if (staffStatusChart) { staffStatusChart.destroy(); }
        staffStatusChart = new Chart(chartCanvas, {
            type: 'pie',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Staff Status',
                    data: chartData.data,
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#6c757d'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                },
                // --- FIX: onClick event has been removed from the chart options ---
            }
        });
    }

    // --- 4. MODAL & CLICK EVENT HANDLING ---

    // --- FIX: Add click listeners to the main stat cards ---
    document.getElementById('licensees_card').addEventListener('click', () => {
        showDetailsModal('All Licensees', 'licensee-modal-table', `${BASE_URL}api/get_licensees_list.php`, [
            { "data": "id", "title": "ID" },
            { "data": "name", "title": "Name" },
            { "data": "mobile_number", "title": "Mobile" },
            { "data": "status", "title": "Status" }
        ]);
    });

    document.getElementById('contracts_card').addEventListener('click', () => {
        showDetailsModal('All Contracts', 'contract-modal-table', `${BASE_URL}api/get_contracts_list.php`, [
            { "data": "id", "title": "ID" },
            { "data": "contract_name", "title": "Contract Name" },
            { "data": "contract_type", "title": "Type" },
            { "data": "station_code", "title": "Location" },
            { "data": "licensee_name", "title": "Licensee" },
            { "data": "status", "title": "Status" }
        ]);
    });

    document.getElementById('staff_card').addEventListener('click', () => {
        // We use the existing get_approved_staff API as it's suitable for a quick overview
        showDetailsModal('All Approved Staff', 'staff-modal-table', `${BASE_URL}api/get_approved_staff.php`, [
            { "data": "id", "title": "ID" },
            { "data": "name", "title": "Name" },
            { "data": "designation", "title": "Designation" },
            { "data": "contract_name", "title": "Contract" },
            { "data": "station_code", "title": "Station" }
        ]);
    });
    
    // Generic function to create and show a modal with a DataTable
    function showDetailsModal(title, tableId, apiUrl, columns) {
        Swal.fire({
            title: title,
            html: `<table id="${tableId}" class="display" style="width:100%"></table>`,
            width: '90%',
            showCloseButton: true,
            showConfirmButton: false,
            didOpen: () => {
                // Initialize DataTable inside the modal
                $(`#${tableId}`).DataTable({
                    "processing": true,
                    "ajax": { "url": apiUrl, "dataSrc": "data" },
                    "columns": columns,
                    "pageLength": 5,
                    "lengthMenu": [5, 10, 25]
                });
            }
        });
    }

    

    licenseeTable.on('click', 'tbody tr', function() {
        const rowData = licenseeTable.DataTable().row(this).data();
        if (!rowData) return;

        // Show a loading state while fetching data
        Swal.fire({
            title: `Loading Details for ${rowData.licensee_name}...`,
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch(`${BASE_URL}api/get_licensee_details.php?licensee_id=${rowData.licensee_id}`)
            .then(res => res.json())
            .then(response => {
                if (!response.success) {
                    throw new Error(response.message || 'Could not fetch details.');
                }

                // Build the detailed HTML content for the modal
                let modalContent = '<div class="licensee-details-modal">';
                if (response.contracts && response.contracts.length > 0) {
                    response.contracts.forEach(contract => {
                        modalContent += `
                            <div class="contract-group">
                                <h4>${contract.contract_name} (Status: ${contract.status})</h4>
                                <ul class="staff-list">`;
                        if (contract.staff && contract.staff.length > 0) {
                            contract.staff.forEach(staff => {
                                modalContent += `<li>${staff.name} (ID: ${staff.id}) - <span class="status-${staff.status}">${staff.status}</span></li>`;
                            });
                        } else {
                            modalContent += '<li>No staff found for this contract.</li>';
                        }
                        modalContent += '</ul></div>';
                    });
                } else {
                    modalContent += '<p>No contracts found for this licensee.</p>';
                }
                modalContent += '</div>';

                // Display the final modal
                Swal.fire({
                    title: `Details for ${response.licensee_name}`,
                    html: modalContent,
                    width: '800px',
                    showCloseButton: true,
                    showConfirmButton: false
                });
            })
            .catch(error => {
                Swal.fire('Error!', error.message, 'error');
            });
    });
});