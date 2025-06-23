/**
 * VARUNA System - Add Contract Page Script
 * Handles dynamic document fields, dependent dropdowns, and Select2.
 * Current Time: Tuesday, June 17, 2025 at 12:15 PM IST
 * Location: Kalyan, Maharashtra, India
 */

document.addEventListener("DOMContentLoaded", function () {
  const contractForm = document.querySelector('form[action*="contracts/add"]');
  if (!contractForm) return; // Exit if we're not on the Add Contract page

  // --- 1. GET ALL FORM ELEMENTS ---
  const contractTypeSelect = document.getElementById("contract_type");
  const documentFieldsContainer = document.getElementById("document_fields");

  // Containers for dynamic fields
  const stationFieldsContainer = document.getElementById(
    "station_fields_container"
  );
  const trainFieldsContainer = document.getElementById(
    "train_fields_container"
  );
  const stallsFieldContainer = document
    .querySelector('input[name="stalls"]')
    .closest(".input-group");

  // Specific inputs
  const sectionSelect = document.getElementById("section_code");
  const stationSelect = document.getElementById("station_code");
  const trainSelect = $("#train_select"); // Use jQuery selector for Select2

  // --- 2. INITIALIZE LIBRARIES ---
  trainSelect.select2({
    placeholder: "Search and select one or more trains",
    allowClear: true,
  });

  // --- 3. HELPER FUNCTIONS ---

  // This function shows/hides Station/Section fields OR the Train field

  function toggleLocationFields() {
    const selectedOption =
      contractTypeSelect.options[contractTypeSelect.selectedIndex];
    const stallsInput = stallsFieldContainer.querySelector("input"); // Get the input inside the container

    if (!selectedOption || !selectedOption.value) {
      stationFieldsContainer.classList.add("hidden");
      trainFieldsContainer.classList.add("hidden");
      stallsFieldContainer.classList.remove("hidden"); // Show stalls by default
      stallsInput.required = true;
      return;
    }

    const contractTypesData = JSON.parse(contractTypeSelect.dataset.docs);
    const contractType = selectedOption.value;
    const typeInfo = contractTypesData[contractType];

    if (typeInfo) {
      if (typeInfo.TrainStation === "Station") {
        stationFieldsContainer.classList.remove("hidden");
        trainFieldsContainer.classList.add("hidden");
        stallsFieldContainer.classList.remove("hidden"); // Show stalls for stations

        sectionSelect.required = true;
        stationSelect.required = true;
        trainSelect.prop("required", false);
        stallsInput.required = true;
      } else if (typeInfo.TrainStation === "Train") {
        stationFieldsContainer.classList.add("hidden");
        trainFieldsContainer.classList.remove("hidden");
        stallsFieldContainer.classList.add("hidden"); // Hide stalls for trains

        sectionSelect.required = false;
        stationSelect.required = false;
        trainSelect.prop("required", true);
        stallsInput.required = false; // Make stalls NOT required
      } else {
        // Default behavior if 'TrainStation' is something else or not defined
        stationFieldsContainer.classList.add("hidden");
        trainFieldsContainer.classList.add("hidden");
        stallsFieldContainer.classList.remove("hidden");
        sectionSelect.required = false;
        stationSelect.required = false;
        trainSelect.prop("required", false);
        stallsInput.required = true;
      }
    }
  }

  // This function generates the required document fields
  function updateContractDocumentFields() {
    const selectedType = contractTypeSelect.value;
    documentFieldsContainer.innerHTML = "";
    if (!selectedType) return;

    const contractDocsData = JSON.parse(contractTypeSelect.dataset.docs);
    if (contractDocsData[selectedType]) {
      const requiredDocs = contractDocsData[selectedType];
      const docMapping = {
        FSSAI: { type: "file", label: "FSSAI Image", name: "fssai_image" },
        FireSafety: {
          type: "file",
          label: "Fire Safety Image",
          name: "fire_safety_image",
        },
        PestControl: {
          type: "file",
          label: "Pest Control Image",
          name: "pest_control_image",
        },
        RailNeerAvailability: {
          type: "number",
          label: "Rail Neer Stock Available",
          name: "rail_neer_stock",
        },
        WaterSafety: {
          type: "file",
          label: "Water Safety Image",
          name: "water_safety_image",
        },
      };

      for (const docKey in docMapping) {
        if (requiredDocs[docKey] === "Y") {
          const docInfo = docMapping[docKey];
          let fieldHTML = "";
          if (docInfo.type === "file") {
            fieldHTML = `<div class="input-group"><label>${docInfo.label}</label><input type="file" name="${docInfo.name}" accept="image/*" required></div>`;
          } else if (docInfo.type === "number") {
            fieldHTML = `<div class="input-group"><label>${docInfo.label}</label><input type="number" name="${docInfo.name}" placeholder="Enter stock count" required></div>`;
          }
          documentFieldsContainer.insertAdjacentHTML("beforeend", fieldHTML);
        }
      }
    }
  }

  // This function populates the station dropdown based on the selected section
  function updateStations(sectionCode, oldStationValue = "") {
    stationSelect.innerHTML = '<option value="">Loading...</option>';
    stationSelect.disabled = true;
    if (sectionCode) {
      fetch(BASE_URL + "api/get_stations.php?section=" + sectionCode)
        .then((res) => res.json())
        .then((data) => {
          stationSelect.innerHTML =
            '<option value="">-- Select Station --</option>';
          if (data.success && data.stations.length > 0) {
            data.stations.forEach((station) => {
              const opt = document.createElement("option");
              opt.value = station.Code;
              opt.textContent = station.Name;
              if (station.Code === oldStationValue) {
                opt.selected = true;
              }
              stationSelect.appendChild(opt);
            });
            stationSelect.disabled = false;
          } else {
            stationSelect.innerHTML =
              '<option value="">-- No Stations Found --</option>';
          }
        });
    } else {
      stationSelect.innerHTML =
        '<option value="">-- Select Section First --</option>';
    }
  }

  // --- 4. EVENT LISTENERS ---

  // When contract type changes, update both document and location fields
  contractTypeSelect.addEventListener("change", function () {
    updateContractDocumentFields();
    toggleLocationFields();
  });

  // When section changes, update the station list
  sectionSelect.addEventListener("change", function () {
    updateStations(this.value);
  });

  // --- 5. RUN ON PAGE LOAD ---
  // Trigger functions on page load to handle repopulation after validation errors
  if (contractTypeSelect.value) {
    updateContractDocumentFields();
    toggleLocationFields();
  }
  if (sectionSelect.value) {
    updateStations(sectionSelect.value, stationSelect.dataset.oldValue || "");
  }
});
