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

    const docMapping = {
        Police: { label: 'Police Verification', name: 'police' },
        Medical: { label: 'Medical Fitness', name: 'medical' },
        TA: { label: 'TA Document', name: 'ta' },
        PPO: { label: 'PPO Document', name: 'ppo' }
    };

    for (const [key, doc] of Object.entries(docMapping)) {
        if (docReqs[key] === 'Y') {
            const hasExpiry = (key === 'Police' || key === 'Medical' || key === 'TA');
            const hasIssue = (key === 'Police' || key === 'Medical');

            const docItem = document.createElement('div');
            docItem.className = 'document-upload-item';

            let html = `
                <label>${doc.label} Image</label>
                <input type="file" name="${doc.name}_image" accept="image/*" required>
            `;

            if (hasIssue) {
                html += `
                    <div class="input-group">
                        <label>${doc.label} Issue Date</label>
                        <input type="date" name="${doc.name}_issue_date" required>
                    </div>
                `;
            }

            if (hasExpiry) {
                html += `
                    <div class="input-group">
                        <label>${doc.label} Expiry Date</label>
                        <input type="date" name="${doc.name}_expiry_date" ${hasIssue ? 'readonly' : 'required'}>
                    </div>
                `;
            }

            docItem.innerHTML = html;
            staffDocsContainer.appendChild(docItem);

            // Add event listeners for issue date if applicable
            if (hasIssue) {
                const issueDate = docItem.querySelector(`[name="${doc.name}_issue_date"]`);
                const expiryDate = docItem.querySelector(`[name="${doc.name}_expiry_date"]`);
                
                issueDate.addEventListener('change', function() {
                    if (this.value) {
                        const date = new Date(this.value);
                        date.setFullYear(date.getFullYear() + 1);
                        expiryDate.value = date.toISOString().split('T')[0];
                    } else {
                        expiryDate.value = '';
                    }
                });
            }
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
                    },
                    "dataSrc": function(json) {
                        // Update status counts
                        if (json.statusCounts) {
                            $('#pending_count').text(json.statusCounts.pending_count);
                            $('#approved_count').text(json.statusCounts.approved_count);
                            $('#terminated_count').text(json.statusCounts.terminated_count);
                        }
                        return json.data;
                    }
                },
                "columns": [
                    { 
                        "data": "id",
                        /* "render": function(data, type, row) {
                            if (type === 'display') {
                                return `<a href="${BASE_URL}staff_details.php?id=${data}" target="_blank" class="staff-id-link">${data}</a>`;
                            }
                            return data;
                        } */
                    },
                    { "data": "name" },
                    { "data": "designation" },
                    { "data": "contact" },
                    { "data": "adhar_card_number" },
                    { "data": "status" }
                ],
                "drawCallback": function() {
                    // Prevent default link behavior to keep modals open
                    $('.staff-id-link').on('click', function(e) {
                        e.stopPropagation();
                    });
                }
            });
        }
    }

    // Add status count elements to the DOM
    $(document).ready(function() {
        // Add status count display above the table
        const statusCountHtml = `
            <div class="status-counts" style="margin-bottom: 15px;">
                <span class="status-count">Pending: <span id="pending_count">0</span></span>
                <span class="status-count">Approved: <span id="approved_count">0</span></span>
                <span class="status-count">Terminated: <span id="terminated_count">0</span></span>
            </div>
        `;
        staffTable.before(statusCountHtml);
    });

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
  }
});
