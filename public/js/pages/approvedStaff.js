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
        buildEditForm(data.staff, data.doc_reqs, data.all_contracts);
      } else {
        throw new Error(data.message || "Could not load data.");
      }
    } catch (error) {
      editModalBody.innerHTML = `<p class="error-text">Error: ${error.message}</p>`;
    }
  }

  // This function builds the form HTML. It's a simplified version of the one in staffApproval.js

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
          `<option value="${c.id}" ${c.id == s.contract_id ? "selected" : ""}>${
            c.contract_name
          } (${c.station_code})</option>`
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

  $(document).on("submit", "#editStaffForm", function (e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');

    submitButton.innerHTML = `<span><i class="fa fa-spinner fa-spin"></i> Updating...</span>`;
    submitButton.disabled = true;

    fetch(`${BASE_URL}api/update_staff_details.php`, {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((response) => {
        refreshToken(response.new_csrf_token);
        if (response.success) {
          editModal.classList.add("hidden");
          Swal.fire({
                toast: true,
                position: "top-end",
                icon: "success",
                title: response.message,
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
            });
          approvedTable.ajax.reload(null, false); // Refresh datatable
        } else {
          throw new Error(response.message || "An unknown error occurred.");
        }
      })
      .catch((error) => {
        Swal.fire("Error!", error.message, "error");
      })
      .finally(() => {
        submitButton.innerHTML = "Update & Resubmit";
        submitButton.disabled = false;
      });
  });

  editModal.addEventListener("click", function (e) {
    if (e.target.matches(".modal-overlay, .modal-close-btn")) {
      editModal.classList.add("hidden");
    }
  });
  editModal.addEventListener("change", function (event) {
    const targetId = event.target.id;

    if (targetId === "police_issue_date" || targetId === "medical_issue_date") {
      const issueDate = new Date(event.target.value);
      if (!isNaN(issueDate.getTime())) {
        const years = targetId === "police_issue_date" ? 3 : 1;
        issueDate.setFullYear(issueDate.getFullYear() + years);
        issueDate.setDate(issueDate.getDate() - 1);

        // We need to find the expiry input within the context of the modal
        const expiryInput = editModal.querySelector(
          "#" + targetId.replace("issue", "expiry")
        );
        if (expiryInput) {
          expiryInput.value = issueDate.toISOString().split("T")[0];
        }
      }
    }
  });
  tableElement.on("click", ".btn-action.terminate", function () {
    const staffId = $(this).data("staff-id");
    const csrfToken = document
      .querySelector('meta[name="csrf-token"]')
      .getAttribute("content");

    Swal.fire({
      title: "Terminate this Staff Member?",
      text: `This will Terminate staff member ${staffId}. They can be re-approved later by an SCI.`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Yes, terminate!",
      confirmButtonColor: "#d9534f",
    }).then((result) => {
      if (result.isConfirmed) {
        const formData = new FormData();
        formData.append("staff_id", staffId);
        formData.append("csrf_token", csrfToken);
        fetch(`${BASE_URL}api/terminate_staff.php`, {
          method: "POST",
          body: formData,
        })
          .then((res) => res.json())
          .then((response) => {
            refreshToken(response.new_csrf_token);
            if (response.success) {
              Swal.fire("Terminated!", response.message, "success");
              approvedTable.ajax.reload(null, false);
            } else {
              Swal.fire("Error!", response.message, "error");
            }
          });
      }
    });
  });
});
