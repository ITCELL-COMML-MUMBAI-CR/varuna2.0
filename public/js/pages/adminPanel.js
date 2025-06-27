/**
 * VARUNA System - Admin Panel Script (Full CRUD, Corrected Scope)
 * Current Time: Thursday, June 19, 2025 at 12:05 PM IST
 * Location: Kalyan, Maharashtra, India
 */
document.addEventListener("DOMContentLoaded", function () {
  const adminPageContainer = document.querySelector(".tab-container");
  if (!adminPageContainer) return;

  // --- 1. SETUP & GLOBAL ELEMENTS ---
  const editModal = document.getElementById("editRecordModal");
  const editModalTitle = document.getElementById("editModalTitle");
  const editModalBody = document.getElementById("editModalBody");
  let tables = {}; // To hold our DataTable instances

  // --- 2. HELPER FUNCTIONS ---

  function refreshToken(newToken) {
    if (newToken) {
      document
        .querySelector('meta[name="csrf-token"]')
        .setAttribute("content", newToken);
      document
        .querySelectorAll('form input[name="csrf_token"]')
        .forEach((input) => (input.value = newToken));
    }
  }

  function renderActions(row, tableInfo) {
    const id = row[tableInfo.id_column];
    return `<button class="btn-action edit" title="Edit" data-id="${id}" data-table="${tableInfo.name}" data-id-col="${tableInfo.id_column}">✏️</button> 
                <button class="btn-action reject" title="Delete" data-id="${id}" data-table="${tableInfo.name}" data-id-col="${tableInfo.id_column}">🗑️</button>`;
  }

  function handleAjaxSubmit(form, successCallback) {
    const formData = new FormData(form);
    fetch(form.action, { method: "POST", body: formData })
      .then((res) => {
        if (!res.ok)
          throw new Error(`Server responded with status: ${res.status}`);
        return res.json();
      })
      .then((data) => {
        if (data.new_csrf_token) {
          refreshToken(data.new_csrf_token);
        }
        if (data.success) {
          Swal.fire({
            toast: true,
            position: "top-end",
            icon: "success",
            title: data.message,
            showConfirmButton: false,
            timer: 3000,
          });
          if (successCallback) successCallback();
        } else {
          Swal.fire({
            icon: "error",
            title: "Operation Failed",
            text: data.message,
          });
        }
      })
      .catch((err) => {
        console.error("AJAX Submit Error:", err);
        Swal.fire(
          "Error",
          "A critical error occurred. Check the console.",
          "error"
        );
      });
  }

  async function buildEditForm(tableName, data) {
    let title = `Edit ${tableName.replace("varuna_", "").replace(/_/g, " ")}`;
    editModalTitle.textContent = title.replace(/\b\w/g, (l) => l.toUpperCase());
    let formContent = `<form id="editRecordForm" action="${BASE_URL}api/admin/update_record.php" method="POST">
            <input type="hidden" name="csrf_token" value="${
              document.querySelector('form input[name="csrf_token"]').value
            }">
            <input type="hidden" name="table_name" value="${tableName}">`;
    switch (tableName) {
      case "varuna_staff_designation":
        formContent += `<input type="hidden" name="id_column" value="id"><input type="hidden" name="id_value" value="${data.id}"><div class="input-group"><label>Designation Name</label><input type="text" name="designation_name" value="${data.designation_name}" required></div>`;
        break;
      case "varuna_users":
        let formData;
        try {
          const response = await fetch(`${BASE_URL}api/admin/get_form_data.php`);
          formData = await response.json();
        } catch (error) {
          console.error('Error fetching form data:', error);
          formData = { sections: [], department_sections: [] };
        }

        formContent += `<input type="hidden" name="id_column" value="id"><input type="hidden" name="id_value" value="${data.id}">
        <div class="details-grid">
          <div class="input-group"><label>Username</label><input type="text" name="username" value="${data.username}" required></div>
          <div class="input-group"><label>Role</label>
            <select name="role" required>
              <option value="ADMIN" ${data.role === "ADMIN" ? "selected" : ""}>ADMIN</option>
              <option value="SCI" ${data.role === "SCI" ? "selected" : ""}>SCI</option>
              <option value="VIEWER" ${data.role === "VIEWER" ? "selected" : ""}>VIEWER</option>
            </select>
          </div>
          <div class="input-group"><label>Geographical Section</label>
            <select name="section" required>
              <option value="" ${!data.section ? "selected" : ""}>Select Section</option>
              <option value="Train" ${data.section === "Train" ? "selected" : ""}>Train</option>
              ${formData.sections ? formData.sections.map(section => 
                `<option value="${section}" ${data.section === section ? "selected" : ""}>${section}</option>`
              ).join('') : ''}
            </select>
          </div>
          <div class="input-group"><label>Department Section</label>
            <select name="department_section" required>
              <option value="" ${!data.department_section ? "selected" : ""}>Select Department Section</option>
              ${formData.department_sections ? formData.department_sections.map(section => 
                `<option value="${section}" ${data.department_section === section ? "selected" : ""}>${section}</option>`
              ).join('') : ''}
            </select>
          </div>
        </div>`;
        break;
        case 'varuna_contract_types':
                formContent += `
                    <input type="hidden" name="id_column" value="ContractType">
                    <input type="hidden" name="id_value" value="${data.ContractType}">
                    <div class="input-group"><label>Contract Type Name (Cannot be changed)</label><input type="text" name="ContractType_display" value="${data.ContractType}" readonly></div>
                    <div class="input-group"><label>Train/Station</label>
                        <select name="TrainStation" required>
                            <option value="Station" ${data.TrainStation === 'Station' ? 'selected' : ''}>Station</option>
                            <option value="Train" ${data.TrainStation === 'Train' ? 'selected' : ''}>Train</option>
                        </select>
                    </div>
                    <div class="input-group"><label>Department Section</label><input type="text" name="Section" value="${data.Section}" required></div>
                    <h4 style="margin-top:15px;">Required Documents (Y/N)</h4>
                    <div class="details-grid" style="grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));">`;
                
                // This loop now dynamically creates all the Y/N dropdowns and selects the saved value
                const docFields = ['Police', 'Medical', 'TA', 'PPO', 'FSSAI', 'FireSafety', 'PestControl', 'RailNeerAvailability', 'WaterSafety'];
                docFields.forEach(field => {
                    const yesSelected = data[field] === 'Y' ? 'selected' : '';
                    const noSelected = data[field] === 'N' ? 'selected' : '';
                    formContent += `<div class="input-group">
                                    <label>${field}</label>
                                    <select name="${field}">
                                        <option value="Y" ${yesSelected}>Yes</option>
                                        <option value="N" ${noSelected}>No</option>
                                    </select>
                                 </div>`;
                });

                formContent += `</div>`;
                break;
    }
    formContent += `<div class="modal-actions"><button type="submit" class="btn-login">Save Changes</button></div></form>`;
    editModalBody.innerHTML = formContent;
  }

  // --- 3. UI INITIALIZATION & EVENT LISTENERS ---

  // Tab switching
  adminPageContainer.querySelectorAll(".tab-link").forEach((tab) => {
    tab.addEventListener("click", () => {
      adminPageContainer
        .querySelectorAll(".tab-link")
        .forEach((item) => item.classList.remove("active"));
      document
        .querySelectorAll(".tab-content")
        .forEach((item) => item.classList.remove("active"));
      tab.classList.add("active");
      const targetContent = document.getElementById(tab.dataset.tab);
      if (targetContent) {
        targetContent.classList.add("active");
        setTimeout(
          () =>
            $(targetContent)
              .find("table.dataTable")
              .DataTable()
              .columns.adjust(),
          10
        );
      }
    });
  });

  // Accordion
  document.querySelectorAll(".accordion-header").forEach((header) => {
    header.addEventListener("click", () => {
      const content = header.nextElementSibling;
      header.classList.toggle("active");
      content.style.maxHeight = content.style.maxHeight
        ? null
        : content.scrollHeight + "px";
      setTimeout(() => {
        $(content).find("table.dataTable").DataTable().columns.adjust();
      }, 400);
    });
  });

  // DataTables
  tables.contractTypesTable = $("#contractTypesTable").DataTable({
    ajax: {
      url: `${BASE_URL}api/admin/get_contract_types.php`,
      dataSrc: "data",
    },
    columns: [
      { data: "ContractType" },
      { data: "TrainStation" },
      { data: "Section" },
      {
        data: null,
        orderable: false,
        render: (d, t, r, m) =>
          renderActions(r, {
            name: "varuna_contract_types",
            id_column: "ContractType",
          }),
      },
    ],
  });
  tables.designationsTable = $("#designationsTable").DataTable({
    ajax: { url: `${BASE_URL}api/admin/get_designations.php`, dataSrc: "data" },
    columns: [
      { data: "id" },
      { data: "designation_name" },
      {
        data: null,
        orderable: false,
        render: (d, t, r, m) =>
          renderActions(r, {
            name: "varuna_staff_designation",
            id_column: "id",
          }),
      },
    ],
  });
  if (document.getElementById("usersTable")) {
    tables.usersTable = $("#usersTable").DataTable({
      ajax: { url: `${BASE_URL}api/admin/get_users.php`, dataSrc: "data" },
      columns: [
        { data: "id" },
        { data: "username" },
        { data: "role" },
        { data: "section" },
        { data: "department_section" },
        {
          data: null,
          orderable: false,
          render: (d, t, r, m) =>
            renderActions(r, { name: "varuna_users", id_column: "id" }),
        },
      ],
      initComplete: function() {
        // Add a button to view all users data
        const wrapper = $(this).closest('.dataTables_wrapper');
        $('<button class="btn-login" style="margin-bottom: 10px;">View All Users Data</button>')
          .prependTo(wrapper)
          .on('click', function() {
            // Show modal with all users data
            showUsersDataModal();
          });
      }
    });
  }
  
  // Function to show modal with all users data
  function showUsersDataModal() {
    editModalTitle.textContent = "All Users Data";
    editModalBody.innerHTML = "<p>Loading users data...</p>";
    editModal.classList.remove("hidden");
    
    fetch(`${BASE_URL}api/admin/get_users_data.php`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          const users = data.users;
          const sections = data.sections;
          const departmentSections = data.department_sections;
          
          let content = `
            <div class="users-data-summary">
              <h3>Users Summary</h3>
              <p>Total Users: ${users.length}</p>
              
              <h4>Geographical Sections</h4>
              <ul>
                <li>Train</li>
                ${sections.map(section => `<li>${section}</li>`).join('')}
              </ul>
              
              <h4>Department Sections</h4>
              <ul>
                ${departmentSections.map(section => `<li>${section}</li>`).join('')}
              </ul>
              
              <h4>Users by Role</h4>
              <ul>
                <li>ADMIN: ${users.filter(u => u.role === 'ADMIN').length}</li>
                <li>SCI: ${users.filter(u => u.role === 'SCI').length}</li>
                <li>VIEWER: ${users.filter(u => u.role === 'VIEWER').length}</li>
              </ul>
              
              <h4>Users by Section</h4>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Section</th>
                    <th>Count</th>
                  </tr>
                </thead>
                <tbody>
                  ${['Train', ...sections].map(section => {
                    const count = users.filter(u => u.section === section).length;
                    return count > 0 ? `<tr><td>${section}</td><td>${count}</td></tr>` : '';
                  }).join('')}
                </tbody>
              </table>
              
              <h4>Users by Department Section</h4>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Department Section</th>
                    <th>Count</th>
                  </tr>
                </thead>
                <tbody>
                  ${departmentSections.map(section => {
                    const count = users.filter(u => u.department_section === section).length;
                    return count > 0 ? `<tr><td>${section}</td><td>${count}</td></tr>` : '';
                  }).join('')}
                </tbody>
              </table>
              
              <h4>Complete User List</h4>
              <div class="filter-controls">
                <div class="search-box">
                  <input type="text" id="userSearchInput" placeholder="Search users..." class="form-control">
                </div>
                <div class="filter-selects">
                  <select id="roleFilter" class="form-control">
                    <option value="">All Roles</option>
                    <option value="ADMIN">ADMIN</option>
                    <option value="SCI">SCI</option>
                    <option value="VIEWER">VIEWER</option>
                  </select>
                  <select id="sectionFilter" class="form-control">
                    <option value="">All Geographical Sections</option>
                    <option value="Train">Train</option>
                    ${sections.map(section => `<option value="${section}">${section}</option>`).join('')}
                  </select>
                  <select id="deptSectionFilter" class="form-control">
                    <option value="">All Department Sections</option>
                    ${departmentSections.map(section => `<option value="${section}">${section}</option>`).join('')}
                  </select>
                </div>
                <div class="export-controls">
                  <button id="exportUsersCSV" class="btn-login">Export to CSV</button>
                </div>
              </div>
              <table id="usersDataTable" class="data-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Geographical Section</th>
                    <th>Department Section</th>
                  </tr>
                </thead>
                <tbody>
                  ${users.map(user => `
                    <tr data-role="${user.role}" data-section="${user.section || ''}" data-dept-section="${user.department_section || ''}">
                      <td>${user.id}</td>
                      <td>${user.username}</td>
                      <td>${user.role}</td>
                      <td>${user.section || '-'}</td>
                      <td>${user.department_section || '-'}</td>
                    </tr>
                  `).join('')}
                </tbody>
              </table>
              <script>
                // Initialize filtering functionality after the modal is shown
                setTimeout(() => {
                  const searchInput = document.getElementById('userSearchInput');
                  const roleFilter = document.getElementById('roleFilter');
                  const sectionFilter = document.getElementById('sectionFilter');
                  const deptSectionFilter = document.getElementById('deptSectionFilter');
                  const table = document.getElementById('usersDataTable');
                  const rows = table.querySelectorAll('tbody tr');
                  
                  function filterTable() {
                    const searchText = searchInput.value.toLowerCase();
                    const roleValue = roleFilter.value;
                    const sectionValue = sectionFilter.value;
                    const deptSectionValue = deptSectionFilter.value;
                    
                    rows.forEach(row => {
                      const rowData = row.textContent.toLowerCase();
                      const rowRole = row.getAttribute('data-role');
                      const rowSection = row.getAttribute('data-section');
                      const rowDeptSection = row.getAttribute('data-dept-section');
                      
                      const matchesSearch = searchText === '' || rowData.includes(searchText);
                      const matchesRole = roleValue === '' || rowRole === roleValue;
                      const matchesSection = sectionValue === '' || rowSection === sectionValue;
                      const matchesDeptSection = deptSectionValue === '' || rowDeptSection === deptSectionValue;
                      
                      if (matchesSearch && matchesRole && matchesSection && matchesDeptSection) {
                        row.style.display = '';
                      } else {
                        row.style.display = 'none';
                      }
                    });
                  }
                  
                  searchInput.addEventListener('input', filterTable);
                  roleFilter.addEventListener('change', filterTable);
                  sectionFilter.addEventListener('change', filterTable);
                  deptSectionFilter.addEventListener('change', filterTable);
                  
                  // Export to CSV functionality
                  document.getElementById('exportUsersCSV').addEventListener('click', function() {
                    // Get visible rows only (respecting current filters)
                    const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
                    
                    if (visibleRows.length === 0) {
                      alert('No data to export. Please adjust your filters.');
                      return;
                    }
                    
                    // Create CSV content
                    const headers = ['ID', 'Username', 'Role', 'Geographical Section', 'Department Section'];
                    let csvContent = headers.join(',') + '\n';
                    
                    visibleRows.forEach(row => {
                      const cells = row.querySelectorAll('td');
                      const rowData = Array.from(cells).map(cell => {
                        // Escape commas and quotes in cell content
                        let content = cell.textContent.trim();
                        if (content.includes(',') || content.includes('"')) {
                          content = '"' + content.replace(/"/g, '""') + '"';
                        }
                        return content;
                      });
                      csvContent += rowData.join(',') + '\n';
                    });
                    
                    // Create download link
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.setAttribute('href', url);
                    link.setAttribute('download', 'varuna_users_export.csv');
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                  });
                }, 100);
              </script>
            </div>
          `;
          
          editModalBody.innerHTML = content;
        } else {
          editModalBody.innerHTML = "<p>Error loading users data</p>";
        }
      })
      .catch(err => {
        console.error("Error fetching users data:", err);
        editModalBody.innerHTML = "<p>Error loading users data</p>";
      });
  }

  // Event delegation for all form submissions
  document.body.addEventListener("submit", function (event) {
    const form = event.target;
    if (
      form.matches(
        "#addContractTypeForm, #addDesignationForm, #changePasswordForm"
      )
    ) {
      event.preventDefault();
      handleAjaxSubmit(form, () => {
        form.reset();
        for (const key in tables) {
          if ($.fn.DataTable.isDataTable(tables[key]))
            tables[key].ajax.reload();
        }
      });
    }
    if (form.matches("#editRecordForm")) {
      event.preventDefault();
      handleAjaxSubmit(form, () => {
        editModal.classList.add("hidden");
        for (const key in tables) {
          if ($.fn.DataTable.isDataTable(tables[key]))
            tables[key].ajax.reload();
        }
      });
    }
  });

  // Event delegation for all action buttons
  document
    .querySelector(".page-container")
    .addEventListener("click", function (event) {
      const button = event.target.closest(".btn-action");
      if (!button) return;

      const id = button.dataset.id;
      const table = button.dataset.table;
      const idCol = button.dataset.idCol;

      if (button.classList.contains("edit")) {
        editModalBody.innerHTML = "<p>Loading...</p>";
        editModal.classList.remove("hidden");
        fetch(
          `${BASE_URL}api/admin/get_record_for_edit.php?table=${table}&id=${encodeURIComponent(
            id
          )}`
        )
          .then((res) => res.json())
          .then(async (response) => { // Make the callback async
            if (response.success) {
              await buildEditForm(table, response.data); // Await the async function
            }
          });
      }

      if (button.classList.contains("reject")) {
        Swal.fire({
          title: "Are you sure?",
          text: "This action cannot be undone!",
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
              document.querySelector('form input[name="csrf_token"]').value
            );
            fetch(`${BASE_URL}api/admin/delete_record.php`, {
              method: "POST",
              body: formData,
            })
              .then((res) => res.json())
              .then((data) => {
                if (data.new_csrf_token) {
                  refreshToken(data.new_csrf_token);
                }
                if (data.success) {
                  Swal.fire("Deleted!", data.message, "success");
                  for (const key in tables) {
                    if ($.fn.DataTable.isDataTable(tables[key]))
                      tables[key].ajax.reload();
                  }
                } else {
                  Swal.fire("Error!", data.message, "error");
                }
              });
          }
        });
      }
    });
$('#addNewUserForm').on('submit', function(e) {
    e.preventDefault();
    var form = this;
    var formData = new FormData(form);

    // Using your existing Swal and AJAX pattern
    Swal.fire({
        title: 'Are you sure?',
        text: "You are about to create a new user.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, create user!',
        cancelButtonText: 'No, cancel!',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: form.action,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Created!', response.message, 'success');
                        form.reset();
                        // Reload the users table to show the new entry
                        if ($.fn.DataTable.isDataTable('#usersTable')) {
                            $('#usersTable').DataTable().ajax.reload(null, false);
                        }
                    } else {
                        Swal.fire('Error!', response.message || 'Could not create user.', 'error');
                    }
                    // Refresh CSRF token if your API provides it
                    if (response.new_csrf_token) {
                         $('input[name="csrf_token"]').val(response.new_csrf_token);
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'An unexpected error occurred.', 'error');
                }
            });
        }
    });
});
  // Close Modal Logic
  editModal.addEventListener("click", function (e) {
    if (e.target.matches(".modal-overlay, .modal-close-btn"))
      editModal.classList.add("hidden");
  });
});
