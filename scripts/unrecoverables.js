document.addEventListener("DOMContentLoaded", function () {
  const tableBody = document.querySelector(".unrecoverables-table tbody");
  const searchInput = document.getElementById("search-item");
  const generateCsvButton = document.getElementById("generate-csv");

  // Function to fetch and display unrecoverable items
  function loadUnrecoverables() {
    fetch("../php/get_unrecoverables.php")
      .then((response) => response.json())
      .then((data) => {
        populateTable(data.data, data.statuses);
      })
      .catch((error) => {
        console.error("Error fetching data:", error);
        tableBody.innerHTML =
          '<tr><td colspan="8">Error loading data.</td></tr>';
      });
  }

  // Function to populate the table
  function populateTable(items, allStatuses) {
    tableBody.innerHTML = ""; // Clear existing table rows
    items.forEach((item) => {
      const row = tableBody.insertRow();
      console.log("Current Item:", item);

      // Action Buttons
      const actionCell = row.insertCell();
      const editButton = document.createElement("button");
      editButton.textContent = "Edit";
      editButton.classList.add("edit-btn");
      editButton.style.padding = "10px 30px";
      editButton.style.backgroundColor = "#0e2f56";
      editButton.style.border = "none";
      editButton.style.borderRadius = "20px";
      editButton.style.color = "white";
      editButton.style.fontSize = "15px";
      editButton.addEventListener("click", () => enableEdit(row, allStatuses));

      const saveButton = document.createElement("button");
      saveButton.textContent = "Save";
      saveButton.classList.add("save-btn");
      saveButton.style.padding = "10px 30px";
      saveButton.style.backgroundColor = "white";
      saveButton.style.border = "1px solid #0e2f56";
      saveButton.style.borderRadius = "20px";
      saveButton.style.color = "#0e2f56";
      saveButton.style.fontSize = "15px";
      saveButton.style.display = "none"; // Initially hidden
      saveButton.addEventListener("click", () => saveEdit(row, item));

      actionCell.appendChild(editButton);
      actionCell.appendChild(saveButton);

      // Status (Initially a non-interactive display)
      const statusCell = row.insertCell();
      statusCell.textContent = item.status_name;
      statusCell.dataset.statusId = item.status_id;

      // Box Number
      const boxNoCell = row.insertCell();
      boxNoCell.textContent = item.box_no;

      // Accountable
      const accountableCell = row.insertCell();
      accountableCell.textContent = item.accountable || "N/A"; // Handle cases where accountable might be null

      // Department
      const departmentCell = row.insertCell();
      departmentCell.textContent = item.department_name || "N/A"; // Handle cases where department might be null

      // Item Name
      const itemNameCell = row.insertCell();
      itemNameCell.textContent = item.item_name;

      // Serial No.
      const serialNoCell = row.insertCell();
      serialNoCell.textContent = item.serial_no;
    });
  }

  // Function to enable editing of a row
  function enableEdit(row, allStatuses) {
    // Change Status cell to a dropdown
    const statusCell = row.cells[1];
    const currentStatusId = statusCell.dataset.statusId;
    const statusDropdown = document.createElement("select");
    statusDropdown.classList.add("modern-dropdown");
    for (const statusId in allStatuses) {
      const option = document.createElement("option");
      option.value = statusId;
      option.textContent = allStatuses[statusId];
      if (statusId == currentStatusId) {
        option.selected = true;
      }
      statusDropdown.appendChild(option);
    }
    statusCell.innerHTML = "";
    statusCell.appendChild(statusDropdown);

    // Show Save button, hide Edit
    row.querySelector(".edit-btn").style.display = "none";
    row.querySelector(".save-btn").style.display = "inline-block";
  }

  // Function to save the edited row
  function saveEdit(row, item) {
    const statusDropdown = row.cells[1].querySelector("select");
    const newStatusId = statusDropdown.value;
    const newStatusName =
      statusDropdown.options[statusDropdown.selectedIndex].textContent;

    // Prepare data for updating in the database
    const updatedData = {
      dist_id: item.dist_id,
      status_id: newStatusId,
    };

    // Send data to the server for update (PHP script to handle this)
    fetch("../php/update_unrecoverable.php", {
      // Create this PHP file
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(updatedData),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          loadUnrecoverables(); // Reload the table
        } else {
          alert("Error updating record: " + data.message);
        }
      })
      .catch((error) => {
        console.error("Error updating data:", error);
        alert("An error occurred while updating.");
      });

    // Prevent further editing of the row until the table is reloaded
    const editButton = row.querySelector(".edit-btn");
    const saveButton = row.querySelector(".save-btn");
    if (editButton) editButton.disabled = true;
    if (saveButton) {
      saveButton.disabled = true;
      saveButton.textContent = "Saving..."; // Provide visual feedback
    }
  }

  // Search functionality
  searchInput.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase();
    const rows = tableBody.querySelectorAll("tr");

    rows.forEach((row) => {
      const serialNo = row.cells[6].textContent.toLowerCase();
      if (serialNo.includes(searchTerm)) {
        row.style.display = "";
      } else {
        row.style.display = "none";
      }
    });
  });

  // Function to generate CSV
  generateCsvButton.addEventListener("click", function () {
    fetch("../php/generate_unrecoverables_csv.php")
      .then((response) => response.blob())
      .then((blob) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = "unrecoverable_items.csv";
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
      })
      .catch((error) => {
        console.error("Error generating CSV:", error);
        alert("Error generating CSV file.");
      });
  });

  // Load data on page load
  loadUnrecoverables();
});
