// Assuming this script is linked in your return_logs.html

document.addEventListener("DOMContentLoaded", function () {
  const downloadOverlayCsv = document.getElementById("downloadOverlay-csv");
  const csvBtn = document.getElementById("csv_btn");
  const closeDownloadBtn = document.getElementById("closeDownloadBtn");
  const columnSelectorContainer = downloadOverlayCsv.querySelector(
    ".multiselect-dropdown"
  );
  const selectAllBtn = downloadOverlayCsv.querySelector(".select-all");
  const deselectAllBtn = downloadOverlayCsv.querySelector(".deselect-all");
  const departmentSelect = document.getElementById("department-select");
  const selectedCountSpan = downloadOverlayCsv.querySelector(
    ".selected-count-value"
  );
  const multiselectHeader = downloadOverlayCsv.querySelector(
    ".multiselect-header"
  );
  const multiselectDropdown = downloadOverlayCsv.querySelector(
    ".multiselect-dropdown"
  );

  const allColumns = [
    { name: "Box No", value: 1 },
    { name: "Accountable", value: 2 },
    { name: "Department", value: 3 },
    { name: "Name", value: 4 },
    { name: "Section", value: 5 },
    { name: "Item Name", value: 6 },
    { name: "Serial No", value: 7 },
    { name: "Received Date", value: 8 },
    { name: "Return Date", value: 9 },
    { name: "Condition", value: 10 },
    { name: "Remarks", value: 11 },
  ];

  function populateColumnSelector() {
    allColumns.forEach((column) => {
      const checkbox = document.createElement("input");
      checkbox.type = "checkbox";
      checkbox.value = column.value;
      checkbox.id = `col-${column.value}`;
      checkbox.dataset.columnName = column.name;
      checkbox.checked = true; // Initially select all columns

      const label = document.createElement("label");
      label.textContent = column.name;
      label.setAttribute("for", `col-${column.value}`);

      const optionDiv = document.createElement("div");
      optionDiv.classList.add("multiselect-option");
      optionDiv.appendChild(checkbox);
      optionDiv.appendChild(label);
      columnSelectorContainer.appendChild(optionDiv);
    });
    updateSelectedCount();
  }

  function populateDepartmentFilter() {
    const departmentSet = new Set();
    const table = document.getElementById("tblReturnLogs");
    const tbody = table.querySelector("tbody");
    const rows = tbody.querySelectorAll("tr");

    rows.forEach((row) => {
      const departmentCell = row.querySelector("td:nth-child(4)"); // Assuming Department is the 4th column
      if (departmentCell) {
        departmentSet.add(departmentCell.textContent.trim());
      }
    });

    departmentSet.forEach((department) => {
      const option = document.createElement("option");
      option.value = department;
      option.textContent = department;
      departmentSelect.appendChild(option);
    });
  }

  function getSelectedColumns() {
    const selectedColumns = [];
    columnSelectorContainer
      .querySelectorAll('input[type="checkbox"]:checked')
      .forEach((checkbox) => {
        selectedColumns.push(checkbox.dataset.columnName);
      });
    return selectedColumns;
  }

  function getFilteredRows(selectedDepartment) {
    const table = document.getElementById("tblReturnLogs");
    const tbody = table.querySelector("tbody");
    const rows = Array.from(tbody.querySelectorAll("tr")); // Convert to array for easier filtering

    return rows.filter((row) => {
      if (selectedDepartment === "all") {
        return true;
      }
      const departmentCell = row.querySelector("td:nth-child(4)"); // Assuming Department is the 4th column
      return (
        departmentCell &&
        departmentCell.textContent.trim() === selectedDepartment
      );
    });
  }

  function prepareCSVData() {
    const selectedColumns = getSelectedColumns();
    const selectedDepartment = departmentSelect.value;
    const filteredRows = getFilteredRows(selectedDepartment);

    if (filteredRows.length === 0) {
      alert("No data to download based on the selected filters.");
      return null;
    }

    let csvString = selectedColumns.join(",") + "\r\n"; // Add header row

    filteredRows.forEach((row) => {
      const rowData = [];
      const cells = Array.from(row.querySelectorAll("td"));

      // Map the visible table columns to the selected columns
      selectedColumns.forEach((columnName) => {
        let columnIndex;
        switch (columnName) {
          case "Box No":
            columnIndex = 1;
            break;
          case "Accountable":
            columnIndex = 2;
            break;
          case "Department":
            columnIndex = 3;
            break;
          case "Name":
            columnIndex = 4;
            break;
          case "Section":
            columnIndex = 5;
            break;
          case "Item Name":
            columnIndex = 6;
            break;
          case "Serial No":
            columnIndex = 7;
            break;
          case "Received Date":
            columnIndex = 8;
            break;
          case "Return Date":
            columnIndex = 9;
            break;
          case "Condition":
            columnIndex = 10;
            break;
          case "Remarks":
            columnIndex = 11;
            break;
          default:
            columnIndex = -1;
        }
        if (columnIndex > 0 && columnIndex <= cells.length) {
          rowData.push(`"${cells[columnIndex].textContent.trim()}"`); // Enclose in quotes to handle commas
        } else {
          rowData.push(""); // Handle cases where the column might not be present
        }
      });
      csvString += rowData.join(",") + "\r\n";
    });

    return csvString;
  }

  function downloadCSVFile(csvData) {
    if (!csvData) {
      return;
    }
    const filename = "returned_logs.csv";
    const blob = new Blob([csvData], { type: "text/csv;charset=utf-8;" });

    if (navigator.msSaveBlob) {
      // IE and Edge
      navigator.msSaveBlob(blob, filename);
    } else {
      const link = document.createElement("a");
      if (link.download !== undefined) {
        // Browsers that support HTML5 download attribute
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", filename);
        link.style.visibility = "hidden";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
      }
    }
  }

  function updateSelectedCount() {
    const checkedCount = columnSelectorContainer.querySelectorAll(
      'input[type="checkbox"]:checked'
    ).length;
    selectedCountSpan.textContent = checkedCount;
  }

  // Event listeners
  if (csvBtn && downloadOverlayCsv) {
    csvBtn.addEventListener("click", function () {
      downloadOverlayCsv.style.display = "flex";
      // Ensure the column selector and department filter are populated when the overlay is shown
      if (!columnSelectorContainer.hasChildNodes()) {
        populateColumnSelector();
      }
      if (departmentSelect.options.length <= 1) {
        // Only populate if not already done (includes 'All Departments')
        populateDepartmentFilter();
      }
    });
  }

  if (closeDownloadBtn && downloadOverlayCsv) {
    closeDownloadBtn.addEventListener("click", function () {
      downloadOverlayCsv.style.display = "none";
    });
  }

  if (selectAllBtn) {
    selectAllBtn.addEventListener("click", function () {
      columnSelectorContainer
        .querySelectorAll('input[type="checkbox"]')
        .forEach((checkbox) => {
          checkbox.checked = true;
        });
      updateSelectedCount();
    });
  }

  if (deselectAllBtn) {
    deselectAllBtn.addEventListener("click", function () {
      columnSelectorContainer
        .querySelectorAll('input[type="checkbox"]')
        .forEach((checkbox) => {
          checkbox.checked = false;
        });
      updateSelectedCount();
    });
  }

  multiselectHeader.addEventListener("click", function () {
    multiselectDropdown.classList.toggle("show");
  });

  document.addEventListener("click", function (event) {
    if (
      !multiselectHeader.contains(event.target) &&
      !multiselectDropdown.contains(event.target)
    ) {
      multiselectDropdown.classList.remove("show");
    }
  });

  const downloadButton = downloadOverlayCsv.querySelector(".download-button");
  if (downloadButton) {
    downloadButton.addEventListener("click", function () {
      const csvData = prepareCSVData();
      downloadCSVFile(csvData);
    });
  }
});
