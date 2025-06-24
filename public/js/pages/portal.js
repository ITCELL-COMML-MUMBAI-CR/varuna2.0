document.addEventListener("DOMContentLoaded", function () {
  // --- 1. GET ELEMENTS ---
  const contractsListContainer = document.getElementById("contracts_list");
  const staffSection = document.getElementById("staff_section");
  const staffHeader = document.getElementById("staff_header");
  const addNewStaffBtn = document.getElementById("add_new_staff_btn");
  const bulkPrintBtn = document.getElementById("bulk_print_btn");
  const staffTable = $("#portal_staff_table");
  let staffDataTable;
  let selectedContractId = null;
  let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  // --- 2. CORE FUNCTIONS ---
  function refreshToken(newToken) {
        if (newToken) {
            csrfToken = newToken;
            document.querySelector('meta[name="csrf-token"]').setAttribute('content', newToken);
        }
    }
  function buildStaffForm(formData) {
        const { isEdit, data, designations, docReqs } = formData;
    
        let designationOptions = designations.map(d => `<option value="${d}" ${data.designation == d ? 'selected' : ''}>${d}</option>`).join('');
    
        let docFieldsHTML = '';
        const docMapping = { Profile: 'profile', Signature: 'signature', Police: 'police', Medical: 'medical', TA: 'ta', PPO: 'ppo' };
    
        for (const key in docMapping) {
            const name = docMapping[key];
            const isRequiredOnAdd = !isEdit;
            const hasExpiry = (key === 'Police' || key === 'Medical');
            
            // Only render the field if it's required by the contract OR if it's the core Profile/Signature fields
            if ((docReqs && docReqs[key] === 'Y') || key === 'Profile' || key === 'Signature') {
                const existingFile = data[name + '_image'];
                const existingFileLink = existingFile ? `<div class="current-doc-link">Current: <a href="${BASE_URL}uploads/staff/${existingFile}" target="_blank">View File</a></div>` : '';

                // Profile and Signature are always required on add
                const requiredAttr = (key === 'Profile' || key === 'Signature') && isRequiredOnAdd ? 'required' : '';

                docFieldsHTML += `<div class="input-group grid-full-width">
                                <label>${key} Image ${existingFileLink}</label>
                                <input type="file" name="${name}_image" accept="image/*" ${requiredAttr}>
                             </div>`;
                
                if (hasExpiry) {
                    docFieldsHTML += `
                        <div class="input-group">
                            <label>${key} Issue Date</label>
                            <input type="date" id="${name}_issue_date" name="${name}_issue_date" value="${data[name + '_issue_date'] || ''}">
                         </div>
                         <div class="input-group">
                            <label>${key} Expiry Date</label>
                            <input type="date" id="${name}_expiry_date" name="${name}_expiry_date" value="${data[name + '_expiry_date'] || ''}" readonly>
                         </div>`;
                }
            }
        }
    
        return `
            <form id="staff_portal_form" class="styled-form" enctype="multipart/form-data" style="text-align: left;">
                <input type="hidden" name="csrf_token" value="${csrfToken}">
                <input type="hidden" name="contract_id" value="${selectedContractId}">
                ${isEdit ? `<input type="hidden" name="staff_id" value="${data.id}">` : ''}

                <h4>Core Details</h4>
                <div class="details-grid">
                    <div class="input-group"><label>Full Name</label><input type="text" name="name" value="${data.name || ''}" required></div>
                    <div class="input-group"><label>Designation</label><select name="designation" required>${designationOptions}</select></div>
                    <div class="input-group"><label>Contact</label><input type="text" name="contact" value="${data.contact || ''}" required></div>
                    <div class="input-group"><label>Aadhar (Optional)</label><input type="text" name="adhar_card_number" value="${data.adhar_card_number || ''}"></div>
                </div>
                
                <h4 style="margin-top: 15px;">Documents ${isEdit ? '(Upload a file only to replace an existing one)' : ''}</h4>
                <div class="details-grid">${docFieldsHTML}</div>
            </form>
        `;
    }

    async function openAddStaffModal() {
        if (!selectedContractId) { Swal.fire("Error", "Please select a contract first.", "error"); return; }
        
        const response = await fetch(`${BASE_URL}api/portal/get_portal_form_data.php?contract_id=${selectedContractId}`);
        const formData = await response.json();
        if (!formData.success) { Swal.fire("Error", "Could not load form data.", "error"); return; }

        Swal.fire({
            title: 'Add New Staff',
            html: buildStaffForm({ isEdit: false, data: {}, designations: formData.designations, docReqs: formData.doc_reqs }),
            width: '800px',
            showCancelButton: true,
            confirmButtonText: 'Submit Application',
            preConfirm: () => {
                const form = document.getElementById('staff_portal_form');
                const formData = new FormData(form);
                if (!formData.get('name') || !formData.get('designation') || !formData.get('contact')) { Swal.showValidationMessage('Please fill in all core details.'); return false; }
                return fetch(`${BASE_URL}api/portal/add_portal_staff.php`, { method: 'POST', body: formData }).then(res => res.json());
            }
        }).then(result => {
            if (result.isConfirmed) {
                refreshToken(result.value.new_csrf_token);
                if (result.value.success) {
                    Swal.fire('Success!', result.value.message, 'success');
                    staffDataTable.ajax.reload(null, false);
                } else { Swal.fire('Error!', result.value.message, 'error'); }
            }
        });
    }

    // --- FIX: This is the corrected and final version of the Edit Staff modal ---
    async function openEditStaffModal(staffId) {
        const [staffRes, formRes] = await Promise.all([
            fetch(`${BASE_URL}api/portal/get_staff_for_edit.php?id=${staffId}`),
            fetch(`${BASE_URL}api/portal/get_portal_form_data.php?contract_id=${selectedContractId}`)
        ]);

        const staffData = await staffRes.json();
        const formData = await formRes.json();

        if (!staffData.success || !formData.success) {
            Swal.fire("Error", "Could not load data for editing.", "error");
            return;
        }

        Swal.fire({
            title: `Edit Staff: ${staffData.staff.name}`,
            html: buildStaffForm({ isEdit: true, data: staffData.staff, designations: formData.designations, docReqs: formData.doc_reqs }),
            width: '800px',
            showCancelButton: true,
            confirmButtonText: 'Update & Resubmit',
            preConfirm: () => {
                const form = document.getElementById('staff_portal_form');
                const formData = new FormData(form);
                return fetch(`${BASE_URL}api/portal/update_portal_staff.php`, { method: 'POST', body: formData }).then(res => res.json());
            }
        }).then(result => {
            if (result.isConfirmed) {
                refreshToken(result.value.new_csrf_token);
                if (result.value.success) {
                    Swal.fire('Success!', result.value.message, 'success');
                    staffDataTable.ajax.reload(null, false);
                } else { Swal.fire('Error!', result.value.message, 'error'); }
            }
        });
    }

    addNewStaffBtn.addEventListener('click', openAddStaffModal);
  
  // Function to fetch and display contracts as cards
  function loadContracts() {
    contractsListContainer.innerHTML = "<p>Loading your contracts...</p>";
    fetch(`${BASE_URL}api/portal/get_portal_contracts.php`)
      .then((res) => res.json())
      .then((data) => {
        if (data.success && data.contracts.length > 0) {
          contractsListContainer.innerHTML = ""; // Clear loading message
          data.contracts.forEach((contract) => {
            const card = document.createElement("div");
            card.className = "details-card contract-card";
            card.dataset.contractId = contract.id;
            card.innerHTML = `
                            <h4>${contract.contract_name} <span class="status-${contract.status}" style="font-size: 0.8rem; float: right;">${contract.status}</span></h4>
                            <p><strong>Type:</strong> ${contract.contract_type}</p>
                            <p><strong>Location/Train:</strong> ${contract.station_code}</p>
                        `;
            contractsListContainer.appendChild(card);
          });
        } else {
          contractsListContainer.innerHTML = "<p>No contracts found.</p>";
        }
      })
      .catch((err) => {
        contractsListContainer.innerHTML =
          '<p class="error-text">Could not load contracts.</p>';
      });
  }

  // Function to initialize or reload the staff DataTable

  function initializeOrReloadDataTable(contractId) {
    selectedContractId = contractId;
    staffSection.classList.remove("hidden");

    if (staffDataTable) {
      staffDataTable.ajax
        .url(
          `${BASE_URL}api/portal/get_portal_staff.php?contract_id=${contractId}`
        )
        .load();
    } else {
      staffDataTable = staffTable.DataTable({
        processing: true,
        ajax: {
          url: `${BASE_URL}api/portal/get_portal_staff.php?contract_id=${contractId}`,
          dataSrc: "data",
        },
        columns: [
          { data: "id" },
          { data: "name" },
          { data: "designation" },
          { data: "contact" },
          {
            data: "status",
            render: function (data) {
              return `<span class="status-${data}">${data}</span>`;
            },
          },
          {
            data: null, // Use null for the actions column
            orderable: false,
            render: function (data, type, row) {
              
              let buttons = `<button class="btn-action edit" data-staff-id="${row.id}">✏️ Edit</button>`;

              if (row.status === "approved") {
                // If staff is approved, add an enabled Print ID button
                const printUrl = `${BASE_URL}id_card.php?staff_id=${row.id}`;
                buttons += ` <a href="${printUrl}" target="_blank" class="btn-action view" title="Print ID Card">🖨️ Print ID</a>`;
              } else {
                // If not approved, add a disabled button
                buttons += ` <button class="btn-action" title="ID card not available" disabled>🖨️ Print ID</button>`;
              }
              return buttons;
              
            },
          },
        ],
      });
      $("#portal_staff_table tbody").on("click", ".btn-action.edit", function () {
    const staffId = $(this).data("staff-id");
    openEditStaffModal(staffId);
  });
    }
  }

  // --- 3. EVENT LISTENERS ---
  document.addEventListener('change', function(event) {
    const targetId = event.target.id;

    if (targetId === 'police_issue_date' || targetId === 'medical_issue_date') {
        const issueDate = new Date(event.target.value);
        if (!isNaN(issueDate.getTime())) {
            const years = (targetId === 'police_issue_date') ? 3 : 1;
            issueDate.setFullYear(issueDate.getFullYear() + years);
            issueDate.setDate(issueDate.getDate() - 1);
            
            // We need to find the expiry input within the context of the modal
            const expiryInput = document.querySelector('#' + targetId.replace('issue', 'expiry'));
            if (expiryInput) {
                expiryInput.value = issueDate.toISOString().split('T')[0];
            }
        }
    }
});
  if (bulkPrintBtn) {
    bulkPrintBtn.addEventListener("click", function () {
      if (!selectedContractId) {
        Swal.fire(
          "No Contract Selected",
          "Please select a contract to print its staff IDs.",
          "warning"
        );
        return;
      }

      // Construct the URL for the existing bulk print page
      const bulkPrintUrl = `${BASE_URL}bulk_id_page.php?filter_by=contract&filter_value=${selectedContractId}`;

      // Open the URL in a new tab
      window.open(bulkPrintUrl, "_blank");
    });
  }

  // Handle clicks on contract cards
  contractsListContainer.addEventListener("click", function (event) {
    const card = event.target.closest(".contract-card");
    if (!card) return;

    // Highlight the selected card
    document
      .querySelectorAll(".contract-card")
      .forEach((c) => (c.style.borderColor = "#eee"));
    card.style.borderColor = "var(--primary-color)";

    const contractId = card.dataset.contractId;
    staffHeader.innerText = `Staff for: ${card
      .querySelector("h4")
      .innerText.split("<")[0]
      .trim()}`;
    initializeOrReloadDataTable(contractId);
  });

  

  // --- 4. INITIAL LOAD ---
  loadContracts();
});
