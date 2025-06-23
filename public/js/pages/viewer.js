document.addEventListener("DOMContentLoaded", function () {
  const tableEl = $("#viewer_staff_table");
  if (!tableEl.length) return;

  const viewerTable = tableEl.DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": `${BASE_URL}api/get_viewer_staff.php`, // Use the correct API
            "type": "POST",
            "data": function(d) {
                // Add filter data to the request
                d.filter_licensee = $('#filter_licensee').val();
                d.filter_contract = $('#filter_contract').val();
                d.filter_station = $('#filter_station').val();
                d.filter_section = $('#filter_section').val();
            }
        },
        "columns": [
            { "data": "profile_image", "orderable": false, "render": function(data) {
                const imageUrl = data ? `${BASE_URL}uploads/staff/${data}` : `${BASE_URL}images/default_profile.png`;
                return `<img src="${imageUrl}" style="width: 80px; height: 100px; object-fit: cover; border-radius: 4px;">`;
            }},
            { "data": null, "orderable": false, "render": function(data, type, row) {
                return `<strong>${row.name}</strong> (${row.id})<br>
                        <small>Designation: ${row.designation}</small><br>
                        <small>Contact: ${row.contact}</small><br>
                        <small>Aadhar: ${row.adhar_card_number || 'N/A'}</small><br>
                        <span class="status-${row.status}">Status: ${row.status}</span>`;
            }},
            { "data": null, "orderable": false, "render": function(data, type, row) {
                return `<strong>${row.contract_name}</strong><br>
                        <small>Licensee: ${row.licensee_name}</small><br>
                        <small>Location: ${row.station_code}</small>`;
            }},
            { "data": null, "orderable": false, "render": function(data, type, row) {
                let docLinks = '';
                if (row.police_image) {
                    docLinks += `<a href="${BASE_URL}uploads/staff/${row.police_image}" target="_blank">Police (Exp: ${row.police_expiry_date})</a><br>`;
                }
                if (row.medical_image) {
                    docLinks += `<a href="${BASE_URL}uploads/staff/${row.medical_image}" target="_blank">Medical (Exp: ${row.medical_expiry_date})</a>`;
                }
                return docLinks || 'No documents';
            }}
        ],
        "pageLength": 10
    });

  // Add event listeners to filter controls
  $(".filter-control").on("change", function () {
    viewerTable.ajax.reload();
  });

  // PDF Export functionality
   $('#export_pdf').on('click', async function() {
    Swal.fire({
        title: 'Generating PDF',
        html: 'Fetching all records, please wait...',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => { Swal.showLoading(); }
    });

    // 1. Fetch data from the API
    const formData = new FormData();
    formData.append('filter_licensee', $('#filter_licensee').val());
    formData.append('filter_contract', $('#filter_contract').val());
    formData.append('filter_station', $('#filter_station').val());
    formData.append('filter_section', $('#filter_section').val());
    formData.append('search', viewerTable.search());

    const response = await fetch(`${BASE_URL}api/get_all_staff_for_export.php`, {
        method: 'POST', body: formData
    });
    const result = await response.json();

    if (!result.success || result.data.length === 0) {
        Swal.fire('No Data', 'There is no data to export.', 'warning');
        return;
    }

    // 2. Build the PDF body with properly structured image cells
    const bodyData = result.data.map(row => {
        // Create properly structured image cell
        const imageCell = row.image_data 
            ? { 
                image: row.image_data, 
                width: 45, 
                height: 55,
                fit: [45, 55], // Use fit array instead of 'cover'
                alignment: 'center'
              } 
            : { 
                text: 'No Image', 
                fontSize: 8, 
                italics: true, 
                alignment: 'center',
                margin: [0, 20, 0, 20] // Add vertical margin to center text
              };

        return [
            imageCell, // Remove the spread operator and extra properties
            { 
                text: `${row.name}\n(ID: ${row.id})`, 
                style: 'cell',
                alignment: 'left' // Better alignment for text
            },
            { 
                text: row.designation, 
                style: 'cell' 
            },
            { 
                text: `${row.contract_name || 'N/A'}\n(Licensee: ${row.licensee_name || 'N/A'})`, 
                style: 'cell',
                alignment: 'left' // Better alignment for longer text
            },
            { 
                text: row.station_code, 
                style: 'cell' 
            },
            { 
                text: row.status, 
                style: ['cell', `status_${row.status}`] 
            }
        ];
    });

    Swal.close();

    // 3. Define and create the PDF with improved styling
    const docDefinition = {
        pageSize: 'A4',
        pageOrientation: 'landscape',
        pageMargins: [20, 40, 20, 40], // Add proper margins
        content: [
            { 
                text: 'VARUNA System - Staff Master Report', 
                style: 'header',
                alignment: 'center',
                margin: [0, 0, 0, 20]
            },
            { 
                text: `Generated on: ${new Date().toLocaleString()}`, 
                alignment: 'right', 
                style: 'dateStyle',
                margin: [0, 0, 0, 20]
            },
            {
                style: 'tableStyle',
                table: {
                    headerRows: 1,
                    widths: [60, '*', 80, '*', 60, 60], // Adjusted widths for better layout
                    body: [
                        // Header row
                        [
                            {text: 'Photo', style: 'tableHeader'},
                            {text: 'Name & ID', style: 'tableHeader'},
                            {text: 'Designation', style: 'tableHeader'},
                            {text: 'Contract & Licensee', style: 'tableHeader'},
                            {text: 'Location', style: 'tableHeader'},
                            {text: 'Status', style: 'tableHeader'}
                        ],
                        ...bodyData
                    ]
                },
                layout: {
                    fillColor: function (rowIndex, node, columnIndex) {
                        return (rowIndex === 0) ? '#f8f9fa' : null; // Header background
                    },
                    hLineWidth: function (i, node) {
                        return 0.5; // Horizontal line width
                    },
                    vLineWidth: function (i, node) {
                        return 0.5; // Vertical line width
                    },
                    hLineColor: function (i, node) {
                        return '#dee2e6'; // Line color
                    },
                    vLineColor: function (i, node) {
                        return '#dee2e6'; // Line color
                    },
                    paddingLeft: function(i, node) { return 4; },
                    paddingRight: function(i, node) { return 4; },
                    paddingTop: function(i, node) { return 4; },
                    paddingBottom: function(i, node) { return 4; }
                }
            }
        ],
        styles: {
            header: { 
                fontSize: 18, 
                bold: true, 
                color: '#2c3e50'
            },
            dateStyle: {
                fontSize: 10,
                color: '#666666'
            },
            tableStyle: { 
                margin: [0, 0, 0, 0], 
                fontSize: 9 
            },
            tableHeader: { 
                bold: true, 
                fontSize: 10, 
                color: '#2c3e50', 
                alignment: 'center',
                margin: [0, 5, 0, 5]
            },
            cell: { 
                alignment: 'center', 
                margin: [2, 3, 2, 3],
                fontSize: 8
            },
            status_approved: { 
                bold: true, 
                color: '#28a745' 
            },
            status_pending: { 
                bold: true, 
                color: '#ffc107' 
            },
            status_rejected: { 
                bold: true, 
                color: '#dc3545' 
            },
            status_Terminated: { 
                bold: true, 
                color: '#6c757d' 
            }
        },
        defaultStyle: { 
            font: 'Roboto',
            fontSize: 9
        },
        watermark: { 
            text: 'VARUNA', 
            color: '#e9ecef', 
            opacity: 0.3, 
            bold: true, 
            italics: false,
            fontSize: 60
        }
    };

    // Generate and download the PDF
    pdfMake.createPdf(docDefinition).download(`VARUNA_Staff_Report_${Date.now()}.pdf`);
});
});
