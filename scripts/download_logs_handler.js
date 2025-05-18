// ../scripts/download_logs_handler.js

document.addEventListener("DOMContentLoaded", function () {
  const closeDownloadBtn = document.getElementById("closeDownloadBtn");
  const downloadOverlayCsv = document.getElementById("downloadOverlay-csv");
  const csvBtn = document.getElementById("csv_btn");
  const multiselectDropdown = document.querySelector(
    "#downloadOverlay-csv .multiselect-dropdown"
  );
  const multiselectHeader = document.querySelector(
    "#downloadOverlay-csv .multiselect-header"
  );
  const selectedCount = document.querySelector(
    "#downloadOverlay-csv .selected-count-value"
  );
  const selectAllBtn = document.querySelector(
    "#downloadOverlay-csv .select-all"
  );
  const deselectAllBtn = document.querySelector(
    "#downloadOverlay-csv .deselect-all"
  );
  const departmentSelect = document.getElementById("department-select");

  let allColumnHeaders = [];
  let selectedColumns = [];
  let isAdmin = false; // Will be set based on user role
  let userDepartmentId = null; // Will be set based on user's department

  // Function to fetch user role and department (you'll need a PHP endpoint for this)
  function fetchUserRoleAndDepartment() {
    fetch("../php/get_user_info.php") // Replace with your actual endpoint
      .then((response) => response.json())
      .then((data) => {
        isAdmin = data.is_admin;
        userDepartmentId = data.department_id;
        populateDepartmentDropdown(data.departments); // Assuming the endpoint returns departments for admin
        if (!isAdmin && departmentSelect) {
          // If not admin, hide the department dropdown
          departmentSelect.parentNode.style.display = "none";
        }
      })
      .catch((error) => {
        console.error("Error fetching user info:", error);
      });
  }

  // Function to populate the column selection dropdown
  function populateColumnDropdown() {
    const returnTable = document.getElementById("tblReturnLogs");
    if (returnTable && returnTable.rows.length > 0) {
      const headerRow = returnTable.rows[0];
      for (let i = 0; i < headerRow.cells.length; i++) {
        const columnHeader = headerRow.cells[i].textContent.trim();
        // Exclude the 'Action' column from the download options
        if (columnHeader !== "Action") {
          allColumnHeaders.push(columnHeader);
          const checkbox = document.createElement("input");
          checkbox.type = "checkbox";
          checkbox.value = columnHeader;
          checkbox.id = `col-${columnHeader.replace(/\s+/g, "-")}`; // Create unique ID
          const label = document.createElement("label");
          label.textContent = columnHeader;
          label.setAttribute("for", checkbox.id);
          const listItem = document.createElement("div");
          listItem.appendChild(checkbox);
          listItem.appendChild(label);
          multiselectDropdown.appendChild(listItem);

          checkbox.addEventListener("change", function () {
            if (this.checked) {
              selectedColumns.push(this.value);
            } else {
              selectedColumns = selectedColumns.filter(
                (col) => col !== this.value
              );
            }
            updateSelectedCount();
          });
        }
      }
    }
    updateSelectedCount();
  }

  // Function to populate the department dropdown (for admins)
  function populateDepartmentDropdown(departments) {
    if (isAdmin && departmentSelect && departments && departments.length > 0) {
      // Clear existing options (except 'All Departments')
      while (departmentSelect.options.length > 1) {
        departmentSelect.remove(1);
      }
      departments.forEach((dept) => {
        const option = document.createElement("option");
        option.value = dept.department_id;
        option.textContent = dept.department_name;
        departmentSelect.appendChild(option);
      });
    }
  }

  // Function to update the selected column count in the header
  function updateSelectedCount() {
    selectedCount.textContent = selectedColumns.length;
  }

  // Event listener for closing the CSV download overlay
  if (closeDownloadBtn && downloadOverlayCsv) {
    closeDownloadBtn.addEventListener("click", function () {
      downloadOverlayCsv.style.display = "none";
      // Reset selected columns when closing
      selectedColumns = [];
      updateSelectedCount();
      // Uncheck all checkboxes
      const checkboxes = multiselectDropdown.querySelectorAll(
        'input[type="checkbox"]'
      );
      checkboxes.forEach((checkbox) => {
        checkbox.checked = false;
      });
      if (departmentSelect) {
        departmentSelect.value = "all"; // Reset department selection
      }
    });
  }

  // Event listener for clicking the CSV download button (icon)
  if (csvBtn && downloadOverlayCsv) {
    csvBtn.addEventListener("click", function () {
      downloadOverlayCsv.style.display = "flex";
    });
  }

  // Event listener for the multiselect header to toggle the dropdown
  if (multiselectHeader && multiselectDropdown) {
    multiselectHeader.addEventListener("click", function () {
      multiselectDropdown.style.display =
        multiselectDropdown.style.display === "block" ? "none" : "block";
    });
  }

  // Event listener for "Select All" button
  if (selectAllBtn && multiselectDropdown) {
    selectAllBtn.addEventListener("click", function () {
      const checkboxes = multiselectDropdown.querySelectorAll(
        'input[type="checkbox"]'
      );
      checkboxes.forEach((checkbox) => {
        checkbox.checked = true;
        if (!selectedColumns.includes(checkbox.value)) {
          selectedColumns.push(checkbox.value);
        }
      });
      updateSelectedCount();
    });
  }

  // Event listener for "Deselect All" button
  if (deselectAllBtn && multiselectDropdown) {
    deselectAllBtn.addEventListener("click", function () {
      const checkboxes = multiselectDropdown.querySelectorAll(
        'input[type="checkbox"]'
      );
      checkboxes.forEach((checkbox) => {
        checkbox.checked = false;
      });
      selectedColumns = [];
      updateSelectedCount();
    });
  }

  // Function to handle the CSV download
  window.downloadLogsCSV = function (logType) {
    if (selectedColumns.length === 0) {
      alert("Please select at least one column to download.");
      return;
    }

    let department = "all";
    if (isAdmin && departmentSelect) {
      department = departmentSelect.value;
    } else if (!isAdmin && userDepartmentId) {
      department = userDepartmentId;
    }

    fetch("../php/generate_returns_logs_csv.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `columns=${JSON.stringify(
        selectedColumns
      )}&department=${department}`,
    })
      .then((response) => response.blob())
      .then((blob) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = `return_logs_${new Date().toISOString().slice(0, 10)}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        downloadOverlayCsv.style.display = "none"; // Close the overlay after download
      })
      .catch((error) => {
        console.error("Error downloading CSV:", error);
        alert("An error occurred while generating the CSV file.");
      });
  };

  // Initialize: Fetch user info and populate column dropdown
  fetchUserRoleAndDepartment();
  populateColumnDropdown();
});
