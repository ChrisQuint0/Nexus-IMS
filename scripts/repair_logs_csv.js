// ../scripts/repair_logs.js

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
  const downloadButton = downloadOverlayCsv.querySelector(".download-button");

  const allColumns = [
    { name: "Box Number", value: 1 },
    { name: "Accountable", value: 2 },
    { name: "Department", value: 3 },
    { name: "Receiver", value: 4 },
    { name: "Section", value: 5 },
    { name: "Item Name", value: 6 },
    { name: "Serial No.", value: 7 },
    { name: "Repair Date", value: 8 },
    { name: "Reason", value: 9 },
    { name: "Staff", value: 10 },
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
    const table = document.getElementById("tblReturnLogs"); // Assuming the table ID is still tblReturnLogs
    const tbody = table.querySelector("tbody");
    const rows = tbody.querySelectorAll("tr");

    rows.forEach((row) => {
      const departmentCell = row.querySelector("td:nth-child(3)"); // Assuming Department is the 3rd column
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
    const table = document.getElementById("tblReturnLogs"); // Assuming the table ID is still tblReturnLogs
    const tbody = table.querySelector("tbody");
    const rows = Array.from(tbody.querySelectorAll("tr"));

    return rows.filter((row) => {
      if (selectedDepartment === "all") {
        return true;
      }
      const departmentCell = row.querySelector("td:nth-child(3)"); // Assuming Department is the 3rd column
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

      selectedColumns.forEach((columnName) => {
        let columnIndex;
        switch (columnName) {
          case "Box Number":
            columnIndex = 0;
            break;
          case "Accountable":
            columnIndex = 1;
            break;
          case "Department":
            columnIndex = 2;
            break;
          case "Receiver":
            columnIndex = 3;
            break;
          case "Section":
            columnIndex = 4;
            break;
          case "Item Name":
            columnIndex = 5;
            break;
          case "Serial No.":
            columnIndex = 6;
            break;
          case "Repair Date":
            columnIndex = 7;
            break;
          case "Reason":
            columnIndex = 8;
            break;
          case "Staff":
            columnIndex = 9;
            break;
          default:
            columnIndex = -1;
        }
        if (columnIndex >= 0 && columnIndex < cells.length) {
          rowData.push(`"${cells[columnIndex].textContent.trim()}"`);
        } else {
          rowData.push("");
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
    const filename = "repair_logs.csv";
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
      if (!columnSelectorContainer.hasChildNodes()) {
        populateColumnSelector();
      }
      if (departmentSelect.options.length <= 1) {
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

  if (downloadButton) {
    downloadButton.addEventListener("click", function () {
      const csvData = prepareCSVData();
      downloadCSVFile(csvData);
    });
  }
});
