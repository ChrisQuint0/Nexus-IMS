document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("searchbar");
  // --- Function to populate the table with repair records ---
  function populateRepairTable(data) {
    tableBody.innerHTML = ""; // Clear existing table rows
    data.forEach((record) => {
      const row = tableBody.insertRow();
      const editCell = row.insertCell();
      const statusCell = row.insertCell();
      const saveCell = row.insertCell();
      const actionCell = row.insertCell();
      const boxNoCell = row.insertCell();
      const receiverCell = row.insertCell();
      const itemNameCell = row.insertCell();
      const serialNoCell = row.insertCell();
      const receivedDateCell = row.insertCell();
      const returnDateCell = row.insertCell();
      const accountableCell = row.insertCell();
      const reasonCell = row.insertCell();

      editCell.textContent = "Edit"; // You'll likely add edit functionality here
      statusCell.textContent = record.status || "N/A"; // Assuming your record has a 'status' field
      saveCell.textContent = "Save"; // You'll likely add save functionality here
      actionCell.textContent = "Action"; // You'll likely add action buttons here
      boxNoCell.textContent = record.box_number || "N/A";
      receiverCell.textContent = record.receiver || "N/A"; // Adjust based on your actual data field
      itemNameCell.textContent = record.item_name || "N/A";
      serialNoCell.textContent = record.serial_no || "N/A";
      receivedDateCell.textContent = record.received_date || "N/A";
      returnDateCell.textContent = record.return_date || "N/A";
      accountableCell.textContent = record.accountable || "N/A"; // Adjust based on your actual data field
      reasonCell.textContent = record.repair_reason || "N/A"; // Adjust based on your actual data field
    });
  }

  // --- Function to filter the table based on the search query ---
  function filterRepairTable(query) {
    const lowerCaseQuery = query.toLowerCase();
    const filteredRecords = allRepairRecords.filter((record) => {
      const receiver = record.receiver ? record.receiver.toLowerCase() : ""; // Adjust based on your actual data field
      const serialNo = record.serial_no ? record.serial_no.toLowerCase() : "";
      const itemName = record.item_name ? record.item_name.toLowerCase() : ""; // You might want to include other fields

      return (
        receiver.includes(lowerCaseQuery) ||
        serialNo.includes(lowerCaseQuery) ||
        itemName.includes(lowerCaseQuery)
        // Add more fields to search if needed
      );
    });
    populateRepairTable(filteredRecords);
  }

  // --- Event listener for the search input ---
  searchInput.addEventListener("input", function () {
    const query = this.value.trim();
    filterRepairTable(query);
  });

  // --- Call fetchRepairRecords to load data when the page loads ---
  fetchRepairRecords();

  const showPopupBtn = document.getElementById("scan-btn");
  const overlayContainer = document.getElementById("overlayContainer");
  const closePopupBtn = document.getElementById("closePopupBtn");
  const popup = document.querySelector(".popup");

  // Scanner Module
  let html5QrcodeScanner = null;

  function startScanner() {
    const config = {
      fps: 10,
      qrbox: { width: 250, height: 250 },
    };

    html5QrcodeScanner = new Html5QrcodeScanner(
      "reader",
      config,
      /* verbose= */ false
    );

    html5QrcodeScanner.render(function (decodedText, decodedResult) {
      console.log(`Scan result: ${decodedText}`, decodedResult);
      document.getElementById("result").innerHTML = `Scanned: ${decodedText}`;
    });
  }

  // Function to show the popup
  function showPopup() {
    overlayContainer.classList.add("active");
    startScanner();
  }

  // Function to hide the popup
  function hidePopup() {
    overlayContainer.classList.remove("active");
    stopScanner();
  }

  // Event listeners for main popup
  showPopupBtn.addEventListener("click", function (event) {
    event.preventDefault(); // Prevent form submission
    event.stopPropagation(); // Stop event bubbling
    showPopup();
  });

  closePopupBtn.addEventListener("click", function (event) {
    event.preventDefault(); // Prevent form submission
    event.stopPropagation(); // Stop event bubbling
    hidePopup();
  });

  // Close popup when clicking outside the popup content
  overlayContainer.addEventListener("click", (event) => {
    if (event.target === overlayContainer) {
      hidePopup();
    }
  });

  // Side navigation functions
  function hideShowBtn() {
    let x = document.getElementById("sidenav");
    x.className = "sidenav-hidden";
  }

  function showSideNav() {
    let x = document.getElementById("sidenav");
    x.className = "sidenav";
  }

  function gotoAddBorrower() {
    window.location.href = "/pages/add_borrower.html";
  }

  function gotoBorrowers() {
    window.location.href = "/pages/borrowers_dashboard.html";
  }
  function gotoRepairs() {
    window.location.href = "/pages/repair_dashboard.html";
  }

  function gotoReturnLogs() {
    window.location.href = "/pages/return_logs.html";
  }
  function gotoClaimLogs() {
    window.location.href = "/pages/claim_logs.html";
  }
  function gotoRepairLogs() {
    window.location.href = "/pages/repair_logs.html";
  }
  function gotoTbrDashboard() {
    window.location.href = "/pages/tbr_dashboard.html";
  }
  function gotoTbrDeletionLogs() {
    window.location.href = "/pages/tbr_deletions_logs.html";
  }

  document.addEventListener("DOMContentLoaded", function () {
    // Get all edit buttons
    const editButtons = document.querySelectorAll("#edit-row");

    // Add click event listener to each edit button
    editButtons.forEach((button) => {
      button.addEventListener("click", function () {
        // Get the parent row (tr) of the clicked button
        const row = this.closest("tr");

        // Find the select element and save button in this row
        const selectElement = row.querySelector(
          "select.status-option-inactive"
        );
        const saveButton = row.querySelector("button.save-btn-inactive");

        // If elements are found, update them
        if (selectElement) {
          // Remove disabled attribute
          selectElement.removeAttribute("disabled");

          // Change class from inactive to active
          selectElement.classList.remove("status-option-inactive");
          selectElement.classList.add("status-option-active");
        }

        if (saveButton) {
          // Change class from inactive to active
          saveButton.classList.remove("save-btn-inactive");
          saveButton.classList.add("save-btn-active");
        }
      });
    });

    // This would handle reverting the form back to inactive state after saving
    const saveButtons = document.querySelectorAll(".save-btn-inactive");
    saveButtons.forEach((button) => {
      button.addEventListener("click", function () {
        // This only works after the button has been activated
        if (this.classList.contains("save-btn-active")) {
          const row = this.closest("tr");
          const selectElement = row.querySelector(
            "select.status-option-active"
          );

          if (selectElement) {
            // Disable the select element again
            selectElement.setAttribute("disabled", "disabled");

            // Change classes back to inactive
            selectElement.classList.remove("status-option-active");
            selectElement.classList.add("status-option-inactive");

            // Change save button back to inactive
            this.classList.remove("save-btn-active");
            this.classList.add("save-btn-inactive");

            // Here you would typically save the data to your backend
            console.log("Status updated to:", selectElement.value);
          }
        }
      });
    });
  });

  const tableBody = document.querySelector(".table-table tbody");
  let allRepairRecords = [];
  let editingRowId = null;

  function populateTable(data) {
    tableBody.innerHTML = "";
    data.forEach((record) => {
      const row = tableBody.insertRow();
      row.dataset.distId = record.dist_id;

      // Edit Button
      const editCell = row.insertCell();
      const editButton = document.createElement("button");
      editButton.textContent = "Edit";
      editButton.classList.add("edit-btn");
      editButton.addEventListener("click", () => enableEdit(record.dist_id));
      editCell.appendChild(editButton);

      // Status Dropdown
      const statusCell = row.insertCell();
      const statusDropdown = document.createElement("select");
      statusDropdown.classList.add("status-dropdown");
      statusDropdown.disabled = true; // Initially disabled

      const forRepairOption = document.createElement("option");
      forRepairOption.value = 4;
      forRepairOption.textContent = "For Repair";
      statusDropdown.appendChild(forRepairOption);

      const onRepairOption = document.createElement("option");
      onRepairOption.value = 6;
      onRepairOption.textContent = "On Repair";
      statusDropdown.appendChild(onRepairOption);

      const claimedOption = document.createElement("option");
      claimedOption.value = 3;
      claimedOption.textContent = "Claimed";
      statusDropdown.appendChild(claimedOption);

      statusDropdown.value = record.status_id; // Set initial value
      statusCell.appendChild(statusDropdown);

      // Save Button
      const saveCell = row.insertCell();
      const saveButton = document.createElement("button");
      saveButton.textContent = "Save";
      saveButton.classList.add("save-btn");
      saveButton.disabled = true; // Initially disabled
      saveButton.addEventListener("click", () =>
        saveStatus(record.dist_id, statusDropdown.value)
      );
      saveCell.appendChild(saveButton);

      // Action Button - View More (similar to borrowers dashboard)
      const actionCell = row.insertCell();
      const viewMoreLink = document.createElement("a");
      viewMoreLink.style.color = "#1C2143";
      viewMoreLink.href = "#";
      viewMoreLink.textContent = "View More";
      viewMoreLink.addEventListener("click", function (event) {
        event.preventDefault();
        openViewMoreOverlay(record); // You'll need to implement this function
        event.stopPropagation();
      });
      actionCell.appendChild(viewMoreLink);

      insertCell(row, record.box_no);
      insertCell(row, record.borrower_name);
      insertCell(row, record.item_name);
      insertCell(row, record.serial_no);
      insertCell(row, record.received_date);
      insertCell(row, record.accountable_name);
      insertCell(row, record.repair_reason);
    });
  }

  function insertCell(row, text) {
    const cell = row.insertCell();
    cell.textContent = text || "N/A";
  }

  function enableEdit(distId) {
    const row = Array.from(tableBody.rows).find(
      (row) => row.dataset.distId === String(distId)
    );
    if (row) {
      const dropdown = row.querySelector(".status-dropdown");
      const saveButton = row.querySelector(".save-btn");
      if (dropdown && saveButton) {
        dropdown.disabled = false;
        saveButton.disabled = false;
        editingRowId = distId;
      }
    }
  }

  function saveStatus(distId, newStatusId) {
    if (editingRowId !== distId) {
      alert(
        "No Changes Made: Please select a row and make changes before saving."
      );
      return;
    }

    fetch("../php/process_update_repair_status.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        dist_id: distId,
        status_id: newStatusId,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert(
            "✅ Status Update Complete: The item's repair status has been successfully updated."
          );
          editingRowId = null;
          fetchRepairRecords(); // Refresh the table
        } else {
          alert(
            "Error Updating Status: " +
              (data.error ||
                "An unexpected error occurred. Please try again or contact support.")
          );
        }
      })
      .catch((error) => {
        console.error("Error updating status:", error);
        alert(
          "System Error: Unable to update the repair status. Please try again later or contact support."
        );
      });
  }

  function filterTable(query) {
    const lowerCaseQuery = query.toLowerCase();
    const filteredRecords = allRepairRecords.filter((record) => {
      const borrowerName = record.borrower_name
        ? record.borrower_name.toLowerCase()
        : "";
      const serialNo = record.serial_no ? record.serial_no.toLowerCase() : "";
      const itemName = record.item_name ? record.item_name.toLowerCase() : "";
      const accountableName = record.accountable_name
        ? record.accountable_name.toLowerCase()
        : "";
      const repairReason = record.repair_reason
        ? record.repair_reason.toLowerCase()
        : "";

      return (
        borrowerName.includes(lowerCaseQuery) ||
        serialNo.includes(lowerCaseQuery) ||
        itemName.includes(lowerCaseQuery) ||
        accountableName.includes(lowerCaseQuery) ||
        repairReason.includes(lowerCaseQuery)
      );
    });
    populateTable(filteredRecords);
  }

  searchInput.addEventListener("input", function () {
    const query = this.value.trim();
    filterTable(query);
  });

  function fetchRepairRecords() {
    fetch("../php/get_repair_records.php")
      .then((response) => response.json())
      .then((data) => {
        allRepairRecords = data;
        populateTable(data);
      })
      .catch((error) => {
        console.error("Error fetching repair records:", error);
        tableBody.innerHTML =
          '<tr><td colspan="14">Failed to load repair records.</td></tr>';
      });
  }

  // Initial fetch
  fetchRepairRecords();

  // --- View More Functionality (You'll need to adapt the overlay and content) ---
  const viewMoreOverlay = document.getElementById("viewMoreOverlay"); // Assuming you have this in your repair_dashboard.html
  const viewMoreId = document.getElementById("viewMore-id");
  const viewMoreName = document.getElementById("viewMore-name");
  // ... Add other view more elements as needed

  function openViewMoreOverlay(record) {
    console.log("View More clicked for repair record:", record);

    const viewMoreId = document.getElementById("viewMore-id");
    const viewMoreName = document.getElementById("viewMore-name");
    const viewMoreGender = document.getElementById("viewMore-gender");
    const viewMoreSection = document.getElementById("viewMore-section");
    const viewMoreDepartment = document.getElementById("viewMore-department");
    const viewMoreContact = document.getElementById("viewMore-contact");
    const viewMoreEmail = document.getElementById("viewMore-email"); // You might not have email in your repair records
    const viewMoreAddress = document.getElementById("viewMore-address"); // You might not have address in your repair records
    const borrowerPhoto = document.querySelector(
      "#viewMoreOverlay .borrower-photo"
    );

    viewMoreId.textContent = `ID: ${
      record.borrower_type === "student" ? record.student_id || "N/A" : "N/A"
    }`;
    viewMoreName.textContent = `Name: ${record.borrower_name || "N/A"}`;
    viewMoreGender.textContent = `Gender: ${
      record.borrower_type === "student" ? record.gender || "N/A" : "N/A"
    }`;
    viewMoreSection.textContent = `Section: ${record.section || "N/A"}`;
    viewMoreDepartment.textContent = `Department: ${
      record.borrower_department || "N/A"
    }`;
    viewMoreContact.textContent = `Contact: ${
      record.borrower_type === "student"
        ? record.contact_number || "N/A"
        : "N/A"
    }`;
    viewMoreEmail.textContent = `Email: ${
      record.borrower_type === "student" ? record.email || "N/A" : "N/A"
    }`;
    viewMoreAddress.textContent = `Address: ${
      record.borrower_type === "student" ? record.stud_address || "N/A" : "N/A"
    }`;

    // Set the borrower's photo (you might need to adjust the path based on your structure)
    if (
      record.photo_path &&
      record.photo_path !== null &&
      record.photo_path !== ""
    ) {
      borrowerPhoto.src = record.photo_path;
    } else {
      borrowerPhoto.src = "../assets/borrowers-photo/default-photo.png";
    }

    viewMoreOverlay.style.opacity = 1;
    viewMoreOverlay.style.visibility = "visible";
    viewMoreOverlay.style.display = "flex";
  }

  function closePopup(overlayId) {
    const overlay = document.getElementById(overlayId);
    if (overlay) {
      overlay.style.display = "none";
      overlay.style.visibility = "hidden";
      overlay.style.opacity = 0;
    }
  }
  window.closePopup = closePopup;

  // Close the view more overlay when the close button is clicked
  const closeViewMoreBtn = document.querySelector(
    "#viewMoreOverlay .close-btn"
  );
  if (closeViewMoreBtn) {
    closeViewMoreBtn.addEventListener("click", function () {
      closePopup("viewMoreOverlay");
    });
  }

  // Close the view more overlay if the user clicks outside of it
  window.addEventListener("click", function (event) {
    if (event.target === viewMoreOverlay) {
      closePopup("viewMoreOverlay");
    }
  });
});
