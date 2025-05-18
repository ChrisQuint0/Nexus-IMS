// ../scripts/unrecoverables.js

document.addEventListener("DOMContentLoaded", function () {
  const downloadOverlayCsv = document.getElementById("downloadOverlay-csv");
  const csvButton = document.getElementById("generate-csv"); // Changed ID to generate-csv
  const closeDownloadBtn = document.getElementById("closeDownloadBtn");
  const columnSelectorContainer = downloadOverlayCsv.querySelector(
    ".multiselect-dropdown"
  );
  const selectAllBtn = downloadOverlayCsv.querySelector(".select-all");
  const deselectAllBtn = downloadOverlayCsv.querySelector(".deselect-all");
  const departmentSelectContainer =
    downloadOverlayCsv.querySelector(".adminDeptChoice"); // Container for department select
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
  const unrecoverablesTable = document.querySelector(".unrecoverables-table"); // Target the correct table

  const allColumns = [
    { name: "Status", value: 1 },
    { name: "Box Number", value: 2 },
    { name: "Accountable", value: 3 },
    { name: "Department", value: 4 },
    { name: "Item Name", value: 5 },
    { name: "Serial No.", value: 6 },
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

  // We don't need populateDepartmentFilter anymore if the dropdown is always hidden
  // function populateDepartmentFilter() {
  //     const departmentSet = new Set();
  //     const tbody = unrecoverablesTable.querySelector("tbody");
  //     const rows = tbody.querySelectorAll("tr");

  //     rows.forEach((row) => {
  //         const departmentCell = row.querySelector("td:nth-child(4)"); // Assuming Department is the 4th column
  //         if (departmentCell) {
  //             departmentSet.add(departmentCell.textContent.trim());
  //         }
  //     });

  //     departmentSet.forEach((department) => {
  //         const option = document.createElement("option");
  //         option.value = department;
  //         option.textContent = department;
  //         departmentSelect.appendChild(option);
  //     });
  // }

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
    const tbody = unrecoverablesTable.querySelector("tbody");
    const rows = Array.from(tbody.querySelectorAll("tr"));

    // If the department filter is hidden, we don't need to filter by department
    return rows;
  }

  function prepareCSVData() {
    const selectedColumns = getSelectedColumns();
    // The department filter is hidden, so we don't use departmentSelect.value
    const filteredRows = getFilteredRows();

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
          case "Status":
            columnIndex = 1;
            break;
          case "Box Number":
            columnIndex = 2;
            break;
          case "Accountable":
            columnIndex = 3;
            break;
          case "Department":
            columnIndex = 4;
            break;
          case "Item Name":
            columnIndex = 5;
            break;
          case "Serial No.":
            columnIndex = 6;
            break;
          default:
            columnIndex = -1;
        }
        if (columnIndex > 0 && columnIndex <= cells.length) {
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
    const filename = "unrecoverable_logs.csv";
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
  if (csvButton && downloadOverlayCsv) {
    csvButton.addEventListener("click", function () {
      downloadOverlayCsv.style.display = "flex";
      if (!columnSelectorContainer.hasChildNodes()) {
        populateColumnSelector();
      }
      // Always hide the department filter container
      if (departmentSelectContainer) {
        departmentSelectContainer.style.display = "none";
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

  // Hide the department filter container on page load as well, for consistency
  const departmentSelectContainerOnLoad =
    document.querySelector(".adminDeptChoice");
  if (departmentSelectContainerOnLoad) {
    departmentSelectContainerOnLoad.style.display = "none";
  }
});
