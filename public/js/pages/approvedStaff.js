/**
 * VARUNA System - Approved Staff Page Script
 * FIX: Added Edit and Delete functionality.
 */
document.addEventListener("DOMContentLoaded", function () {
  const tableElement = $("#approved_staff_table");
  if (!tableElement.length) return;

  // --- 1. DATATABLE INITIALIZATION ---
  const approvedTable = tableElement.DataTable({
    processing: true,
    ajax: { url: `${BASE_URL}api/get_approved_staff.php`, dataSrc: "data" },
    columns: [
      { data: "id" },
      { data: "name" },
      { data: "designation" },
      { data: "contract_name" },
      { data: "station_code" },
      {
        data: "id",
        orderable: false,
        render: function (data, type, row) {
          const printUrl = `${BASE_URL}id_card.php?staff_id=${data}`;
          // Add new Edit and Delete buttons alongside the Print link
          return `
                        <a href="${printUrl}" target="_blank" class="btn-action view" title="Print ID Card">🖨️</a>
                        <button class="btn-action edit" title="Edit Staff" data-staff-id="${data}">✏️</button>
                        <button class="btn-action terminate" title="Terminate Staff" data-staff-id="${data}">⏻</button>
                        <button class="btn-action reject" title="Delete Staff" data-staff-id="${data}">🗑️</button>
                    `;
        },
      },
    ],
  });

  // --- 2. MODAL AND FORM HANDLING ---

  const editModal = document.getElementById("staff_edit_modal");
  const editModalBody = document.getElementById("edit_modal_body");
  let csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute("content");

  function refreshToken(newToken) {
    if (newToken) {
      csrfToken = newToken;
      document
        .querySelector('meta[name="csrf-token"]')
        .setAttribute("content", newToken);
    }
  }

  // Function to build and show the edit modal (adapted from staffApproval.js)
  async function showEditModal(staffId) {
    editModalBody.innerHTML = "<p>Loading form for editing...</p>";
    editModal.classList.remove("hidden");

    try {
      const response = await fetch(
        `${BASE_URL}api/get_staff_for_edit.php?id=${staffId}`
      );
      const data = await response.json();

      if (data.success) {
        // This function (to build the form) needs to be defined or adapted from staffApproval.js
        // For simplicity here, we will assume a similar function `buildEditForm` exists.
        // This will be a large function, so we will implement it by reusing existing patterns.
        // The key is that the data is fetched and the modal is shown.
        buildCompleteEditForm(data.staff, data.doc_reqs, data.all_contracts);
      } else {
        throw new Error(data.message || "Could not load data.");
      }
    } catch (error) {
      editModalBody.innerHTML = `<p class="error-text">Error: ${error.message}</p>`;
    }
  }

  // This function builds the form HTML. It's a simplified version of the one in staffApproval.js
  async function buildCompleteEditForm(staff, docReqs, allContracts) {
    const designationResponse = await fetch(
      `${BASE_URL}api/get_designations.php`
    );
    const designationData = await designationResponse.json();
    const designations = designationData.designations || [];

    let designationOptions = designations
      .map(
        (d) =>
          `<option value="${d}" ${
            d === staff.designation ? "selected" : ""
          }>${d}</option>`
      )
      .join("");
    let contractOptions = allContracts
      .map(
        (c) =>
          `<option value="${c.id}" ${
            c.id === staff.contract_id ? "selected" : ""
          }>${c.contract_name} (${c.station_code})</option>`
      )
      .join("");

    let docFieldsHTML = "";
    const docMapping = {
      Police: "police",
      Medical: "medical",
      TA: "ta",
      PPO: "ppo",
      Profile: "profile",
      Signature: "signature",
    };

    for (const key in docMapping) {
      const name = docMapping[key];
      const isRequiredByContract = docReqs && docReqs[key] === "Y";
      const isProfileOrSig = key === "Profile" || key === "Signature";

      if (isRequiredByContract || isProfileOrSig) {
        const existingFile = staff[name + "_image"];
        const existingFileLink = existingFile
          ? `<div class="current-doc-link">Current: <a href="${BASE_URL}uploads/staff/${existingFile}" target="_blank">View</a></div>`
          : '<div class="current-doc-link">No file uploaded.</div>';

        docFieldsHTML += `<div class="input-group grid-full-width"><label>${key} Image ${existingFileLink}</label><input type="file" name="${name}_image" accept="image/*"></div>`;

        if (key === "Police" || key === "Medical") {
          docFieldsHTML += `
                        <div class="input-group"><label>${key} Issue Date</label><input type="date" name="${name}_issue_date" value="${
            staff[name + "_issue_date"] || ""
          }"></div>
                        <div class="input-group"><label>${key} Expiry Date</label><input type="date" name="${name}_expiry_date" value="${
            staff[name + "_expiry_date"] || ""
          }" readonly></div>`;
        }
      }
    }

    editModalBody.innerHTML = `
            <form id="editStaffFormApproved" action="${BASE_URL}api/update_staff_details.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="${csrfToken}">
                <input type="hidden" name="staff_id" value="${staff.id}">
                <h4>Core Details</h4>
                <div class="details-grid">
                    <div class="input-group"><label>Full Name</label><input type="text" name="name" value="${
                      staff.name || ""
                    }" required></div>
                    <div class="input-group"><label>Designation</label><select name="designation" required>${designationOptions}</select></div>
                    <div class="input-group"><label>Contact</label><input type="text" name="contact" value="${
                      staff.contact || ""
                    }" required></div>
                    <div class="input-group"><label>Aadhar</label><input type="text" name="adhar_card_number" value="${
                      staff.adhar_card_number || ""
                    }"></div>
                    <div class="input-group grid-full-width"><label>Change Contract</label><select name="contract_id" required>${contractOptions}</select></div>
                </div>
                <h4 class="modal-section-title">Update Documents (Upload a file only to replace an existing one)</h4>
                <div class="details-grid">${docFieldsHTML}</div>
                <div class="modal-actions"><button type="submit" class="btn-login">Update & Resubmit for Approval</button></div>
            </form>`;
  }
  // --- 3. EVENT LISTENERS ---

  tableElement.on("click", ".btn-action.edit", function () {
    showEditModal($(this).data("staff-id"));
  });

  tableElement.on("click", ".btn-action.reject", function () {
    const staffId = $(this).data("staff-id");
    Swal.fire({
      title: "Are you sure?",
      text: `You are about to permanently delete staff member ${staffId}. This cannot be undone!`,
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
              Swal.fire("Deleted!", response.message, "success");
              approvedTable.ajax.reload(null, false);
            } else {
              Swal.fire("Error!", response.message, "error");
            }
          });
      }
    });
  });

  $(document).on("submit", "#editStaffFormApproved", function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch(this.action, { method: "POST", body: formData })
      .then((res) => res.json())
      .then((response) => {
        refreshToken(response.new_csrf_token);
        if (response.success) {
          editModal.classList.add("hidden");
          Swal.fire("Updated!", response.message, "success");
          approvedTable.ajax.reload(null, false);
        } else {
          Swal.fire("Error!", response.message, "error");
        }
      });
  });

  editModal.addEventListener("click", function (e) {
    if (e.target.matches(".modal-overlay, .modal-close-btn")) {
      editModal.classList.add("hidden");
    }
  });
   tableElement.on('click', '.btn-action.terminate', function() {
        const staffId = $(this).data('staff-id');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        Swal.fire({
            title: 'Terminate this Staff Member?',
            text: `This will set staff member ${staffId}'s status to Terminated. They can be re-approved later by an SCI.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, terminate!',
            confirmButtonColor: '#d9534f'
        }).then(result => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('staff_id', staffId);
                formData.append('csrf_token', csrfToken);
                fetch(`${BASE_URL}api/terminate_staff.php`, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(response => {
                        refreshToken(response.new_csrf_token);
                        if (response.success) {
                            Swal.fire('Terminated!', response.message, 'success');
                            approvedTable.ajax.reload(null, false);
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    });
            }
        });
    });
});
