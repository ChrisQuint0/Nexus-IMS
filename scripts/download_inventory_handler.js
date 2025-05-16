// Unified download handler for both CSV and PDF
let userType = "";
let departmentId = "";

// Column definitions - customize these based on your database schema
const columns = [
  { id: "dist_id", display: "Distribution ID" },
  { id: "borrower_type", display: "Borrower Type" },
  { id: "item_id", display: "Item ID" },
  { id: "serial_no", display: "Serial Number" },
  { id: "item_name", display: "Item Name" },
  { id: "received_date", display: "Received Date" },
  { id: "returned_date", display: "Returned Date" },
  { id: "borrower_name", display: "Borrower Name" },
  { id: "section", display: "Section" },
  { id: "MR", display: "MR" },
  { id: "department", display: "Department" },
];

document.addEventListener("DOMContentLoaded", function () {
  // Fetch user info
  fetch("../php/get_user_info.php", {
    credentials: "include",
  })
    .then((response) => response.json())
    .then((data) => {
      if (!data.success) {
        alert(data.message || "Session invalid");
        return;
      }
      userType = data.userType;
      departmentId = data.department_id;
    })
    .catch((error) => {
      console.error("Error checking session:", error);
    });

  // Set up download buttons - Add null check before setting up
  if (document.getElementById("csv_btn")) {
    setupDownloadButton("csv_btn", "downloadOverlay-csv");
  }
  if (document.getElementById("pdf_btn")) {
    setupDownloadButton("pdf_btn", "downloadOverlay-pdf");
  }
});

function setupDownloadButton(buttonId, overlayId) {
  const button = document.getElementById(buttonId);
  if (!button) {
    console.error(`Button with ID '${buttonId}' not found in the document`);
    return;
  }

  const overlay = document.getElementById(overlayId);
  if (!overlay) {
    console.error(`Overlay with ID '${overlayId}' not found in the document`);
    return;
  }

  const closeButton = overlay.querySelector(".close");
  const adminDeptChoice = overlay.querySelector(".adminDeptChoice");
  const departmentSelect = overlay.querySelector(".department-select");
  const multiselectHeader = overlay.querySelector(".multiselect-header");
  const multiselectDropdown = overlay.querySelector(".multiselect-dropdown");
  const dropdownIcon = overlay.querySelector(".dropdown-icon");
  const selectedCountElem = overlay.querySelector(".selected-count-value");
  const selectAllBtn = overlay.querySelector(".select-all");
  const deselectAllBtn = overlay.querySelector(".deselect-all");

  // Populate dropdown options
  if (
    multiselectDropdown &&
    !multiselectDropdown.querySelector(".option-item")
  ) {
    columns.forEach((column) => {
      const option = document.createElement("div");
      option.className = "option-item";
      option.innerHTML = `
        <label>
            <input type="checkbox" value="${column.id}" class="column-checkbox"> 
            ${column.display}
        </label>
      `;
      multiselectDropdown.appendChild(option);
    });
  }

  // Set up event listeners
  button.addEventListener("click", function () {
    if (userType === "admin" && adminDeptChoice) {
      adminDeptChoice.style.display = "flex";

      // Clear previous options
      if (departmentSelect) {
        while (departmentSelect.firstChild) {
          departmentSelect.removeChild(departmentSelect.firstChild);
        }

        // Populate departments
        fetch("../php/get_departments.php")
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              const alldeptoption = document.createElement("option");
              alldeptoption.value = 0;
              alldeptoption.textContent = "All Departments";
              departmentSelect.appendChild(alldeptoption);
              data.departments.forEach((dept) => {
                const option = document.createElement("option");
                option.value = dept.department_id;
                option.textContent = dept.department_name;
                departmentSelect.appendChild(option);
              });
            } else {
              console.error("Error fetching departments:", data.message);
            }
          })
          .catch((error) => {
            console.error("Error fetching departments:", error);
          });
      }
    }

    overlay.style.display = "flex";
  });

  // Toggle dropdown
  if (multiselectHeader) {
    multiselectHeader.addEventListener("click", function () {
      if (multiselectDropdown) multiselectDropdown.classList.toggle("show");
      if (dropdownIcon) dropdownIcon.classList.toggle("open");
    });
  }

  // Update selected count
  function updateSelectedCount() {
    if (!selectedCountElem) return;
    const selectedBoxes = overlay.querySelectorAll(".column-checkbox:checked");
    selectedCountElem.textContent = selectedBoxes.length;
  }

  // Add event listeners to checkboxes
  overlay.querySelectorAll(".column-checkbox").forEach((checkbox) => {
    checkbox.addEventListener("change", updateSelectedCount);
  });

  // Select all functionality
  if (selectAllBtn) {
    selectAllBtn.addEventListener("click", function () {
      overlay.querySelectorAll(".column-checkbox").forEach((checkbox) => {
        checkbox.checked = true;
      });
      updateSelectedCount();
    });
  }

  // Deselect all functionality
  if (deselectAllBtn) {
    deselectAllBtn.addEventListener("click", function () {
      overlay.querySelectorAll(".column-checkbox").forEach((checkbox) => {
        checkbox.checked = false;
      });
      updateSelectedCount();
    });
  }

  // Close overlay
  if (closeButton) {
    closeButton.addEventListener("click", function () {
      overlay.style.display = "none";
    });
  }

  // Close on outside click
  overlay.addEventListener("click", function (e) {
    if (e.target === overlay) {
      overlay.style.display = "none";
    }
  });
}

function downloadInventoryFile(type, fileFormat) {
  const overlayId =
    fileFormat === "csv" ? "downloadOverlay-csv" : "downloadOverlay-pdf";
  const overlay = document.getElementById(overlayId);
  if (!overlay) {
    console.error(`Overlay with ID '${overlayId}' not found in the document`);
    alert(
      "Error: Could not find the download overlay. Please refresh the page."
    );
    return;
  }

  const departmentSelect = overlay.querySelector(".department-select");
  const onlyDepartment = departmentSelect ? departmentSelect.value : "";

  // Check if department is available
  if (!departmentId && userType !== "admin") {
    alert(
      "Error: Department information not available. Please refresh the page and try again."
    );
    return;
  }

  // Get selected columns
  const selectedColumns = [];
  overlay.querySelectorAll(".column-checkbox:checked").forEach((checkbox) => {
    selectedColumns.push(checkbox.value);
  });

  const formData = new FormData();
  formData.append("userType", userType);
  formData.append("department", departmentId);
  formData.append("onlyDepartment", onlyDepartment);
  formData.append("fileFormat", fileFormat);
  formData.append("columns", JSON.stringify(selectedColumns));

  let endpoint = "";
  let filename = "";
  const dateString = new Date().toISOString().split("T")[0];

  switch (type) {
    case "claims":
      if (fileFormat === "csv") {
        endpoint = "../php/generate_claimed_csv.php";
      } else {
        endpoint = "../php/generate_claimed_pdf.php";
      }
      filename = `claims_inventory_${dateString}.${fileFormat}`;
      break;
    case "available":
      if (fileFormat === "csv") {
        endpoint = "../php/generate_available_csv.php";
      } else {
        endpoint = "../php/generate_available_pdf.php";
      }
      filename = `available_inventory_${dateString}.${fileFormat}`;
      break;
    case "brandnew":
      if (fileFormat === "csv") {
        endpoint = "../php/generate_brandnew_csv.php";
      } else {
        endpoint = "../php/generate_brandnew_pdf.php";
      }
      filename = `brandnew_inventory_${dateString}.${fileFormat}`;
      break;
    case "repair":
      if (fileFormat === "csv") {
        endpoint = "../php/generate_repair_csv.php";
      } else {
        endpoint = "../php/generate_repair_pdf.php";
      }
      filename = `repair_inventory_${dateString}.${fileFormat}`;
      break;
    default:
      console.error(`Download inventory ${fileFormat} FAILED: Invalid type`);
      alert(`Invalid inventory type: ${type}`);
      return;
  }

  // Add a debug log
  console.log(
    `Attempting to download from: ${endpoint} with format: ${fileFormat}`
  );

  // Fetch the file
  fetch(endpoint, {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      if (!response.ok) {
        // Try to get more detailed error from response
        return response.text().then((text) => {
          let errorMessage = `Failed to download ${fileFormat.toUpperCase()}`;
          try {
            // Try to parse as JSON
            const jsonData = JSON.parse(text);
            if (jsonData.error) {
              errorMessage = jsonData.error;
            }
          } catch (e) {
            // Not JSON, use text if it exists
            if (text) {
              errorMessage = text;
            }
          }
          throw new Error(errorMessage);
        });
      }
      return response.blob();
    })
    .then((blob) => {
      // Check if we got an empty blob
      if (blob.size === 0) {
        throw new Error(
          `Server returned an empty ${fileFormat.toUpperCase()} file`
        );
      }

      // Create a temporary link to download the file
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();

      // Cleanup
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);

      // Close the overlay
      overlay.style.display = "none";
    })
    .catch((error) => {
      console.error(
        `Error downloading ${type} ${fileFormat.toUpperCase()}:`,
        error
      );
      alert(`Error downloading ${fileFormat.toUpperCase()}: ${error.message}`);
    });
}

// Export wrapper functions for HTML onclick calls
function downloadInventoryCSV(type) {
  downloadInventoryFile(type, "csv");
}

function downloadInventoryPDF(type) {
  downloadInventoryFile(type, "pdf");
}
