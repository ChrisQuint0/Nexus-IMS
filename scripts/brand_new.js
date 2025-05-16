document.addEventListener("DOMContentLoaded", function () {
  const repairBtn = document.getElementById("repairBtn");
  const repairOverlay = document.getElementById("repairOverlay");
  const repairIdNo = document.getElementById("repair-idNo");
  const repairName = document.getElementById("repair-name");
  const repairGender = document.getElementById("repair-gender");
  const repairSection = document.getElementById("repair-section");
  const repairDepartment = document.getElementById("repair-department");
  const repairContact = document.getElementById("repair-contact");
  const repairAddress = document.getElementById("repair-address");
  const repairItemName = document.getElementById("repair-itemName");
  const repairSerialNumber = document.getElementById("repair-serialNumber");
  const repairReasonTextarea = document.getElementById("repair-reason");
  const closeRepairBtn = document.querySelector("#repairOverlay .close-btn");
  const repairConfirmButton = document.getElementById("repair-confirm");
  const repairConfirmOverlay = document.getElementById(
    "repair-confirm-overlay"
  );
  const confirmRepairFinalButton = document.getElementById("confirm-repair");
  const cancelRepairButton = document.getElementById("cancel-repair");
  const cancelRepairFinalButton = document.getElementById("cancel-repair");

  const repairOverlayContainer = document.getElementById("repairOverlay");

  const showPopupBtn = document.getElementById("scan-btn");
  const overlayContainer = document.getElementById("overlayContainer");
  const closePopupBtn = document.getElementById("closePopupBtn");

  const returnPopupBtn = document.getElementById("returnBtn");
  const returnOverlayContainer = document.getElementById("returnOverlay");
  const returnCloseBtn = document.querySelector("#returnOverlay .close-btn");

  const returnCnfirmOverlay = document.getElementById("confirm-overlay"); // Corrected ID
  const returnConfirmTriggerBtn = document.getElementById("return-confirm"); // Corrected ID
  const returnConfirmActionBtn = document.getElementById("confirm-return"); // Corrected ID
  const returnCancelBtn = document.getElementById("cancel-return"); // Corrected ID

  const returnConfirmFinal = document.getElementById("confirm-return");
  const returnConditionDropdown = document.getElementById("return-condition");
  const remarksTextarea = document.getElementById("remarks");

  const tableBody = document.querySelector(".table-table tbody");
  const viewMoreOverlay = document.getElementById("viewMoreOverlay");
  const viewMoreId = document.getElementById("viewMore-id");
  const viewMoreName = document.getElementById("viewMore-name");
  const viewMoreGender = document.getElementById("viewMore-gender");
  const viewMoreSection = document.getElementById("viewMore-section");
  const viewMoreDepartment = document.getElementById("viewMore-department");
  const viewMoreContact = document.getElementById("viewMore-contact");
  const viewMoreEmail = document.getElementById("viewMore-email");
  const viewMoreAddress = document.getElementById("viewMore-address");
  const borrowerPhoto = document.querySelector(
    "#viewMoreOverlay .borrower-photo"
  );
  const viewMoreCloseBtn = document.querySelector(
    "#viewMoreOverlay .close-btn"
  );
  const viewMoreOverlayContainer = document.getElementById("viewMoreOverlay");

  const returnIdNo = document.getElementById("return-idNo");
  const returnName = document.getElementById("return-name");
  const returnGender = document.getElementById("return-gender");
  const returnSection = document.getElementById("return-section");
  const returnDepartment = document.getElementById("return-department");
  const returnContact = document.getElementById("return-contact");
  const returnAddress = document.getElementById("return-address");
  const returnItemName = document.getElementById("return-itemName");
  const returnSerialNumber = document.getElementById("return-serialNumber");

  const searchInput = document.getElementById("searchbar");
  let allBorrowerRecords = [];
  let selectedRowData = null; // To store the data of the selected row

  function openRepairOverlay() {
    if (selectedRowData) {
      repairIdNo.textContent = `ID No: ${
        selectedRowData.borrower_type === "student"
          ? selectedRowData.student_id || "N/A"
          : "N/A"
      }`;
      repairName.textContent = `Name: ${
        selectedRowData.borrower_name || "N/A"
      }`;
      repairGender.textContent = `Gender: ${
        selectedRowData.borrower_type === "student"
          ? selectedRowData.gender || "N/A"
          : "N/A"
      }`;
      repairSection.textContent = `Section: ${
        selectedRowData.section || "N/A"
      }`;
      repairDepartment.textContent = `Department: ${
        selectedRowData.borrower_department || "N/A"
      }`;
      repairContact.textContent = `Contact: ${
        selectedRowData.borrower_type === "student"
          ? selectedRowData.contact_number || "N/A"
          : "N/A"
      }`;
      repairAddress.textContent = `Address: ${
        selectedRowData.borrower_type === "student"
          ? selectedRowData.stud_address || "N/A"
          : "N/A"
      }`;
      repairItemName.textContent = `Item Name: ${
        selectedRowData.item_name || "N/A"
      }`;
      repairSerialNumber.textContent = `Serial Number: ${
        selectedRowData.serial_no || "N/A"
      }`;

      // Clear any previous remarks
      repairReasonTextarea.value = "";

      repairOverlay.style.opacity = 1;
      repairOverlay.style.visibility = "visible";
      repairOverlay.style.display = "flex";
    } else {
      alert("Please select a row in the table first to request a repair.");
    }
  }

  function hideRepairPopup() {
    console.log("Hiding repair popup");
    const repairOverlay = document.getElementById("repairOverlay");
    if (repairOverlay) {
      repairOverlay.classList.remove("active");
      repairOverlay.style.display = "none";
      repairOverlay.style.visibility = "hidden";
      repairOverlay.style.opacity = 0;
    }
  }

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

    html5QrcodeScanner.render(
      function (decodedText, decodedResult) {
        console.log(`Scan result: ${decodedText}`, decodedResult);

        const searchInput = document.getElementById("searchbar");

        searchInput.value = decodedText;

        const query = searchInput.value.trim();

        filterTable(query);

        hidePopup();
      },
      function (errorMessage) {
        console.log(`QR code scan error = ${errorMessage}`);
      }
    );
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

  function showReturnPopup(event) {
    event.preventDefault();
    if (selectedRowData) {
      populateReturnOverlay();
      returnOverlayContainer.classList.add("active");
      // Ensure the overlay is visible when activated
      returnOverlayContainer.style.visibility = "visible";
      returnOverlayContainer.style.opacity = "1";
      returnOverlayContainer.style.display = "flex"; // Or "block" depending on your CSS
    } else {
      alert("Please select a row in the table before clicking 'Return'.");
    }
  }

  function hideReturnPopup() {
    returnOverlayContainer.classList.remove("active");
    selectedRowData = null;
    const currentlySelected = document.querySelector(
      ".table-table tbody tr.selected"
    );
    if (currentlySelected) {
      currentlySelected.classList.remove("selected");
    }
    // Ensure the overlay is fully hidden
    returnOverlayContainer.style.visibility = "hidden";
    returnOverlayContainer.style.opacity = "0";
    returnOverlayContainer.style.display = "none"; // Add this to be explicit
  }
  // Function to close specific popups (for the X buttons)
  function closePopup(overlayId) {
    document.getElementById(overlayId).classList.remove("active");
    document.getElementById(overlayId).style.display = "none";
    document.getElementById(overlayId).style.visibility = "hidden";
    document.getElementById(overlayId).style.opacity = 0;
  }
  window.closePopup = closePopup;

  // Confirmation overlay functionality (Return)
  function showReturnConfirmation(event) {
    event.preventDefault();
    console.log("Showing return confirmation overlay");
    returnCnfirmOverlay.classList.add("active");
  }

  function hideReturnConfirmation() {
    returnCnfirmOverlay.classList.remove("active");
  }

  function handleReturnConfirmation() {
    if (selectedRowData && selectedRowData.dist_id) {
      const distId = selectedRowData.dist_id;
      const returnDate = new Date().toISOString().slice(0, 10); // Get today's date in YYYY-MM-DD format
      const itemCondition = returnConditionDropdown
        ? returnConditionDropdown.value
        : null;
      const remarks = remarksTextarea ? remarksTextarea.value : null;

      fetch("../php/process_return.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          dist_id: distId,
          return_date: returnDate,
          item_condition: itemCondition,
          remarks: remarks,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            alert("Item returned successfully!");
            hideAllReturnOverlays(); // Close all return-related overlays
            fetchBorrowerRecords(); // Refresh the table to reflect changes
          } else {
            alert(
              "Error processing return: " + (data.error || "Unknown error")
            );
          }
        })
        .catch((error) => {
          console.error("Error sending return data:", error);
          alert("An error occurred while processing the return.");
        });
    } else {
      alert("No item selected for return.");
    }
  }

  function hideConfirmation() {
    console.log("Hiding return confirmation overlay");
    const returnCnfirmOverlay = document.getElementById("confirm-overlay");
    if (returnCnfirmOverlay) {
      returnCnfirmOverlay.classList.remove("active");
    }
  }

  function hideAllReturnOverlays() {
    hideConfirmation();
    hideReturnPopup();
  }

  function populateTable(data) {
    tableBody.innerHTML = ""; // Clear existing table rows

    data.forEach((record) => {
      const row = tableBody.insertRow();

      // Create table cells for each piece of data in the record
      const actionCell = row.insertCell();
      const boxNoCell = row.insertCell();
      const accountableCell = row.insertCell();
      const departmentCell = row.insertCell();
      const nameCell = row.insertCell();
      const sectionCell = row.insertCell();
      const itemNameCell = row.insertCell();
      const serialNoCell = row.insertCell();
      const receivedDateCell = row.insertCell();

      // Populate the cells with data from the record
      boxNoCell.textContent = record.box_no || "N/A";
      accountableCell.textContent = record.accountable_name || "N/A";
      departmentCell.textContent = record.borrower_department || "N/A";
      nameCell.textContent = record.borrower_name || "N/A";
      sectionCell.textContent = record.section || "N/A";
      itemNameCell.textContent = record.item_name || "N/A";
      serialNoCell.textContent = record.serial_no || "N/A";
      receivedDateCell.textContent = record.received_date || "N/A";

      // Add item type indicator (optional - you can style this or handle it differently)
      itemNameCell.title = `Item Type: ${record.item_type}`;

      // --- "View More" Link ---
      const viewMoreLink = document.createElement("a");
      viewMoreLink.style.color = "#1C2143";
      viewMoreLink.href = "#";
      viewMoreLink.textContent = "View More";
      viewMoreLink.addEventListener("click", function (event) {
        event.preventDefault();
        openViewMoreOverlay(record);
        event.stopPropagation();
      });
      actionCell.appendChild(viewMoreLink);

      // --- Row Selection for Actions (Return, Repair) ---
      row.addEventListener("click", function () {
        // Remove 'selected' class from any previously selected row
        const currentlySelected = document.querySelector(
          ".table-table tbody tr.selected"
        );
        if (currentlySelected) {
          currentlySelected.classList.remove("selected");
        }
        // Add 'selected' class to the clicked row
        this.classList.add("selected");
        selectedRowData = record; // Store the data of the selected row
        console.log("Selected Row Data:", selectedRowData);
      });
    });
  }

  function openViewMoreOverlay(record) {
    console.log("View More clicked for record:", record);
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

    // Set the borrower's photo
    if (
      record.photo_path &&
      record.photo_path !== null &&
      record.photo_path !== ""
    ) {
      borrowerPhoto.src = record.photo_path;
    } else {
      borrowerPhoto.src = "../assets/borrowers-photo/default-photo.png";
    }

    // viewMoreOverlay.style.display = "flex";
    viewMoreOverlay.style.opacity = 1; // Directly set opacity
    viewMoreOverlay.style.visibility = "visible"; // Directly set visibility
    viewMoreOverlay.style.display = "flex"; // Ensure it's displayed
    // viewMoreOverlay.classList.add("active"); // Comment out this line
  }

  function filterTable(query) {
    const lowerCaseQuery = query.toLowerCase();
    const filteredRecords = allBorrowerRecords.filter((record) => {
      const borrowerName = record.borrower_name
        ? record.borrower_name.toLowerCase()
        : "";
      const studentId = record.student_id
        ? record.student_id.toLowerCase()
        : "";
      const serialNo = record.serial_no ? record.serial_no.toLowerCase() : "";
      const itemName = record.item_name ? record.item_name.toLowerCase() : "";
      const accountableName = record.accountable_name
        ? record.accountable_name.toLowerCase()
        : "";
      const borrowerDepartment = record.borrower_department
        ? record.borrower_department.toLowerCase()
        : ""; // Added for department search

      return (
        borrowerName.includes(lowerCaseQuery) ||
        studentId.includes(lowerCaseQuery) ||
        serialNo.includes(lowerCaseQuery) ||
        itemName.includes(lowerCaseQuery) ||
        accountableName.includes(lowerCaseQuery) ||
        borrowerDepartment.includes(lowerCaseQuery) // Added for department search
      );
    });
    populateTable(filteredRecords);
  }

  function fetchBorrowerRecords() {
    // Fetch data from the PHP script
    fetch("../php/get_brandnew_items.php")
      .then((response) => response.json())
      .then((data) => {
        allBorrowerRecords = data;
        populateTable(data);
      })
      .catch((error) => {
        console.error("Error fetching borrower records:", error);
        tableBody.innerHTML =
          '<tr><td colspan="10">Failed to load records.</td></tr>';
      });
  }

  function populateReturnOverlay() {
    if (selectedRowData) {
      returnIdNo.textContent = `ID: ${
        selectedRowData.borrower_type === "student"
          ? selectedRowData.student_id || "N/A"
          : "N/A"
      }`;
      returnName.textContent = `Name: ${
        selectedRowData.borrower_name || "N/A"
      }`;
      returnGender.textContent = `Gender: ${
        selectedRowData.borrower_type === "student"
          ? selectedRowData.gender || "N/A"
          : "N/A"
      }`;
      returnSection.textContent = `Section: ${
        selectedRowData.section || "N/A"
      }`;
      returnDepartment.textContent = `Department: ${
        selectedRowData.borrower_department || "N/A"
      }`;
      returnContact.textContent = `Contact: ${
        selectedRowData.borrower_type === "student"
          ? selectedRowData.contact_number || "N/A"
          : "N/A"
      }`;
      returnAddress.textContent = `Address: ${
        selectedRowData.borrower_type === "student"
          ? selectedRowData.stud_address || "N/A"
          : "N/A"
      }`;
      returnItemName.textContent = `Item Name: ${
        selectedRowData.item_name || "N/A"
      }`;
      returnSerialNumber.textContent = `Serial Number: ${
        selectedRowData.serial_no || "N/A"
      }`;

      const conditionLabel = document.getElementById("conditionLabel");
      const conditionDropdown = document.getElementById("return-condition");

      if (selectedRowData.category_id === "1") {
        // Laptop
        conditionLabel.style.display = "block";
        conditionDropdown.style.display = "block";
      } else if (selectedRowData.category_id === "3") {
        // Bag
        conditionLabel.style.display = "none";
        conditionDropdown.style.display = "none";
      } else {
        // For other categories, you can choose to show or hide.
        // Let's default to showing for now.
        conditionLabel.style.display = "block";
        conditionDropdown.style.display = "block";
      }
    } else {
      alert("Please select a row in the table first.");
    }
  }

  // --- Event Listeners (Move them after element declarations and function definitions) ---
  repairBtn.addEventListener("click", openRepairOverlay);

  showPopupBtn.addEventListener("click", function (event) {
    event.preventDefault();
    event.stopPropagation();
    showPopup();
  });

  closePopupBtn.addEventListener("click", function (event) {
    event.preventDefault();
    event.stopPropagation();
    hidePopup();
  });

  overlayContainer.addEventListener("click", (event) => {
    if (event.target === overlayContainer) {
      hidePopup();
    }
  });

  returnPopupBtn.addEventListener("click", showReturnPopup);

  returnCloseBtn.addEventListener("click", function (event) {
    event.preventDefault();
    event.stopPropagation();
    hideReturnPopup();
  });

  returnOverlayContainer.addEventListener("click", (event) => {
    if (event.target === returnOverlayContainer) {
      hideReturnPopup();
    }
  });

  viewMoreCloseBtn.addEventListener("click", function (event) {
    event.preventDefault();
    event.stopPropagation();
    hideViewMorePopup(); // Corrected this to hide the view more popup
  });

  viewMoreOverlayContainer.addEventListener("click", (event) => {
    if (event.target === viewMoreOverlayContainer) {
      hideViewMorePopup(); // Corrected this to hide the view more popup
    }
  });

  closeRepairBtn.addEventListener("click", function (event) {
    event.preventDefault();
    event.stopPropagation();
    hideRepairPopup();
  });

  repairOverlayContainer.addEventListener("click", (event) => {
    if (event.target === repairOverlayContainer) {
      hideRepairPopup();
    }
  });

  // Event Listeners for Return Confirmation
  if (returnConfirmTriggerBtn) {
    returnConfirmTriggerBtn.addEventListener("click", showReturnConfirmation);
  }
  if (returnConfirmActionBtn) {
    returnConfirmActionBtn.addEventListener("click", handleReturnConfirmation);
  }
  if (returnCancelBtn) {
    returnCancelBtn.addEventListener("click", hideReturnConfirmation);
  }

  if (returnCnfirmOverlay) {
    returnCnfirmOverlay.addEventListener("click", (event) => {
      if (event.target === returnCnfirmOverlay) {
        hideReturnConfirmation();
        hideReturnPopup();
      }
    });
  }

  if (returnConfirmTriggerBtn && returnConfirmTriggerBtn.form) {
    returnConfirmTriggerBtn.form.addEventListener("submit", (event) => {
      event.preventDefault();
    });
  }

  if (returnConfirmFinal) {
    returnConfirmFinal.addEventListener("click", handleReturnConfirmation);
  }

  // Event Listeners for Repair Confirmation
  if (repairConfirmButton) {
    repairConfirmButton.addEventListener("click", showRepairConfirmation);
  }
  if (confirmRepairFinalButton) {
    confirmRepairFinalButton.addEventListener(
      "click",
      handleRepairConfirmation
    );
  }
  if (cancelRepairButton) {
    // Using the correct cancel button for the repair overlay
    cancelRepairButton.addEventListener("click", function () {
      closePopup("repairOverlay");
    });
  }
  if (cancelRepairFinalButton) {
    // Using the correct cancel button for the repair confirmation
    cancelRepairFinalButton.addEventListener("click", function () {
      closePopup("repair-confirm-overlay");
    });
  }

  if (repairConfirmOverlay) {
    repairConfirmOverlay.addEventListener("click", (event) => {
      if (event.target === repairConfirmOverlay) {
        hideRepairConfirmation();
      }
    });
  }

  searchInput.addEventListener("input", function () {
    const query = this.value.trim();
    filterTable(query);
  });

  // Initial fetch of borrower records
  fetchBorrowerRecords();
});

function hideViewMorePopup() {
  const viewMorePopup = document.getElementById("viewMorePopup");
  if (viewMorePopup) {
    viewMorePopup.style.display = "none";
    // You might also want to clear content or remove event listeners here
  }
}
function showRepairConfirmation(event) {
  if (event) {
    event.preventDefault(); // Prevent any default form submission behavior
  }
  console.log("Showing repair confirmation overlay");

  // Get the repair confirmation overlay element by its ID
  const repairConfirmOverlay = document.getElementById(
    "repair-confirm-overlay"
  );

  // If the overlay element exists, add the 'active' class to make it visible
  if (repairConfirmOverlay) {
    repairConfirmOverlay.classList.add("active");
  }
}

function hideRepairConfirmation() {
  console.log("Hiding repair confirmation overlay");

  // Get the repair confirmation overlay element by its ID
  constrepairConfirmOverlay = document.getElementById("repair-confirm-overlay");

  // If the overlay element exists, remove the 'active' class to hide it
  if (repairConfirmOverlay) {
    repairConfirmOverlay.classList.remove("active");
  }
}

function handleRepairConfirmation() {
  if (selectedRowData && selectedRowData.dist_id) {
    const distId = selectedRowData.dist_id;
    const repairReason = repairReasonTextarea.value.trim();
    const endpoint =
      selectedRowData.item_type === "bag"
        ? "../php/process_bag_repair_request.php"
        : "../php/process_repair_request.php";

    fetch(endpoint, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        dist_id: distId,
        repair_reason: repairReason,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Repair request submitted successfully!");
          closePopup("repairOverlay");
          closePopup("repair-confirm-overlay");
          fetchBorrowerRecords();
        } else {
          alert(
            "Error submitting repair request: " +
              (data.error || "Unknown error")
          );
        }
      })
      .catch((error) => {
        console.error("Error sending repair request:", error);
        alert("An error occurred while submitting the repair request.");
      });
  } else {
    alert("No item selected for repair.");
  }
}
