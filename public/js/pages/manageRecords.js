/**
 * VARUNA System - Manage Records (Contracts & Licensees) Script
 * Current Time: Thursday, June 19, 2025 at 3:15 PM IST
 * Location: Kalyan, Maharashtra, India
 */
document.addEventListener("DOMContentLoaded", function () {
  const contractsTableEl = document.getElementById("contractsTable");
  const licenseesTableEl = document.getElementById("licenseesTable");
  if (!contractsTableEl && !licenseesTableEl) return;

  // --- 1. SETUP ---
  const editModal = document.getElementById("editRecordModal");
  const editModalTitle = document.getElementById("editModalTitle");
  const editModalBody = document.getElementById("editModalBody");
  let tables = {};

  // --- 2. HELPER FUNCTIONS ---

  // NEW: Reusable function to update all CSRF tokens on the page
  function refreshToken(newToken) {
    if (newToken) {
      document
        .querySelectorAll('form input[name="csrf_token"]')
        .forEach((input) => {
          input.value = newToken;
        });
      // Also update the global meta tag if it exists
      const metaToken = document.querySelector('meta[name="csrf-token"]');
      if (metaToken) {
        metaToken.setAttribute("content", newToken);
      }
    }
  }

  function renderActions(row, tableInfo) {
    const id = row[tableInfo.id_column];
    let buttons = `<button class="btn-action edit" title="Edit" data-id="${id}" data-table="${tableInfo.name}" data-id-col="${tableInfo.id_column}">✏️</button>
    <button class="btn-action terminate" title="Terminate" data-id="${id}" data-table="${tableInfo.name}">⏻</button>               
    <button class="btn-action reject" title="Delete" data-id="${id}" data-table="${tableInfo.name}" data-id-col="${tableInfo.id_column}">🗑️</button>`;

    // Only add the "Generate Link" button for the licensees table
    if (tableInfo.name === "varuna_licensee") {
      buttons += `<button class="btn-action link" title="Generate Access Link" data-id="${id}">🔗</button>`;
    }
    // Add the "View Documents" button exclusively for contracts table
    if (tableInfo.name === "contracts") {
      buttons = `<button class="btn-action docs" title="View Documents" data-id="${id}">📑</button>` + buttons;
    }
    return buttons;
  }

  async function buildEditForm(tableName, data) {
    let title = `Edit ${tableName.replace("varuna_", "").replace(/_/g, " ")}`;
    editModalTitle.textContent = title.replace(/\b\w/g, (l) => l.toUpperCase());

    // Get the current CSRF token from the page
    const csrfToken = document
      .querySelector('meta[name="csrf-token"]')
      .getAttribute("content");

    // Decide endpoint and enctype based on table name
    const actionUrl = tableName === 'contracts'
      ? `${BASE_URL}api/admin/update_contract.php`
      : `${BASE_URL}api/admin/update_record.php`;
    const encAttr = tableName === 'contracts' ? 'enctype="multipart/form-data"' : '';

    let formContent = `<form id="editRecordForm" action="${actionUrl}" method="POST" ${encAttr}>
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            ${tableName !== 'contracts' ? `<input type="hidden" name="table_name" value="${tableName}">` : ''}`;

    // Fetch form data if editing a user
    let formData = {};
    if (tableName === 'varuna_users') {
        try {
            const response = await fetch(`${BASE_URL}api/admin/get_form_data.php`);
            formData = await response.json();
            if (!formData.success) {
                throw new Error('Failed to load form data');
            }
        } catch (error) {
            console.error('Error loading form data:', error);
            Swal.fire('Error', 'Failed to load form data', 'error');
            return;
        }
    }

    switch (tableName) {
      case "varuna_licensee":
        formContent += `
                    <input type="hidden" name="id_column" value="id">
                    <input type="hidden" name="id_value" value="${data.id}">
                    <div class="input-group">
                        <label>Licensee Name</label>
                        <input type="text" name="name" value="${
                          data.name || ""
                        }" required>
                    </div>
                    <div class="input-group">
                        <label>Mobile Number</label>
                        <input type="text" name="mobile_number" value="${
                          data.mobile_number || ""
                        }" required>
                    </div>
                    <div class="input-group">
                        <label>Status</label>
                        <select name="status" required>
                            <option value="Active" ${data.status === 'Active' ? 'selected' : ''}>Active</option>
                            <option value="Terminated" ${data.status === 'Terminated' ? 'selected' : ''}>Terminated</option>
                        </select>
                    </div>
                    `;
        break;

      case "contracts":
        // Fetch document requirements synchronously before rendering form
        let docReqs = {};
        try {
          const res = await fetch(`${BASE_URL}api/get_contract_data.php?id=${encodeURIComponent(data.id)}`);
          const resJson = await res.json();
          if (resJson.success) docReqs = resJson.doc_reqs || {};
        } catch (e) { console.warn('Could not load doc requirements', e); }

        formContent += `
                    <input type="hidden" name="id_column" value="id">
                    <input type="hidden" name="id_value" value="${data.id}">
                    <div class="input-group">
                        <label>Contract Name</label>
                        <input type="text" name="contract_name" value="${
                          data.contract_name || ""
                        }" required>
                    </div>
                    <div class="input-group">
                        <label>Location</label>
                        <input type="text" name="location" value="${
                          data.location || ""
                        }" required>
                    </div>
                     <div class="input-group">
                        <label>Stalls</label>
                        <input type="number" name="stalls" value="${
                          data.stalls || ""
                        }">
                    </div>
                    <div class="input-group">
                        <label>License Fee</label>
                        <input type="text" name="license_fee" value="${
                          data.license_fee || ""
                        }" required>
                    </div>
                    <div class="input-group">
                        <label>Period</label>
                        <input type="text" name="period" value="${
                          data.period || ""
                        }" required>
                    </div>
                    <div class="input-group">
                        <label>Status</label>
                        <select name="status" required>
                            <option value="Active" ${data.status === 'Active' ? 'selected' : ''}>Active</option>
                            <option value="Under extension" ${data.status === 'Under extension' ? 'selected' : ''}>Under extension</option>
                            <option value="Expired" ${data.status === 'Expired' ? 'selected' : ''}>Expired</option>
                            <option value="Terminated" ${data.status === 'Terminated' ? 'selected' : ''}>Terminated</option>
                        </select>
                    </div>
                    <h3 style="margin-top:20px;">Documents</h3>
                    `;

        // Dynamically append document inputs based on docReqs flags
        const docInputMapping = {
          FSSAI: { field: 'fssai_image', label: 'FSSAI Image', current: data.fssai_image },
          FireSafety: { field: 'fire_safety_image', label: 'Fire Safety Image', current: data.fire_safety_image },
          PestControl: { field: 'pest_control_image', label: 'Pest Control Image', current: data.pest_control_image },
          WaterSafety: { field: 'water_safety_image', label: 'Water Safety Image', current: data.water_safety_image }
        };

        for (const [reqKey, cfg] of Object.entries(docInputMapping)) {
          if (docReqs[reqKey] === 'Y') {
            formContent += `
              <div class="input-group">
                <label>${cfg.label} ${cfg.current ? `(current: <a href=\"${BASE_URL}uploads/contracts/${cfg.current}\" target=\"_blank\">View</a>)` : ''}</label>
                <input type="file" name="${cfg.field}" accept="image/*">
              </div>`;
          }
        }

        if (docReqs.RailNeerAvailability === 'Y') {
          formContent += `<div class="input-group">
                          <label>Rail Neer Stock</label>
                          <input type="number" name="rail_neer_stock" value="${data.rail_neer_stock || ''}">
                       </div>`;
        }
        break;

      case "varuna_users":
        formContent += `
            <input type="hidden" name="id_column" value="id">
            <input type="hidden" name="id_value" value="${data.id}">
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" value="${data.username || ''}" required>
            </div>
            <div class="input-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="ADMIN" ${data.role === 'ADMIN' ? 'selected' : ''}>ADMIN</option>
                    <option value="SCI" ${data.role === 'SCI' ? 'selected' : ''}>SCI</option>
                    <option value="VIEWER" ${data.role === 'VIEWER' ? 'selected' : ''}>VIEWER</option>
                </select>
            </div>
            <div class="input-group">
                <label>Designation</label>
                <input type="text" name="designation" value="${data.designation || ''}">
            </div>
            <div class="input-group">
                <label>Geographical Section</label>
                <select name="section" required>
                    <option value="">-- Select Section --</option>
                    <option value="Train" ${data.section === 'Train' ? 'selected' : ''}>Train</option>
                    ${formData.sections?.map(section => 
                        `<option value="${section}" ${data.section === section ? 'selected' : ''}>${section}</option>`
                    ).join('')}
                </select>
            </div>
            <div class="input-group">
                <label>Department Section</label>
                <select name="department_section" required>
                    <option value="">-- Select Department --</option>
                    ${formData.department_sections?.map(dept => 
                        `<option value="${dept}" ${data.department_section === dept ? 'selected' : ''}>${dept}</option>`
                    ).join('')}
                </select>
            </div>`;
        break;
    }

    formContent += `<div class="modal-actions"><button type="submit" class="btn-login">Save Changes</button></div></form>`;
    editModalBody.innerHTML = formContent;
  }

  function showGeneratedLink(link) {
    Swal.fire({
      title: "Link Generated!",
      html: `
            <p>Share this secure link with the licensee. It will expire in 30 days.</p>
            <input id="swal-link-input" class="swal2-input" value="${link}" readonly style="width: 90%;">
        `,
      icon: "success",
      confirmButtonText: "Copy to Clipboard",
      preConfirm: () => {
        const linkInput = document.getElementById("swal-link-input");
        linkInput.select();
        linkInput.setSelectionRange(0, 99999); // For mobile devices
        document.execCommand("copy");
        return true;
      },
    }).then((result) => {
      if (result.isConfirmed) {
        Swal.fire({
          toast: true,
          position: "top-end",
          icon: "success",
          title: "Link copied!",
          showConfirmButton: false,
          timer: 2000,
        });
      }
    });
  }

  // --- 3. DATATABLE INITIALIZATIONS ---
  if (contractsTableEl) {
    tables.contractsTable = $(contractsTableEl).DataTable({
      ajax: { url: `${BASE_URL}api/get_contracts_list.php`, dataSrc: "data" },
      columns: [
        { data: "id" },
        { data: "contract_name" },
        { data: "contract_type" },
        { data: "station_code" },
        { data: "licensee_name" },
        { data: "status" },
        {
          data: null,
          orderable: false,
          render: (d, t, r) =>
            renderActions(r, { name: "contracts", id_column: "id" }),
        },
      ],
    });
  }
  if (licenseesTableEl) {
    tables.licenseesTable = $(licenseesTableEl).DataTable({
      ajax: { url: `${BASE_URL}api/get_licensees_list.php`, dataSrc: "data" },
      columns: [
        { data: "id" },
        { data: "name" },
        { data: "mobile_number" },
        { data: "status" },
        {
          data: null,
          orderable: false,
          render: (d, t, r) =>
            renderActions(r, { name: "varuna_licensee", id_column: "id" }),
        },
      ],
    });
  }

  // --- 4. EVENT LISTENERS ---
  $(".page-container").on("click", ".btn-action.link", function () {
    const licenseeId = $(this).data("id");
    const csrfToken = document
      .querySelector('meta[name="csrf-token"]')
      .getAttribute("content");

    Swal.fire({
      title: "Generate Access Link?",
      text: "Any previously generated link for this licensee will be deactivated.",
      icon: "info",
      showCancelButton: true,
      confirmButtonText: "Yes, generate it!",
    }).then((result) => {
      if (result.isConfirmed) {
        const formData = new FormData();
        formData.append("licensee_id", licenseeId);
        formData.append("csrf_token", csrfToken);

        fetch(`${BASE_URL}api/admin/generate_licensee_token.php`, {
          method: "POST",
          body: formData,
        })
          .then((res) => res.json())
          .then((data) => {
            refreshToken(data.new_csrf_token);
            if (data.success) {
              showGeneratedLink(data.link);
            } else {
              Swal.fire("Error!", data.message, "error");
            }
          });
      }
    });
  });
  // Handle Edit Button Clicks
  $(".page-container").on("click", ".btn-action.edit", function () {
    const id = $(this).data("id"),
      table = $(this).data("table");
    editModalBody.innerHTML = "<p>Loading...</p>";
    editModal.classList.remove("hidden");
    fetch(
      `${BASE_URL}api/admin/get_record_for_edit.php?table=${table}&id=${encodeURIComponent(
        id
      )}`
    )
      .then((res) => res.json())
      .then((response) => {
        if (response.success) {
          buildEditForm(table, response.data);
        }
      });
  });
// Handle Terminate Button Clicks
$('.page-container').on('click', '.btn-action.terminate', function() {
    const id = $(this).data('id');
    const table = $(this).data('table');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const entity = table === 'contracts' ? 'Contract' : 'Licensee';
    const apiUrl = `${BASE_URL}api/terminate_${entity.toLowerCase()}.php`;

    Swal.fire({
        title: `Terminate this ${entity}?`,
        text: `This will terminate the ${entity} and all associated records (Contracts and/or Staff). This action can be reversed by an SCI.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, terminate!',
        confirmButtonColor: '#d9534f'
    }).then(result => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append(`${entity.toLowerCase()}_id`, id);
            formData.append('csrf_token', csrfToken);
            fetch(apiUrl, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    refreshToken(data.new_csrf_token);
                    if (data.success) {
                        Swal.fire('Terminated!', data.message, 'success');
                        for (const key in tables) { if (tables[key] && $.fn.DataTable.isDataTable(tables[key])) tables[key].ajax.reload(null, false); }
                    } else { Swal.fire('Error!', data.message, 'error'); }
                });
        }
    });
});
  // Handle Delete Button Clicks
  $(".page-container").on("click", ".btn-action.reject", function () {
    const id = $(this).data("id"),
      table = $(this).data("table"),
      idCol = $(this).data("id-col");
    Swal.fire({
      title: "Are you sure?",
      text: "This cannot be undone!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Yes, delete it!",
    }).then((result) => {
      if (result.isConfirmed) {
        const formData = new FormData();
        formData.append("table_name", table);
        formData.append("id_value", id);
        formData.append("id_column", idCol);
        formData.append(
          "csrf_token",
          document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content")
        );
        fetch(`${BASE_URL}api/admin/delete_record.php`, {
          method: "POST",
          body: formData,
        })
          .then((res) => res.json())
          .then((data) => {
            refreshToken(data.new_csrf_token); // Refresh token on success/failure
            if (data.success) {
              Swal.fire("Deleted!", data.message, "success");
              for (const key in tables) {
                if (tables[key] && $.fn.DataTable.isDataTable(tables[key]))
                  tables[key].ajax.reload(null, false);
              }
            } else {
              Swal.fire("Error!", data.message, "error");
            }
          });
      }
    });
  });

  // Handle Edit Form Submission
  $(editModalBody).on("submit", "#editRecordForm", function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch(this.action, { method: "POST", body: formData })
      .then((res) => res.json())
      .then((data) => {
        refreshToken(data.new_csrf_token); // Refresh token on success/failure
        if (data.success) {
          editModal.classList.add("hidden");
          Swal.fire({
            toast: true,
            position: "top-end",
            icon: "success",
            title: data.message,
            showConfirmButton: false,
            timer: 3000,
          });
          for (const key in tables) {
            if (tables[key] && $.fn.DataTable.isDataTable(tables[key]))
              tables[key].ajax.reload(null, false);
          }
        } else {
          Swal.fire({
            icon: "error",
            title: "Update Failed",
            text: data.message,
          });
        }
      });
  });

  // Handle View Documents Button Clicks (Contracts)
  $(".page-container").on("click", ".btn-action.docs", async function () {
    const contractId = $(this).data("id");

    editModalBody.innerHTML = "<p>Loading documents...</p>";
    editModalTitle.textContent = "Contract Documents";
    editModal.classList.remove("hidden");

    try {
      const response = await fetch(`${BASE_URL}api/get_contract_data.php?id=${encodeURIComponent(contractId)}`);
      const result = await response.json();

      if (!result.success) {
        throw new Error(result.message || 'Unable to fetch contract data');
      }

      // Prepare document links/content filtered by required docs
      const contract = result.contract;
      const reqs = result.doc_reqs || {};
      const docsMapping = {
        fssai_image: { label: "FSSAI", reqKey: "FSSAI" },
        fire_safety_image: { label: "Fire Safety", reqKey: "FireSafety" },
        pest_control_image: { label: "Pest Control", reqKey: "PestControl" },
        water_safety_image: { label: "Water Safety", reqKey: "WaterSafety" }
      };

      let docsHtml = "";
      for (const [key, cfg] of Object.entries(docsMapping)) {
        if (reqs[cfg.reqKey] === 'Y') {
          if (contract[key]) {
            docsHtml += `<div class="doc-link"><a href="${BASE_URL}uploads/contracts/${contract[key]}" target="_blank">${cfg.label}</a></div>`;
          } else {
            docsHtml += `<div class="doc-missing">${cfg.label}: <em>Not uploaded</em></div>`;
          }
        }
      }

      // Rail Neer Stock placeholder
      if (reqs.RailNeerAvailability === 'Y') {
        if (contract.rail_neer_stock !== null && contract.rail_neer_stock !== undefined) {
          docsHtml += `<div class="rail-neer-stock"><strong>Rail Neer Stock:</strong> ${contract.rail_neer_stock}</div>`;
        } else {
          docsHtml += `<div class="rail-neer-stock"><strong>Rail Neer Stock:</strong> <em>Not recorded</em></div>`;
        }
      }

      editModalBody.innerHTML = docsHtml;
    } catch (err) {
      console.error(err);
      editModalBody.innerHTML = `<p style="color:red;">Failed to load documents.</p>`;
    }
  });

  // Close Modal Logic
  editModal.addEventListener("click", function (e) {
    if (e.target.matches(".modal-overlay, .modal-close-btn"))
      editModal.classList.add("hidden");
  });
});
