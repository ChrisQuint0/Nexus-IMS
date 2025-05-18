// Audit log download handler
let userType = "";
let departmentId = "";

// Column definitions for audit logs
const columns = [
  { id: "log_id", display: "Log ID" },
  { id: "user_id", display: "User ID" },
  { id: "username", display: "Username" },
  { id: "email", display: "Email" },
  { id: "department", display: "Department" },
  { id: "user_type", display: "User Type" },
  { id: "action", display: "Action" },
  { id: "timestamp", display: "Timestamp" },
];

document.addEventListener("DOMContentLoaded", function () {
  // Initialize user type and department ID
  userType = "";
  departmentId = "";

  const actionFilter = document.getElementById("action-filter");

  // Function to fetch activity logs with optional filter
  function fetchActivityLogs(filter = "") {
    let url = "../php/get_activity_logs.php";
    if (filter) {
      url += `?action_filter=${filter}`;
    }

    fetch(url)
      .then((response) => response.json())
      .then((data) => {
        if (!data.success) {
          if (data.message === "Session invalid") {
            window.location.href = "../pages/login.html";
            return;
          }
          throw new Error(data.message);
        }

        const tableBody = document.querySelector("#activity-logs-table tbody");
        tableBody.innerHTML = ""; // Clear existing rows

        if (data.logs && data.logs.length > 0) {
          data.logs.forEach((log) => {
            const row = tableBody.insertRow();
            const userCell = row.insertCell();
            const actionCell = row.insertCell();
            const timestampCell = row.insertCell();

            userCell.textContent = log.username || "N/A";
            actionCell.textContent = log.action || "N/A";
            timestampCell.textContent = new Date(
              log.timestamp
            ).toLocaleString();
          });
        } else {
          const row = tableBody.insertRow();
          const noDataCell = row.insertCell();
          noDataCell.colSpan = 3;
          noDataCell.textContent = data.message || "No activity logs found.";
          noDataCell.style.textAlign = "center";
        }
      })
      .catch((error) => {
        console.error("Error fetching activity logs:", error);
        const tableBody = document.querySelector("#activity-logs-table tbody");
        const row = tableBody.insertRow();
        const errorCell = row.insertCell();
        errorCell.colSpan = 3;
        errorCell.textContent = "Failed to load activity logs.";
        errorCell.style.textAlign = "center";
      });
  }

  // Event listener for the action filter dropdown
  if (actionFilter) {
    actionFilter.addEventListener("change", function () {
      const selectedAction = this.value;
      fetchActivityLogs(selectedAction);
    });
  }

  // Initial load of activity logs (without any filter)
  fetchActivityLogs();

  // Set up download buttons
  if (document.getElementById("csv_btn")) {
    setupAuditDownloadButton("csv_btn", "downloadOverlay-csv");
  }
  if (document.getElementById("pdf_btn")) {
    setupAuditDownloadButton("pdf_btn", "downloadOverlay-pdf");
  }

  // Get user info and check permissions
  fetch("../php/get_user_info.php", {
    credentials: "include",
  })
    .then((response) => response.json())
    .then((data) => {
      if (!data.success) {
        window.location.href = "../pages/login.html";
        return;
      }
      userType = data.userType;
      departmentId = data.department_id;

      // Only proceed if the user is an admin
      if (userType !== "admin") {
        hideAuditLogControls();
      }

      console.log("User type: " + userType);
    })
    .catch((error) => {
      console.error("Error checking session:", error);
      window.location.href = "../pages/login.html";
    });
});

function hideAuditLogControls() {
  // Hide audit log download buttons if user is not admin
  const downloadButtons = document.querySelectorAll(".downlaod-icons");
  downloadButtons.forEach((button) => {
    button.style.display = "none";
  });
}

function setupAuditDownloadButton(buttonId, overlayId) {
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
  const departmentSelect = overlay.querySelector(".department-select");
  const multiselectHeader = overlay.querySelector(".multiselect-header");
  const multiselectDropdown = overlay.querySelector(".multiselect-dropdown");
  const dropdownIcon = overlay.querySelector(".dropdown-icon");
  const selectedCountElem = overlay.querySelector(".selected-count-value");
  const selectAllBtn = overlay.querySelector(".select-all");
  const deselectAllBtn = overlay.querySelector(".deselect-all");

  // Populate dropdown options for columns
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
    // Populate departments dropdown for admins
    if (departmentSelect) {
      // Check if we need to show department selection
      const adminDeptChoice = overlay.querySelector(".adminDeptChoice");

      // Only show departments dropdown for admins
      if (userType === "admin") {
        if (adminDeptChoice) {
          adminDeptChoice.style.display = "block";
        }

        while (departmentSelect.firstChild) {
          departmentSelect.removeChild(departmentSelect.firstChild);
        }

        // Add "All Departments" option
        const alldeptoption = document.createElement("option");
        alldeptoption.value = 0;
        alldeptoption.textContent = "All Departments";
        departmentSelect.appendChild(alldeptoption);

        // Fetch and populate department options
        fetch("../php/get_departments.php")
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
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
      } else {
        // Hide department selection for non-admins
        if (adminDeptChoice) {
          adminDeptChoice.style.display = "none";
        }
      }
    }

    overlay.style.display = "flex";
  });

  // Toggle column selection dropdown
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

function downloadAuditLogFile(fileFormat) {
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

  // Get form values
  const departmentSelect = overlay.querySelector(".department-select");
  const onlyDepartment = departmentSelect ? departmentSelect.value : "0";

  // Check if user is admin
  if (userType !== "admin") {
    alert("Error: Only administrators can download audit logs.");
    return;
  }

  // Get selected columns
  const selectedColumns = [];
  overlay.querySelectorAll(".column-checkbox:checked").forEach((checkbox) => {
    selectedColumns.push(checkbox.value);
  });

  // If no columns are selected, use defaults
  if (selectedColumns.length === 0) {
    selectedColumns.push("username", "action", "timestamp");
  }

  // Create form data for request
  const formData = new FormData();
  formData.append("onlyDepartment", onlyDepartment);
  formData.append("columns", JSON.stringify(selectedColumns));

  let endpoint = "";
  const dateString = new Date().toISOString().split("T")[0];
  const filename = `audit_logs_${dateString}.${fileFormat}`;

  if (fileFormat === "csv") {
    endpoint = "../php/generate_activity_logs_csv.php";
  } else {
    endpoint = "../php/generate_activity_logs_pdf.php";
  }

  // Add a debug log
  console.log(
    `Attempting to download audit logs from: ${endpoint} with format: ${fileFormat}`
  );
  console.log("Parameters:", {
    onlyDepartment,
    columns: selectedColumns,
  });

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
        `Error downloading audit logs ${fileFormat.toUpperCase()}:`,
        error
      );
      alert(`Error downloading ${fileFormat.toUpperCase()}: ${error.message}`);
    });
}

// Export wrapper functions for HTML onclick calls
function downloadInventoryCSV() {
  downloadAuditLogFile("csv");
}

function downloadInventoryPDF() {
  downloadAuditLogFile("pdf");
}
