document.addEventListener('DOMContentLoaded', function() {
    // --- 1. SETUP & INITIALIZATION ---
    let staffStatusChart;
    const licenseeTable = $('#licensee_breakdown_table');
    const sectionTable = $('#section_breakdown_table');
    const stationTable = $('#station_breakdown_table');
    const contractTypeTable = $('#contract_type_breakdown_table');
    const expiringDocsTable = $('#expiring_docs_table');
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

            // Initialize Expiring Documents Table
            expiringDocsTable.DataTable({
                data: data.expiring_documents,
                columns: [
                    { 
                        "data": "staff_name",
                        "render": function(data, type, row) {
                            if (type === 'display') {
                                return `<a href="#" class="table-link" data-type="staff" data-id="${row.staff_id}">${data}</a>`;
                            }
                            return data;
                        }
                    },
                    { "data": "designation" },
                    { "data": "licensee_name" },
                    { "data": "licensee_mobile" },
                    { "data": "contract_name" },
                    { "data": "contract_type" },
                    { "data": "station_code" },
                    { 
                        "data": "expiring_document",
                        "render": function(data) {
                            return `<span class="badge bg-warning text-dark">${data}</span>`;
                        }
                    },
                    { 
                        "data": "expiry_date",
                        "render": function(data) {
                            const date = new Date(data);
                            const today = new Date();
                            const daysUntilExpiry = Math.ceil((date - today) / (1000 * 60 * 60 * 24));
                            const formattedDate = date.toLocaleDateString();
                            return `<span class="badge bg-${daysUntilExpiry <= 7 ? 'danger' : 'warning'}">${formattedDate} (${daysUntilExpiry} days)</span>`;
                        }
                    }
                ],
                "pageLength": 10,
                "order": [[8, "asc"]],
                "columnDefs": [
                    { "className": "dt-center", "targets": "_all" }
                ]
            });

            // Initialize Licensee Table
            licenseeTable.DataTable({
                data: data.licensee_breakdown,
                columns: [
                    { 
                        "data": "licensee_name",
                        "render": function(data, type, row) {
                            if (type === 'display') {
                                return `<a href="#" class="table-link" data-type="licensee" data-id="${row.licensee_id}">${data}</a>`;
                            }
                            return data;
                        }
                    },
                    { "data": "mobile_number" },
                    { 
                        "data": "status",
                        "render": function(data) {
                            return `<span class="status-${data.toLowerCase()}">${data}</span>`;
                        }
                    },
                    { "data": "contract_count" },
                    { "data": "staff_count" },
                    { 
                        "data": "pending_staff",
                        "render": function(data) {
                            return `<span class="status-pending">${data || 0}</span>`;
                        }
                    },
                    { 
                        "data": "approved_staff",
                        "render": function(data) {
                            return `<span class="status-approved">${data || 0}</span>`;
                        }
                    },
                    { 
                        "data": "terminated_staff",
                        "render": function(data) {
                            return `<span class="status-terminated">${data || 0}</span>`;
                        }
                    }
                ],
                "pageLength": 5,
                "lengthChange": false,
                "order": [[0, "asc"]],
                "columnDefs": [
                    { "className": "dt-center", "targets": "_all" }
                ]
            });

            // Initialize Section Table
            sectionTable.DataTable({
                data: data.section_breakdown,
                columns: [
                    { 
                        "data": "section_code",
                        "render": function(data, type, row) {
                            if (type === 'display') {
                                return `<a href="#" class="table-link" data-type="section" data-id="${data}">${data}</a>`;
                            }
                            return data;
                        }
                    },
                    { "data": "licensee_count" },
                    { "data": "contract_count" },
                    { "data": "staff_count" },
                    { 
                        "data": "pending_staff",
                        "render": function(data) {
                            return `<span class="status-pending">${data || 0}</span>`;
                        }
                    },
                    { 
                        "data": "approved_staff",
                        "render": function(data) {
                            return `<span class="status-approved">${data || 0}</span>`;
                        }
                    },
                    { 
                        "data": "terminated_staff",
                        "render": function(data) {
                            return `<span class="status-terminated">${data || 0}</span>`;
                        }
                    }
                ],
                "pageLength": 5,
                "lengthChange": false,
                "order": [[3, "desc"]],
                "columnDefs": [
                    { "className": "dt-center", "targets": "_all" }
                ]
            });

            // Initialize Station Table
            stationTable.DataTable({
                data: data.station_breakdown,
                columns: [
                    { 
                        "data": "station_code",
                        "render": function(data, type, row) {
                            if (type === 'display') {
                                return `<a href="#" class="table-link" data-type="station" data-id="${data}">${data}</a>`;
                            }
                            return data;
                        }
                    },
                    { "data": "licensee_count" },
                    { "data": "contract_count" },
                    { "data": "staff_count" },
                    { 
                        "data": "pending_staff",
                        "render": function(data) {
                            return `<span class="status-pending">${data || 0}</span>`;
                        }
                    },
                    { 
                        "data": "approved_staff",
                        "render": function(data) {
                            return `<span class="status-approved">${data || 0}</span>`;
                        }
                    },
                    { 
                        "data": "terminated_staff",
                        "render": function(data) {
                            return `<span class="status-terminated">${data || 0}</span>`;
                        }
                    }
                ],
                "pageLength": 5,
                "lengthChange": false,
                "order": [[3, "desc"]],
                "columnDefs": [
                    { "className": "dt-center", "targets": "_all" }
                ]
            });

            // Initialize Contract Type Table
            contractTypeTable.DataTable({
                data: data.contract_type_breakdown,
                columns: [
                    { 
                        "data": "contract_type",
                        "render": function(data, type, row) {
                            if (type === 'display') {
                                return `<a href="#" class="table-link" data-type="contract_type" data-id="${data}">${data}</a>`;
                            }
                            return data;
                        }
                    },
                    { "data": "licensee_count" },
                    { "data": "contract_count" },
                    { "data": "staff_count" },
                    { 
                        "data": "pending_staff",
                        "render": function(data) {
                            return `<span class="status-pending">${data || 0}</span>`;
                        }
                    },
                    { 
                        "data": "approved_staff",
                        "render": function(data) {
                            return `<span class="status-approved">${data || 0}</span>`;
                        }
                    },
                    { 
                        "data": "terminated_staff",
                        "render": function(data) {
                            return `<span class="status-terminated">${data || 0}</span>`;
                        }
                    }
                ],
                "pageLength": 5,
                "lengthChange": false,
                "order": [[3, "desc"]],
                "columnDefs": [
                    { "className": "dt-center", "targets": "_all" }
                ]
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
                    backgroundColor: ['#ffc107', '#28a745',  '#dc3545', '#6c757d'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                }
            }
        });
    }

    // --- 4. CLICK EVENT HANDLERS ---

    // Keep track of modal history
    const modalStack = [];

    // Generic function to show detailed data modal
    function showDetailsModal(title, url, columns, extraData = null) {
        const modalConfig = {
            title: title,
            html: '<div class="modal-table-container"><table id="details_table" class="display" style="width:100%"></table></div>',
            width: '90%',
            showCloseButton: true,
            showConfirmButton: false,
            didOpen: () => {
                // Initialize DataTable with error handling
                try {
                    const table = $('#details_table').DataTable({
                        ajax: {
                            url: url,
                            data: extraData
                        },
                        columns: columns,
                        pageLength: 10,
                        scrollX: true,
                        columnDefs: [
                            { "className": "dt-center", "targets": "_all" }
                        ],
                        language: {
                            emptyTable: "No data available",
                            zeroRecords: "No matching records found"
                        }
                    });

                    // Add error handler for failed Ajax requests
                    table.on('error.dt', function(e, settings, techNote, message) {
                        console.error('DataTables error:', message);
                        Swal.fire('Error', 'Failed to load data. Please try again.', 'error');
                    });
                } catch (error) {
                    console.error('Error initializing DataTable:', error);
                    Swal.fire('Error', 'Failed to initialize table. Please try again.', 'error');
                }
            },
            willClose: () => {
                // Remove this modal from the stack when it's closed
                modalStack.pop();
                
                // If there are previous modals in the stack, show the last one
                if (modalStack.length > 0) {
                    const prevModal = modalStack[modalStack.length - 1];
                    Swal.fire(prevModal);
                }
            }
        };

        // Add current modal to the stack
        modalStack.push(modalConfig);

        // Show the modal
        Swal.fire(modalConfig);
    }

    // Handle clicks on table links
    $(document).on('click', '.table-link', function(e) {
        e.preventDefault();
        const type = $(this).data('type');
        const id = $(this).data('id');
        
        let title, url, columns, extraData = {};
        
        switch(type) {
            case 'licensee':
                title = 'Licensee Details';
                url = `${BASE_URL}api/get_licensee_details.php?licensee_id=${id}`;
                columns = [
                    { 
                        title: "Contract Name",
                        data: "contract_name",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return `<a href="#" class="table-link" data-type="contract" data-id="${row.contract_id}">${data}</a>`;
                            }
                            return data;
                        }
                    },
                    { title: "Type", data: "contract_type" },
                    { title: "Station", data: "station_code" },
                    { title: "Total Staff", data: "staff_count" },
                    { 
                        title: "Pending Staff",
                        data: "pending_staff",
                        render: function(data) {
                            return `<span class="status-pending">${data || 0}</span>`;
                        }
                    },
                    { 
                        title: "Approved Staff",
                        data: "approved_staff",
                        render: function(data) {
                            return `<span class="status-approved">${data || 0}</span>`;
                        }
                    },
                    { 
                        title: "Terminated Staff",
                        data: "terminated_staff",
                        render: function(data) {
                            return `<span class="status-terminated">${data || 0}</span>`;
                        }
                    }
                ];
                break;
            case 'section':
                title = `Section: ${id}`;
                url = `${BASE_URL}api/get_contracts_list.php`;
                columns = [
                    { 
                        title: "Contract Name",
                        data: "contract_name",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return `<a href="#" class="table-link" data-type="contract" data-id="${row.id}">${data}</a>`;
                            }
                            return data;
                        }
                    },
                    { title: "Type", data: "contract_type" },
                    { title: "Licensee", data: "licensee_name" },
                    { title: "Station", data: "station_code" },
                    { title: "Total Staff", data: "staff_count" },
                    { 
                        title: "Pending Staff",
                        data: "pending_staff",
                        render: function(data) {
                            return `<span class="status-pending">${data || 0}</span>`;
                        }
                    },
                    { 
                        title: "Approved Staff",
                        data: "approved_staff",
                        render: function(data) {
                            return `<span class="status-approved">${data || 0}</span>`;
                        }
                    },
                    { 
                        title: "Terminated Staff",
                        data: "terminated_staff",
                        render: function(data) {
                            return `<span class="status-terminated">${data || 0}</span>`;
                        }
                    }
                ];
                extraData = { section: id };
                break;
            case 'station':
                title = `Station: ${id}`;
                url = `${BASE_URL}api/get_contracts_list.php`;
                columns = [
                    { 
                        title: "Contract Name",
                        data: "contract_name",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return `<a href="#" class="table-link" data-type="contract" data-id="${row.id}">${data}</a>`;
                            }
                            return data;
                        }
                    },
                    { title: "Type", data: "contract_type" },
                    { title: "Licensee", data: "licensee_name" },
                    { title: "Section", data: "section_code" },
                    { title: "Total Staff", data: "staff_count" },
                    { 
                        title: "Pending Staff",
                        data: "pending_staff",
                        render: function(data) {
                            return `<span class="status-pending">${data || 0}</span>`;
                        }
                    },
                    { 
                        title: "Approved Staff",
                        data: "approved_staff",
                        render: function(data) {
                            return `<span class="status-approved">${data || 0}</span>`;
                        }
                    },
                    { 
                        title: "Terminated Staff",
                        data: "terminated_staff",
                        render: function(data) {
                            return `<span class="status-terminated">${data || 0}</span>`;
                        }
                    }
                ];
                extraData = { station: id };
                break;
            case 'contract_type':
                title = `Contract Type: ${id}`;
                url = `${BASE_URL}api/get_contracts_list.php`;
                columns = [
                    { 
                        title: "Contract Name",
                        data: "contract_name",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return `<a href="#" class="table-link" data-type="contract" data-id="${row.id}">${data}</a>`;
                            }
                            return data;
                        }
                    },
                    { title: "Licensee", data: "licensee_name" },
                    { title: "Station", data: "station_code" },
                    { title: "Section", data: "section_code" },
                    { title: "Total Staff", data: "staff_count" },
                    { 
                        title: "Pending Staff",
                        data: "pending_staff",
                        render: function(data) {
                            return `<span class="status-pending">${data || 0}</span>`;
                        }
                    },
                    { 
                        title: "Approved Staff",
                        data: "approved_staff",
                        render: function(data) {
                            return `<span class="status-approved">${data || 0}</span>`;
                        }
                    },
                    { 
                        title: "Terminated Staff",
                        data: "terminated_staff",
                        render: function(data) {
                            return `<span class="status-terminated">${data || 0}</span>`;
                        }
                    }
                ];
                extraData = { contract_type: id };
                break;
            case 'contract':
                title = 'Contract Staff Details';
                url = `${BASE_URL}api/get_staff_list.php`;
                columns = [
                    { title: "Staff ID", data: "id" },
                    { title: "Name", data: "name" },
                    { title: "Designation", data: "designation" },
                    { title: "Contact", data: "contact" },
                    { 
                        title: "Status",
                        data: "status"
                    }
                ];
                extraData = { contract_id: id };
                break;
            case 'staff':
                title = 'Staff Details';
                url = `${BASE_URL}api/get_staff_details.php`;
                columns = [
                    { title: "Name", data: "name" },
                    { title: "Designation", data: "designation" },
                    { title: "Contact", data: "contact" },
                    { title: "Contract", data: "contract_name" },
                    { title: "Contract Type", data: "contract_type" },
                    { title: "Station", data: "station_code" },
                    { title: "Licensee", data: "licensee_name" },
                    { title: "Licensee Mobile", data: "licensee_mobile" },
                    { 
                        title: "Police Verification",
                        data: null,
                        render: function(data) {
                            const issueDate = data.police_issue_date;
                            const expiryDate = data.police_expiry_date;
                            if (!expiryDate) return '<span class="badge bg-secondary">Not Available</span>';
                            
                            const date = new Date(expiryDate);
                            const today = new Date();
                            const daysUntilExpiry = Math.ceil((date - today) / (1000 * 60 * 60 * 24));
                            const formattedExpiry = new Date(expiryDate).toLocaleDateString();
                            const formattedIssue = issueDate ? new Date(issueDate).toLocaleDateString() : 'N/A';
                            
                            let badge = '';
                            if (daysUntilExpiry < 0) {
                                badge = `<span class="badge bg-danger">Expired on ${formattedExpiry}</span>`;
                            } else {
                                badge = `<span class="badge bg-${daysUntilExpiry <= 30 ? 'warning' : 'success'}">${formattedExpiry}</span>`;
                            }
                            return `${badge}<br><small>Issued: ${formattedIssue}</small>`;
                        }
                    },
                    { 
                        title: "Medical Certificate",
                        data: null,
                        render: function(data) {
                            const issueDate = data.medical_issue_date;
                            const expiryDate = data.medical_expiry_date;
                            if (!expiryDate) return '<span class="badge bg-secondary">Not Available</span>';
                            
                            const date = new Date(expiryDate);
                            const today = new Date();
                            const daysUntilExpiry = Math.ceil((date - today) / (1000 * 60 * 60 * 24));
                            const formattedExpiry = new Date(expiryDate).toLocaleDateString();
                            const formattedIssue = issueDate ? new Date(issueDate).toLocaleDateString() : 'N/A';
                            
                            let badge = '';
                            if (daysUntilExpiry < 0) {
                                badge = `<span class="badge bg-danger">Expired on ${formattedExpiry}</span>`;
                            } else {
                                badge = `<span class="badge bg-${daysUntilExpiry <= 30 ? 'warning' : 'success'}">${formattedExpiry}</span>`;
                            }
                            return `${badge}<br><small>Issued: ${formattedIssue}</small>`;
                        }
                    },
                    { 
                        title: "TA Document",
                        data: "ta_expiry_date",
                        render: function(data) {
                            if (!data) return '<span class="badge bg-secondary">Not Available</span>';
                            const date = new Date(data);
                            const today = new Date();
                            const daysUntilExpiry = Math.ceil((date - today) / (1000 * 60 * 60 * 24));
                            const formattedDate = new Date(data).toLocaleDateString();
                            if (daysUntilExpiry < 0) {
                                return `<span class="badge bg-danger">Expired on ${formattedDate}</span>`;
                            }
                            return `<span class="badge bg-${daysUntilExpiry <= 30 ? 'warning' : 'success'}">${formattedDate}</span>`;
                        }
                    },
                    { 
                        title: "Status",
                        data: "status",
                        render: function(data) {
                            return `<span class="status-${data.toLowerCase()}">${data}</span>`;
                        }
                    }
                ];
                extraData = { staff_id: id };
                break;
        }
        
        showDetailsModal(title, url, columns, extraData);
    });

    // --- FIX: Add click listeners to the main stat cards ---
    document.getElementById('licensees_card').addEventListener('click', () => {
        showDetailsModal('All Licensees', `${BASE_URL}api/get_licensees_list.php`, [
            { "data": "id", "title": "ID" },
            { "data": "name", "title": "Name" },
            { "data": "mobile_number", "title": "Mobile" },
            { "data": "status", "title": "Status" }
        ]);
    });

    document.getElementById('contracts_card').addEventListener('click', () => {
        showDetailsModal('All Contracts', `${BASE_URL}api/get_contracts_list.php`, [
            { "data": "id", "title": "ID" },
            { "data": "contract_name", "title": "Contract Name" },
            { "data": "contract_type", "title": "Type" },
            { "data": "station_code", "title": "Location" },
            { "data": "licensee_name", "title": "Licensee" },
            { "data": "status", "title": "Status" }
        ]);
    });

    document.getElementById('staff_card').addEventListener('click', () => {
        showDetailsModal('All Approved Staff', `${BASE_URL}api/get_approved_staff.php`, [
            { "data": "id", "title": "ID" },
            { "data": "name", "title": "Name" },
            { "data": "designation", "title": "Designation" },
            { "data": "contract_name", "title": "Contract" },
            { "data": "station_code", "title": "Station" }
        ]);
    });
});