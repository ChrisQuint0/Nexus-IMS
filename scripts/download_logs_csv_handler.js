// Initialize variables with correct scope
let userType = "";
let departmentId = "";
let selectedBoxes;

// Column definitions - customize these based on your database schema
const columns = [
  { id: "log_id", display: "Log ID" },
  { id: "staff", display: "Staff" },
  { id: "dist_id", display: "Distribution ID" },
  { id: "borrower_type", display: "Borrower Type" },
  { id: "borrower_name", display: "Borrower Name" },
  { id: "serial_no", display: "Serial Number" },
  { id: "item_name", display: "Item Name" },
  { id: "received_date", display: "Received Date" },
  { id: "return_date", display: "Return Date" },
  { id: "department", display: "Department" },
];

document.addEventListener("DOMContentLoaded", function () {
  const dropdown = document.getElementById("multiselect-dropdown");
  const header = document.getElementById("multiselect-header");
  const selectedCountElem = document.getElementById("selected-count");
  const dropdownIcon = document.querySelector(".dropdown-icon");
  const selectAllBtn = document.getElementById("select-all");
  const deselectAllBtn = document.getElementById("deselect-all");

  // Check if elements exist before manipulating them
  if (!dropdown || !header || !selectedCountElem) {
    console.error("Required DOM elements are missing");
    return;
  }

  // Populate dropdown with options
  columns.forEach((column) => {
    const option = document.createElement("div");
    option.className = "option-item";
    option.innerHTML = `
      <label>
          <input type="checkbox" value="${column.id}" class="column-checkbox"> 
          ${column.display}
      </label>
    `;
    dropdown.appendChild(option);
  });

  // Toggle dropdown
  header.addEventListener("click", function () {
    dropdown.classList.toggle("show");
    dropdownIcon.classList.toggle("open");
  });

  // Update selected count and export button state
  function updateSelectedCount() {
    selectedBoxes = document.querySelectorAll(".column-checkbox:checked");
    const count = selectedBoxes.length;
    selectedCountElem.textContent = count;
  }

  // Add event listeners to checkboxes
  document.querySelectorAll(".column-checkbox").forEach((checkbox) => {
    checkbox.addEventListener("change", updateSelectedCount);
  });

  // Select all functionality
  selectAllBtn.addEventListener("click", function () {
    document.querySelectorAll(".column-checkbox").forEach((checkbox) => {
      checkbox.checked = true;
    });
    updateSelectedCount();
  });

  // Deselect all functionality
  deselectAllBtn.addEventListener("click", function () {
    document.querySelectorAll(".column-checkbox").forEach((checkbox) => {
      checkbox.checked = false;
    });
    updateSelectedCount();
  });

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

  const pdf_icon = document.getElementById("csv_btn");
  const downloadCsvOverlay = document.getElementById("downloadCsvOverlay");
  const closeDownloadCSVOverlay = document.getElementById("closePopup");
  const adminDeptChoice = document.getElementById("adminDeptChoice");
  const department = document.getElementById("department");

  pdf_icon.addEventListener("click", function () {
    adminDeptChoice.style.display = "flex";
    //populate
    fetch("../php/get_departments.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          data.departments.forEach((dept) => {
            const option = document.createElement("option");
            option.value = dept.department_id;
            option.textContent = dept.department_name;
            department.appendChild(option);
          });
        } else {
          console.error("Error fetching departments:", data.message);
        }
      })
      .catch((error) => {
        console.error("Error fetching departments:", error);
      });

    downloadCsvOverlay.style.display = "flex";
  });

  closeDownloadCSVOverlay.addEventListener("click", function () {
    downloadCsvOverlay.style.display = "none";
  });

  downloadCsvOverlay.addEventListener("click", function (e) {
    // Close only if the click target is the overlay itself, not the popup
    if (e.target === downloadCsvOverlay) {
      downloadCsvOverlay.style.display = "none";
    }
  });
});

function downloadLogsCSV(type) {
  const departmentSelect = document.getElementById("department");
  const startDate = document.getElementById("start_date");
  const endDate = document.getElementById("end_date");
  let onlyDepartment = departmentSelect.value;
  let start_date = startDate.value;
  let end_date = endDate.value;

  // Get selected columns
  const selectedColumns = [];
  document.querySelectorAll(".column-checkbox:checked").forEach((checkbox) => {
    selectedColumns.push(checkbox.value);
  });

  const formData = new FormData();
  formData.append("onlyDepartment", onlyDepartment);
  formData.append("start_date", start_date);
  formData.append("end_date", end_date);
  // Add selected columns as JSON string
  formData.append("columns", JSON.stringify(selectedColumns));

  let endpoint = "";
  let filename = "";

  switch (type) {
    case "claims":
      endpoint = "../php/generate_claimed_logs_csv.php";
      filename = `claimed_logs_${new Date().toISOString().split("T")[0]}.csv`;
      break;
    case "returns":
      endpoint = "../php/generate_returns_logs_csv.php";
      filename = `returns_logs_${new Date().toISOString().split("T")[0]}.csv`;
      break;
    case "repairs":
      endpoint = "../php/generate_repair_logs_csv.php";
      filename = `repair_logs_${new Date().toISOString().split("T")[0]}.csv`;
      break;
    default:
      console.error("downloadLogsCSV FAILED: Invalid type");
      return;
  }

  // Send AJAX request
  fetch(endpoint, {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      if (!response.ok) {
        if (
          response.headers.get("content-type")?.includes("application/json")
        ) {
          return response.json().then((data) => {
            throw new Error(data.error || "Failed to download CSV");
          });
        }
        throw new Error("Failed to download CSV");
      }
      return response.blob();
    })
    .then((blob) => {
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
    })
    .catch((error) => {
      console.error(`Error downloading ${type} CSV:`, error);
      alert("Error downloading CSV: " + error.message);
    });
}
