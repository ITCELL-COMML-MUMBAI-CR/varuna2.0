/**
 * VARUNA System - Staff Onboarding Page Script
 * Current Time: Monday, June 16, 2025 at 11:58 AM IST
 * Location: Kalyan, Maharashtra, India
 */

document.addEventListener("DOMContentLoaded", function () {
  const contractSelector = document.getElementById("contract_selector");
  if (!contractSelector) return;
  // Only run this script if the main contract selector element exists on the page
  if (contractSelector) {
    ////console.log("Staff Onboarding script loaded successfully.");

    // Get all necessary page elements once
    const contentContainer = document.getElementById("content_container");
    const contractDetailsContainer = document.getElementById(
      "contract_details_container"
    );
    const contractDetailsSection = document.getElementById(
      "contract_details_section"
    );
    const addStaffForm = document.getElementById("addStaffForm");
    const staffTable = $("#staff_table"); // jQuery object for DataTable
    const staffDocsContainer = document.getElementById(
      "staff_documents_container"
    );
    const contractIdField = document.getElementById("contract_id_field");
    const modal = document.getElementById("staff_details_modal");
    const modalBody = document.getElementById("modal_body");
  const adharImageInput = document.getElementById("adhar_card_image");
    let staffDataTable; // To hold the DataTable instance

    // Main Event Listener for Contract Selection
    contractSelector.addEventListener("change", function () {
      const contractId = this.value;
      document.getElementById("contract_id_field").value = contractId;
      //console.log(`Contract selected. ID: ${contractId}`);

      if (!contractId) {
        contentContainer.classList.add("hidden");
        contractDetailsContainer.classList.add("hidden");
        if (staffDataTable) {
          staffDataTable.clear().draw();
        }
        return;
      }

      contentContainer.classList.remove("hidden");
      contractDetailsContainer.classList.remove("hidden");
      addStaffForm.classList.remove("hidden");
      document.getElementById("contract_details_section").innerHTML =
        "<p>Loading details...</p>";

      //console.log("Fetching contract data...");
      fetch(`${BASE_URL}api/get_contract_data.php?id=${contractId}`)
        .then((response) =>
          response.ok
            ? response.json()
            : Promise.reject(`API error: ${response.statusText}`)
        )
        .then((data) => {
          //console.log("API Response Received:", data); // This will show you exactly what the API returns
          if (data.success) {
            populateContractDetails(data.contract);
            updateDocumentFields(data.doc_reqs);
            initializeOrReloadDataTable(contractId);
          } else {
            throw new Error(data.message || "API returned success:false");
          }
        })
        .catch((error) => {
          console.error("CRITICAL ERROR:", error);
          document.getElementById(
            "contract_details_section"
          ).innerHTML = `<p class="error-text">Error loading details. See console (F12) for more info.</p>`;
          addStaffForm.classList.add("hidden");
        });
    });

    // --- Helper Functions to Build UI ---

    function populateContractDetails(contract) {
      contractDetailsSection.innerHTML = `
                <h3>Contract Details</h3>
                <div class="details-grid">
                    <p><strong>Name:</strong> ${
                      contract.contract_name || "N/A"
                    }</p>
                    <p><strong>Licensee:</strong> ${
                      contract.licensee_name || "N/A"
                    }</p>
                    <p><strong>Location:</strong> ${
                      contract.location || "N/A"
                    }</p>
                    <p><strong>Type:</strong> ${
                      contract.contract_type || "N/A"
                    }</p>
                    <p><strong>Period:</strong> ${contract.period || "N/A"}</p>
                    <p><strong>Status:</strong> <span class="status-${
                      contract.status
                    }">${contract.status}</span></p>
                </div>
            `;
    }

    function updateDocumentFields(docReqs) {
    const staffDocsContainer = document.getElementById('staff_documents_container');
    staffDocsContainer.innerHTML = '';
    if (!docReqs || Object.keys(docReqs).length === 0) {
        ////console.log('No document requirements found for this contract type.');
        return;
    }
    ////console.log('Updating document fields based on:', docReqs);

    // Using 'l' for label and 'n' for name to match the corrected code below
    const docMapping = {
        'Police': { l: 'Police Verification', n: 'police' },
        'Medical': { l: 'Medical Fitness', n: 'medical' },
        'TA': { l: 'TA Document', n: 'ta' },
        'PPO': { l: 'PPO Document', n: 'ppo' }
    };

    for (const docKey in docMapping) {
        if (docReqs[docKey] === 'Y') {
            const docInfo = docMapping[docKey];
            const hasExpiryField = (docKey === 'Police' || docKey === 'Medical');
            
            // CORRECTED: Using docInfo.l and docInfo.n
            const fieldsHTML = `
                <div class="input-group">
                    <label>${docInfo.l} Image (Required)</label>
                    <input type="file" name="${docInfo.n}_image" required accept="image/*">
                </div>
                <div class="input-group">
                    <label>${docInfo.l} Issue Date (Required)</label>
                    <div class="date-input-wrapper">
                        <input type="date" id="${docInfo.n}_issue_date" name="${docInfo.n}_issue_date" required>
                    </div>
                </div>
                ${hasExpiryField ? `
                <div class="input-group">
                    <label>${docInfo.l} Expiry Date (Required)</label>
                    <div class="date-input-wrapper">
                        <input type="date" id="${docInfo.n}_expiry_date" name="${docInfo.n}_expiry_date" readonly>
                    </div>
                </div>` : '<div class="input-group"></div>'}
            `;
            staffDocsContainer.insertAdjacentHTML('beforeend', fieldsHTML);
        }
    }
}

   function initializeOrReloadDataTable(contractId) {
        if ($.fn.DataTable.isDataTable(staffTable)) {
            // Table already exists, just update its data source and reload
            staffDataTable.ajax.url(`${BASE_URL}api/get_staff_list.php`).load();
        } else {
            // Initialize the DataTable for the first time
            staffDataTable = staffTable.DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": `${BASE_URL}api/get_staff_list.php`,
                    "type": "POST",
                    // This function sends the currently selected contract ID with every request
                    "data": function(d) {
                        d.contract_id = $('#contract_selector').val();
                    }
                },
                "columns": [
                    { "data": "id" }, { "data": "name" }, { "data": "designation" },
                    { "data": "contact" }, { "data": "adhar_card_number" }, { "data": "status" }
                ]
            });
        }
    }

    // --- Interactive Client-Side Validation on Form Submit ---
    addStaffForm.addEventListener("submit", function (event) {
      event.preventDefault();
      let isFormValid = true;
      let firstInvalidField = null;
      const errorMessages = [];
      addStaffForm
        .querySelectorAll(".is-invalid")
        .forEach((el) => el.classList.remove("is-invalid"));
      const requiredFields = addStaffForm.querySelectorAll(
        "input[required], select[required]"
      );
      requiredFields.forEach((field) => {
        let fieldIsValid = true;
        if (field.type === "file" && field.files.length === 0) {
          fieldIsValid = false;
        } else if (!field.value.trim()) {
          fieldIsValid = false;
        }
        if (!fieldIsValid) {
          isFormValid = false;
          field.classList.add("is-invalid");
          if (!firstInvalidField) {
            firstInvalidField = field;
          }
          const label = field.closest(".input-group")?.querySelector("label");
          if (label && !errorMessages.includes(label.textContent)) {
            errorMessages.push(label.textContent);
          }
        }
      });
      if (isFormValid) {
        addStaffForm.submit();
      } else {
        if (firstInvalidField) {
          firstInvalidField.focus();
        }
        Swal.fire({
          toast: true,
          position: "top-end",
          icon: "error",
          title:
            "Please check the required fields: " + errorMessages.join(", "),
          showConfirmButton: false,
          timer: 5000,
          timerProgressBar: true,
        });
      }
    });

    // --- Event Delegation for Dynamic Content ---
    contentContainer.addEventListener("change", function (event) {
      const targetId = event.target.id;
      if (
        targetId === "police_issue_date" ||
        targetId === "medical_issue_date"
      ) {
        const issueDate = new Date(event.target.value);
        if (!isNaN(issueDate.getTime())) {
          const years = targetId === "police_issue_date" ? 3 : 1;
          issueDate.setFullYear(issueDate.getFullYear() + years);
          issueDate.setDate(issueDate.getDate() - 1);
          const expiryInput = document.getElementById(
            targetId.replace("issue", "expiry")
          );
          if (expiryInput)
            expiryInput.value = issueDate.toISOString().split("T")[0];
        }
      }
    });

    // --- Live validation for Name and Aadhar (with debounce) ---
    let debounceTimer;
    contentContainer.addEventListener("input", function (event) {
      const target = event.target;
      if (target.id === "staff_name" || target.id === "adhar_number") {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
          const value = target.value.trim();
          const type = target.id === "staff_name" ? "name" : "adhar";
          const warningEl = document.getElementById(`${type}_warning`);
          if (value.length > 2 && warningEl) {
            fetch(
              `${BASE_URL}api/check_staff_exists.php?${type}=${encodeURIComponent(
                value
              )}`
            )
              .then((res) => res.json())
              .then((data) => {
                warningEl.textContent = data.exists
                  ? `A user with this ${type} already exists.`
                  : "";
              });
          } else if (warningEl) {
            warningEl.textContent = "";
          }
        }, 500);
      }
    });

    // --- Modal Logic ---
    $('#staff_table tbody').on('click', '.staff-details-link', function(event) {
        event.preventDefault();
        const staffId = $(this).data('staff-id');
        
        modalBody.innerHTML = '<p>Loading staff details...</p>';
        modal.classList.remove('hidden');

        fetch(`${BASE_URL}api/get_staff_details.php?id=${staffId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const s = data.staff;
                    
                    // --- Build the new, detailed HTML structure for the modal ---
                    let modalHTML = `<h2>${s.name} <small>(${s.id})</small></h2>`;

                    // Top section: Profile and Signature images
                    modalHTML += `<div class="modal-top-images">
                        <div class="modal-doc-item">
                            <label>Profile Image</label>
                            <a href="${BASE_URL}uploads/staff/${s.profile_image}" target="_blank" title="Click to view full image">
                                <img src="${BASE_URL}uploads/staff/${s.profile_image}" class="modal-profile-img" alt="Profile Image">
                            </a>
                        </div>
                        <div class="modal-doc-item">
                            <label>Signature</label>
                            <a href="${BASE_URL}uploads/staff/${s.signature_image}" target="_blank" title="Click to view full image">
                                <img src="${BASE_URL}uploads/staff/${s.signature_image}" class="modal-signature-img" alt="Signature">
                            </a>
                        </div>
                    </div>`;

                    // Middle section: Textual details
                    modalHTML += `<h4 class="modal-section-title">Staff Details</h4>`;
                    modalHTML += `<div class="details-grid">
                        <p><strong>Designation:</strong> ${s.designation || 'N/A'}</p>
                        <p><strong>Contact:</strong> ${s.contact || 'N/A'}</p>
                        <p><strong>Aadhar:</strong> ${s.adhar_card_number || 'N/A'}</p>
                        <p><strong>Status:</strong> <span class="status-${s.status}">${s.status}</span></p>
                    </div>`;
                    
                    // Bottom section: Other documents
                    modalHTML += `<h4 class="modal-section-title">Submitted Documents</h4>`;
                    modalHTML += `<div class="modal-docs-grid">`;
                    
                    if (s.police_image) modalHTML += `
                        <div class="modal-doc-item">
                            <label>Police Verification (Expires: ${s.police_expiry_date || 'N/A'})</label>
                            <a href="${BASE_URL}uploads/staff/${s.police_image}" target="_blank" title="Click to view full image">
                                <img src="${BASE_URL}uploads/staff/${s.police_image}" class="modal-doc-img" alt="Police Verification">
                            </a>
                        </div>`;
                    
                    if (s.medical_image) modalHTML += `
                        <div class="modal-doc-item">
                            <label>Medical Fitness (Expires: ${s.medical_expiry_date || 'N/A'})</label>
                            <a href="${BASE_URL}uploads/staff/${s.medical_image}" target="_blank" title="Click to view full image">
                                 <img src="${BASE_URL}uploads/staff/${s.medical_image}" class="modal-doc-img" alt="Medical Fitness">
                            </a>
                        </div>`;

                    if (s.ta_image) modalHTML += `
                        <div class="modal-doc-item">
                            <label>TA Document</label>
                            <a href="${BASE_URL}uploads/staff/${s.ta_image}" target="_blank" title="Click to view full image">
                                <img src="${BASE_URL}uploads/staff/${s.ta_image}" class="modal-doc-img" alt="TA Document">
                            </a>
                        </div>`;

                    if (s.ppo_image) modalHTML += `
                        <div class="modal-doc-item">
                            <label>PPO Document</label>
                            <a href="${BASE_URL}uploads/staff/${s.ppo_image}" target="_blank" title="Click to view full image">
                                <img src="${BASE_URL}uploads/staff/${s.ppo_image}" class="modal-doc-img" alt="PPO Document">
                            </a>
                        </div>`;

                    modalHTML += `</div>`;
                    
                    // Note: No action buttons are added here, as this is for viewing only.
                    
                    modalBody.innerHTML = modalHTML;
                } else {
                    modalBody.innerHTML = `<p class="error-text">Could not load staff details.</p>`;
                }
            });
    });

    // Event listener to close the modal
    modal.addEventListener('click', function(event){
        if (event.target.matches('.modal-overlay') || event.target.matches('.modal-close-btn')){
            modal.classList.add('hidden');
        }
    });
  }
});
