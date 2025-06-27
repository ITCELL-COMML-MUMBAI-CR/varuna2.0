/**
 * VARUNA System - Staff Approval Page Script (Complete & Corrected)
 * Implements the "View in Modal" and "Edit in Modal" workflows.
 * Current Time: Monday, June 16, 2025 at 5:19 PM IST
 * Location: Kalyan, Maharashtra, India
 */

document.addEventListener("DOMContentLoaded", function () {
  // Get all necessary page elements
    const tabs = document.querySelectorAll(".tab-link");
    const contents = document.querySelectorAll(".tab-content");
    const viewModal = document.getElementById("staff_details_modal");
    const viewModalBody = document.getElementById("modal_body");
    const editModal = document.getElementById("staff_edit_modal");
    const editModalBody = document.getElementById("edit_modal_body");
    const selectAllCheckbox = document.getElementById('select_all_pending');
    const bulkApproveBtn = document.getElementById('bulk_approve_btn');
    const bulkRejectBtn = document.getElementById('bulk_reject_btn');
    let pendingTable, rejectedTable, terminatedTable;
  // Use a unique element from the approval page to ensure this script only runs there
  const approvalPageContainer = document.querySelector(".tab-container");
  if (!approvalPageContainer) return;

  if (approvalPageContainer) {
    // --- 1. SETUP ---
    // Read the CSRF token from the meta tag once for all API calls
    let csrfToken = document
      .querySelector('meta[name="csrf-token"]')
      .getAttribute("content");

    function refreshToken(newToken) {
      if (newToken) {
        csrfToken = newToken; // Update the JS variable
        document
          .querySelector('meta[name="csrf-token"]')
          .setAttribute("content", newToken); // Update the meta tag in the DOM
        //console.log("CSRF Token refreshed.");
      }
    }
    // Reusable function to call the update_staff_status API
    function updateStatus(staff_id, status, remark = "") {
      const formData = new FormData();
      formData.append("staff_id", staff_id);
      formData.append("status", status);
      formData.append("remark", remark);
      formData.append("csrf_token", csrfToken);
      fetch(`${BASE_URL}api/update_staff_status.php`, {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((response) => {
          if (response.success) {
            refreshToken(response.new_csrf_token);
            viewModal.classList.add("hidden");
            Swal.fire("Success!", response.message, "success");
            reloadAllTables();
          } else {
            Swal.fire(
              "Error!",
              response.message || "An unknown error occurred.",
              "error"
            );
          }
        });
    }

    

    // --- 2. TAB & DATATABLE INITIALIZATION ---
    tabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        tabs.forEach((item) => item.classList.remove("active"));
        contents.forEach((item) => item.classList.remove("active"));
        tab.classList.add("active");
        document.getElementById(tab.dataset.tab).classList.add("active");
        $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
      });
    });

    // Add status count displays above each table
    const pendingCountHtml = `
        <div class="status-counts" style="margin-bottom: 15px;">
            <span class="status-count">Total Pending: <span id="pending_count">0</span></span>
        </div>
    `;
    $("#pending_staff_table").before(pendingCountHtml);

    const rejectedCountHtml = `
        <div class="status-counts" style="margin-bottom: 15px;">
            <span class="status-count">Total Rejected: <span id="rejected_count">0</span></span>
        </div>
    `;
    $("#rejected_staff_table").before(rejectedCountHtml);

    const terminatedCountHtml = `
        <div class="status-counts" style="margin-bottom: 15px;">
            <span class="status-count">Total Terminated: <span id="terminated_count">0</span></span>
        </div>
    `;
    $("#terminated_staff_table").before(terminatedCountHtml);

    pendingTable = $("#pending_staff_table").DataTable({
      ajax: { 
        url: `${BASE_URL}api/get_pending_staff.php`, 
        dataSrc: function(json) {
            $('#pending_count').text(json.data.length);
            return json.data;
        }
      },
      columns: [
        {
          data: "id",
          orderable: false,
          render: function (data) {
            return `<input type="checkbox" class="staff-checkbox" value="${data}">`;
          }
        },
        { data: "id" },
        { data: "name" },
        { data: "designation" },
        { data: "contract_name" },
        { data: "station_code" },
        {
          data: null,
          orderable: false,
          defaultContent: `<button class="btn-action view" title="View Details">View</button>`,
        },
      ],
      "order": [[ 1, 'asc' ]]
    });
 terminatedTable = $("#terminated_staff_table").DataTable({
    ajax: { 
      url: `${BASE_URL}api/get_terminated_staff.php`, 
      dataSrc: function(json) {
          $('#terminated_count').text(json.data.length);
          return json.data;
      }
    },
    columns: [
        { data: "id" },
        { data: "name" },
        { data: "designation" },
        { data: "contract_name" },
        {
            data: "id",
            orderable: false,
            // Only SCI can re-engage, and they do so by editing the record
            render: function(data, type, row) {
                return `<button class="btn-action edit" data-staff-id="${data}" title="Edit and Resubmit">✏️ Edit to Re-approve</button>`;
            }
        },
    ],
  });
    rejectedTable = $("#rejected_staff_table").DataTable({
      ajax: { 
        url: `${BASE_URL}api/get_rejected_staff.php`, 
        dataSrc: function(json) {
            $('#rejected_count').text(json.data.length);
            return json.data;
        }
      },
      columns: [
        { data: "id" },
        { data: "name" },
        { data: "designation" },
        { data: "remark" },
        { data: "remarked_by" },
        {
          data: "id",
          orderable: false,
          render: function(data, type, row) {
            return `
              <button class="btn-action edit" data-staff-id="${data}" title="Edit and Resubmit">✏️</button>
              <button class="btn-action reject" data-staff-id="${data}" title="Delete Staff">🗑️</button>
            `;
          }
        },
      ],
    });

    // --- 3. EVENT LISTENERS FOR TABLE BUTTONS ---

    // Listener for the "View" button in the PENDING table
    $("#pending_staff_table tbody").on("click", "button.view", function () {
      const rowData = pendingTable.row($(this).parents("tr")).data();
      if (!rowData) return;
      viewModalBody.innerHTML = "<p>Loading full staff details...</p>";
      viewModal.classList.remove("hidden");
      fetch(`${BASE_URL}api/get_staff_details.php?id=${rowData.id}`)
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            buildAndShowViewModal(data.staff);
          } else {
            viewModalBody.innerHTML = `<p class="error-text">Could not load details.</p>`;
          }
        });
    });

    // Listener for the "Edit" button in the REJECTED table
    $("#rejected_staff_table tbody").on("click", "button.edit", function () {
      const rowData = rejectedTable.row($(this).parents("tr")).data();
      if (!rowData) return;
      editModalBody.innerHTML = "<p>Loading form for editing...</p>";
      editModal.classList.remove("hidden");
      fetch(`${BASE_URL}api/get_staff_for_edit.php?id=${rowData.id}`)
        .then((res) => res.json())
        .then((response) => {
          if (response.success) {
            buildEditForm(
              response.staff,
              response.doc_reqs,
              response.all_contracts
            );
          } else {
            editModalBody.innerHTML =
              '<p class="error-text">Could not load data for editing.</p>';
          }
        });
    });

    // Listener for the "Delete" button in the REJECTED table
    $("#rejected_staff_table tbody").on("click", "button.reject", function () {
      const staffId = $(this).data("staff-id");
      Swal.fire({
        title: "Are you sure?",
        text: "You are about to permanently delete this staff member. This action cannot be undone!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete it!",
        confirmButtonColor: "#d9534f",
      }).then((result) => {
        if (result.isConfirmed) {
          const formData = new FormData();
          formData.append("staff_id", staffId);
          formData.append("csrf_token", csrfToken);
          fetch(`${BASE_URL}api/delete_staff.php`, {
            method: "POST",
            body: formData,
          })
            .then((res) => res.json())
            .then((response) => {
              refreshToken(response.new_csrf_token);
              if (response.success) {
                refreshToken(response.new_csrf_token);
                Swal.fire("Deleted!", response.message, "success");
                reloadAllTables();
              } else {
                Swal.fire("Error!", response.message, "error");
              }
            });
        }
      });
    });

    $("#terminated_staff_table tbody").on("click", "button.edit", function () {
    const rowData = terminatedTable.row($(this).parents("tr")).data();
    if (!rowData) return;
    // This reuses the existing edit modal functionality
    editModalBody.innerHTML = "<p>Loading form for editing...</p>";
    editModal.classList.remove('hidden');
    fetch(`${BASE_URL}api/get_staff_for_edit.php?id=${rowData.id}`)
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                buildEditForm(response.staff, response.doc_reqs, response.all_contracts);
            } else {
                editModalBody.innerHTML = '<p class="error-text">Could not load data for editing.</p>';
            }
        });
});

     if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('click', function() {
            document.querySelectorAll('#pending_staff_table .staff-checkbox').forEach(cb => cb.checked = this.checked);
        });
    }

    if (bulkApproveBtn) {
        bulkApproveBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('#pending_staff_table .staff-checkbox:checked')).map(cb => cb.value);
            if (selectedIds.length === 0) {
                Swal.fire("No Selection", "Please select at least one staff member to approve.", "warning");
                return;
            }
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to approve ${selectedIds.length} staff member(s).`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, approve them!',
                confirmButtonColor: '#5cb85c'
            }).then(result => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('staff_ids', JSON.stringify(selectedIds));
                    formData.append('csrf_token', csrfToken);
                    fetch(`${BASE_URL}api/bulk_approve_staff.php`, { method: 'POST', body: formData })
                        .then(res => res.json()).then(handleApiResponse);
                }
            });
        });
    }

    // New listener for the Bulk Reject button
    if (bulkRejectBtn) {
        bulkRejectBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('#pending_staff_table .staff-checkbox:checked')).map(cb => cb.value);
            if (selectedIds.length === 0) {
                Swal.fire("No Selection", "Please select at least one staff member to reject.", "warning");
                return;
            }
            
            // First, get the remark from the user
            Swal.fire({
                title: `Reject ${selectedIds.length} Staff Member(s)`,
                input: 'textarea',
                inputPlaceholder: 'Enter the reason for rejection here...',
                inputAttributes: { 'aria-label': 'Type your message here' },
                showCancelButton: true,
                confirmButtonText: 'Submit Rejection',
                confirmButtonColor: '#d9534f',
                inputValidator: (value) => {
                    if (!value) {
                        return 'A remark is required to reject staff!';
                    }
                }
            }).then(result => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('staff_ids', JSON.stringify(selectedIds));
                    formData.append('remark', result.value); // Add the remark to the request
                    formData.append('csrf_token', csrfToken);
                    
                    fetch(`${BASE_URL}api/bulk_reject_staff.php`, { method: 'POST', body: formData })
                        .then(res => res.json()).then(handleApiResponse);
                }
            });
        });
    }

    // Reusable function to handle the response from both bulk actions
    function handleApiResponse(response) {
        if (response.success) {
            refreshToken(response.new_csrf_token);
            Swal.fire('Success!', response.message, 'success');
            reloadAllTables();
            if(selectAllCheckbox) selectAllCheckbox.checked = false;
        } else {
            Swal.fire('Error!', response.message || 'An unknown error occurred.', 'error');
        }
    }

    function reloadAllTables() {
        if (pendingTable) pendingTable.ajax.reload();
        if (rejectedTable) rejectedTable.ajax.reload();
        if (terminatedTable) terminatedTable.ajax.reload();
    }
    // --- 4. MODAL AND FORM LOGIC ---

    // Helper function to build the VIEW modal content
    function buildAndShowViewModal(s) {
      let modalHTML = `<h2>${s.name} <small>(${s.id})</small></h2>`;
      modalHTML += `<div class="modal-top-images"><div class="modal-doc-item"><label>Profile Image</label><a href="${BASE_URL}uploads/staff/${s.profile_image}" target="_blank" title="Click to view full image"><img src="${BASE_URL}uploads/staff/${s.profile_image}" class="modal-profile-img" alt="Profile Image"></a></div><div class="modal-doc-item"><label>Signature</label><a href="${BASE_URL}uploads/staff/${s.signature_image}" target="_blank" title="Click to view full image"><img src="${BASE_URL}uploads/staff/${s.signature_image}" class="modal-signature-img" alt="Signature"></a></div></div>`;
      modalHTML += `<h4 class="modal-section-title">Staff Details</h4><div class="details-grid"><p><strong>Designation:</strong> ${
        s.designation || "N/A"
      }</p><p><strong>Contact:</strong> ${
        s.contact || "N/A"
      }</p><p><strong>Aadhar:</strong> ${
        s.adhar_card_number || "N/A"
      }</p><p><strong>Status:</strong> <span class="status-${s.status}">${
        s.status
      }</span></p></div>`;
      modalHTML += `<h4 class="modal-section-title">Submitted Documents</h4><div class="modal-docs-grid">`;
      if (s.police_image)
        modalHTML += `<div class="modal-doc-item"><label>Police Verification (Expires: ${
          s.police_expiry_date || "N/A"
        })</label><a href="${BASE_URL}uploads/staff/${
          s.police_image
        }" target="_blank"><img src="${BASE_URL}uploads/staff/${
          s.police_image
        }" class="modal-doc-img" alt="Police Verification"></a></div>`;
      if (s.medical_image)
        modalHTML += `<div class="modal-doc-item"><label>Medical Fitness (Expires: ${
          s.medical_expiry_date || "N/A"
        })</label><a href="${BASE_URL}uploads/staff/${
          s.medical_image
        }" target="_blank"><img src="${BASE_URL}uploads/staff/${
          s.medical_image
        }" class="modal-doc-img" alt="Medical Fitness"></a></div>`;
      if (s.adhar_card_image)
        modalHTML += `<div class="modal-doc-item"><label>Aadhaar Card</label><a href="${BASE_URL}uploads/staff/${s.adhar_card_image}" target="_blank"><img src="${BASE_URL}uploads/staff/${s.adhar_card_image}" class="modal-doc-img" alt="Aadhaar Card"></a></div>`;
      if (s.ta_image)
        modalHTML += `<div class="modal-doc-item"><label>TA Document</label><a href="${BASE_URL}uploads/staff/${s.ta_image}" target="_blank"><img src="${BASE_URL}uploads/staff/${s.ta_image}" class="modal-doc-img" alt="TA Document"></a></div>`;
      if (s.ppo_image)
        modalHTML += `<div class="modal-doc-item"><label>PPO Document</label><a href="${BASE_URL}uploads/staff/${s.ppo_image}" target="_blank"><img src="${BASE_URL}uploads/staff/${s.ppo_image}" class="modal-doc-img" alt="PPO Document"></a></div>`;
      modalHTML += `</div><div class="modal-actions"><button class="btn-action reject" data-staff-id="${s.id}">✖ Reject</button><button class="btn-action approve" data-staff-id="${s.id}">✔ Approve</button></div>`;
      viewModalBody.innerHTML = modalHTML;
    }

    // Helper function to build the EDIT form modal. Declared as ASYNC to fix the 'await' error.
    async function buildEditForm(s, docReqs, allContracts) {
      const editModalBody = document.getElementById("edit_modal_body");

      // This part fetching designations and contracts is correct and remains the same
      let designationOptions = "";
      try {
        const response = await fetch(`${BASE_URL}api/get_designations.php`);
        const data = await response.json();
        if (data.success) {
          designationOptions = data.designations
            .map(
              (d) =>
                `<option value="${d}" ${
                  d == s.designation ? "selected" : ""
                }>${d}</option>`
            )
            .join("");
        }
      } catch (e) {
        console.error("Could not fetch designations", e);
      }

      let contractOptions = allContracts
        .map(
          (c) =>
            `<option value="${c.id}" ${
              c.id == s.contract_id ? "selected" : ""
            }>${c.contract_name} (${c.station_code})</option>`
        )
        .join("");

      // --- Build the main form structure ---
      let formHTML = `<form id="editStaffForm" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="${csrfToken}">
        <input type="hidden" name="staff_id" value="${s.id}">
        
        <h4>Edit Core Details</h4>
        <div class="details-grid">
            <div class="input-group"><label>Full Name</label><input type="text" name="name" value="${
              s.name || ""
            }" required></div>
            <div class="input-group"><label>Designation</label><select name="designation" required><option value="">-- Select --</option>${designationOptions}</select></div>
            <div class="input-group"><label>Contact</label><input type="text" name="contact" value="${
              s.contact || ""
            }" required></div>
            <div class="input-group"><label>Aadhar</label><input type="text" name="adhar_card_number" value="${
              s.adhar_card_number || ""
            }"></div>
            <div class="input-group grid-full-width"><label>Change Contract</label><select name="contract_id" required>${contractOptions}</select></div>
        </div>

        <h4 class="modal-section-title">Update Documents (Upload a file only to replace an existing one)</h4>
        <div class="details-grid" id="edit_doc_fields">
            </div>

        <div class="modal-actions"><button type="submit" class="btn-login">Update & Resubmit</button></div>
    </form>`;

      editModalBody.innerHTML = formHTML;

      const editDocFieldsContainer = document.getElementById("edit_doc_fields");
      editDocFieldsContainer.innerHTML = ""; // Clear it first

      // Add Profile and Signature fields first (These don't have dates)
      editDocFieldsContainer.innerHTML += `
        <div class="input-group"><label>Profile Image</label><div class="current-doc-link">Current: <a href="${BASE_URL}uploads/staff/${s.profile_image}" target="_blank">View File</a></div><input type="file" name="profile_image" accept="image/*"></div>
        <div class="input-group"><label>Signature Image</label><div class="current-doc-link">Current: <a href="${BASE_URL}uploads/staff/${s.signature_image}" target="_blank">View File</a></div><input type="file" name="signature_image" accept="image/*"></div>
        <div class="input-group"><label>Aadhar Card Image</label><div class="current-doc-link">Current: <a href="${BASE_URL}uploads/staff/${s.adhar_card_image}" target="_blank">View File</a></div><input type="file" name="adhar_card_image" accept="image/*"></div>
        <div class="input-group"></div>
    `;

      // --- CORRECTED LOGIC for other conditional document fields ---
      const docMapping = {
        Police: { l: "Police Verification", n: "police" },
        Medical: { l: "Medical Fitness", n: "medical" },
        TA: { l: "TA Document", n: "ta" },
        PPO: { l: "PPO Document", n: "ppo" },
      };
      for (const docKey in docMapping) {
        if (docReqs && docReqs[docKey] === "Y") {
          const docInfo = docMapping[docKey];
          const imageField = `${docInfo.n}_image`;
          const issueDateField = `${docInfo.n}_issue_date`;
          const expiryDateField = `${docInfo.n}_expiry_date`;
          const hasExpiryField = docKey === "Police" || docKey === "Medical";

          const currentDocHTML = s[imageField]
            ? `<div class="current-doc-link">Current: <a href="${BASE_URL}uploads/staff/${s[imageField]}" target="_blank">View File</a></div>`
            : '<div class="current-doc-link">No file uploaded.</div>';

          const docFieldHTML = `
                <div class="input-group grid-full-width">
                    <label>${docInfo.l} Image</label>
                    ${currentDocHTML}
                    <input type="file" name="${imageField}" accept="image/*">
                </div>
                <div class="input-group">
                    <label>${docInfo.l} Issue Date</label>
                    <div class="date-input-wrapper">
                        <input type="date" id="${issueDateField}" name="${issueDateField}" value="${
            s[issueDateField] || ""
          }">
                    </div>
                </div>
                ${
                  hasExpiryField
                    ? `
                <div class="input-group">
                    <label>${docInfo.l} Expiry Date</label>
                    <div class="date-input-wrapper">
                        <input type="date" id="${expiryDateField}" name="${expiryDateField}" value="${
                        s[expiryDateField] || ""
                      }" readonly>
                    </div>
                </div>`
                    : '<div class="input-group"></div>'
                }
            `;
          editDocFieldsContainer.insertAdjacentHTML("beforeend", docFieldHTML);
        }
      }
    }

    // Event listener for actions INSIDE the VIEW modal
    viewModal.addEventListener("click", async function (event) {
      const target = event.target;
      if (
        target.matches(".modal-overlay") ||
        target.matches(".modal-close-btn")
      ) {
        viewModal.classList.add("hidden");
      }
      if (
        target.matches(".btn-action.approve") ||
        target.matches(".btn-action.reject")
      ) {
        const staffId = target.dataset.staffId;
        const action = target.classList.contains("approve")
          ? "approved"
          : "rejected";
        if (action === "approved") {
          updateStatus(staffId, "approved");
        } else {
          const { value: remark } = await Swal.fire({
            title: "Enter Remark for Rejection",
            input: "textarea",
            inputPlaceholder: "Reason for rejection...",
            inputValidator: (value) => {
              if (!value) {
                return "A remark is required!";
              }
            },
            showCancelButton: true,
            confirmButtonText: "Submit Rejection",
          });
          if (remark) {
            updateStatus(staffId, "rejected", remark);
          }
        }
      }
    });

    // Event delegation to handle the dynamic EDIT form submission
    document.body.addEventListener("submit", function (event) {
      if (event.target.id === "editStaffForm") {
        event.preventDefault();
        const form = event.target;
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.innerHTML = "<span>Updating...</span>";
        submitButton.disabled = true;
        const formData = new FormData(form);
        fetch(`${BASE_URL}api/update_staff_details.php`, {
          method: "POST",
          body: formData,
        })
          .then((res) => res.json())
          .then((data) => {
            if (data.success) {
              refreshToken(data.new_csrf_token);
              editModal.classList.add("hidden");
              Swal.fire({
                toast: true,
                position: "top-end",
                icon: "success",
                title: data.message,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
              });
              reloadAllTables();
            } else {
              throw new Error(data.message || "Update failed.");
            }
          })
          .catch((error) => {
            Swal.fire("Error!", error.message, "error");
          })
          .finally(() => {
            submitButton.innerHTML = "Update & Resubmit";
            submitButton.disabled = false;
          });
      }
    });

editModal.addEventListener('change', function(event) {
    const targetId = event.target.id;

    if (targetId === 'police_issue_date' || targetId === 'medical_issue_date') {
        const issueDate = new Date(event.target.value);
        if (!isNaN(issueDate.getTime())) {
            const years = (targetId === 'police_issue_date') ? 3 : 1;
            issueDate.setFullYear(issueDate.getFullYear() + years);
            issueDate.setDate(issueDate.getDate() - 1);
            
            // We need to find the expiry input within the context of the modal
            const expiryInput = editModal.querySelector('#' + targetId.replace('issue', 'expiry'));
            if (expiryInput) {
                expiryInput.value = issueDate.toISOString().split('T')[0];
            }
        }
    }
});
    // Close listener for the EDIT modal
    editModal.addEventListener("click", function (event) {
      if (
        event.target.matches(".modal-overlay") ||
        event.target.matches(".modal-close-btn")
      ) {
        editModal.classList.add("hidden");
      }
    });

    
  }
});
