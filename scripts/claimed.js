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
        selectedRowData.receiver_department || "N/A"
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
      const returnDate = new Date().toISOString().slice(0, 10);
      const itemCondition = returnConditionDropdown
        ? returnConditionDropdown.value
        : null;
      const remarks = remarksTextarea ? remarksTextarea.value : null;
      const itemType = selectedRowData.item_type; // Get item_type

      console.log("Data being sent for return:", {
        dist_id: distId,
        return_date: returnDate,
        item_condition: itemCondition,
        remarks: remarks,
        item_type: itemType, // Include item_type
      });

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
          item_type: itemType, // Send item_type
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          console.log("Response from PHP:", data);
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
      departmentCell.textContent = record.department || "N/A";
      nameCell.textContent = record.borrower_name || "N/A";
      sectionCell.textContent = record.section || "N/A";
      itemNameCell.textContent = record.item_name || "N/A";
      serialNoCell.textContent = record.serial_no || "N/A";
      receivedDateCell.textContent = record.received_date || "N/A";

      // --- "View More" Link ---
      const viewMoreLink = document.createElement("a");
      viewMoreLink.style.color = "#0e2f65";
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
      record.receiver_department || "N/A"
    }`; // Changed to receiver_department
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

    viewMoreOverlay.style.opacity = 1;
    viewMoreOverlay.style.visibility = "visible";
    viewMoreOverlay.style.display = "flex";
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
      const department = record.department
        ? record.department.toLowerCase()
        : ""; // Search by the 'Department' column in the table

      return (
        borrowerName.includes(lowerCaseQuery) ||
        studentId.includes(lowerCaseQuery) ||
        serialNo.includes(lowerCaseQuery) ||
        itemName.includes(lowerCaseQuery) ||
        accountableName.includes(lowerCaseQuery) ||
        department.includes(lowerCaseQuery)
      );
    });
    populateTable(filteredRecords);
  }

  function fetchBorrowerRecords() {
    // Fetch the user's role
    fetch("../php/get_user_role.php")
      .then((response) => response.json())
      .then((userData) => {
        const userRole = userData.role;

        // Construct the fetch URL, potentially including the role
        let fetchUrl = "../php/get_borrower_records.php";

        // Fetch data from the PHP script, now potentially passing the role
        fetch(fetchUrl, {
          method: "POST", // Or GET depending on how you want to handle it
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ role: userRole }), // Send the role in the request body
        })
          .then((response) => response.json())
          .then((data) => {
            allBorrowerRecords = data;
            populateTable(allBorrowerRecords);
          })
          .catch((error) => {
            console.error("Error fetching borrower records:", error);
            alert("Failed to load borrower records.");
          });
      })
      .catch((error) => {
        console.error("Error fetching user role:", error);
        alert("Failed to determine user role.");
      });
  }

  function populateReturnOverlay() {
    if (selectedRowData) {
      returnIdNo.textContent = `ID No: ${
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
        selectedRowData.receiver_department || "N/A"
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

      // Conditionally show/hide the condition dropdown based on item_type
      if (selectedRowData.item_type === "bag") {
        document.getElementById("return-condition-label").style.display =
          "block";
        returnConditionDropdown.style.display = "block";
      } else {
        document.getElementById("return-condition-label").style.display =
          "none";
        returnConditionDropdown.style.display = "block";
        // returnConditionDropdown.value = ""; // Reset value if hidden
      }

      // Clear any previous remarks
      remarksTextarea.value = "";
    }
  }

  function stopScanner() {
    if (html5QrcodeScanner) {
      html5QrcodeScanner.clear();
      html5QrcodeScanner = null;
    }
  }

  // Event listeners
  if (repairBtn) {
    repairBtn.addEventListener("click", openRepairOverlay);
  }
  if (closeRepairBtn) {
    closeRepairBtn.addEventListener("click", () => closePopup("repairOverlay"));
  }
  if (showPopupBtn) {
    showPopupBtn.addEventListener("click", showPopup);
  }
  if (closePopupBtn) {
    closePopupBtn.addEventListener("click", hidePopup);
  }
  if (returnPopupBtn) {
    returnPopupBtn.addEventListener("click", showReturnPopup);
  }
  if (returnCloseBtn) {
    returnCloseBtn.addEventListener("click", hideReturnPopup);
  }
  if (returnConfirmTriggerBtn) {
    returnConfirmTriggerBtn.addEventListener("click", showReturnConfirmation);
  }
  if (returnCancelBtn) {
    returnCancelBtn.addEventListener("click", hideReturnConfirmation);
  }
  if (returnConfirmFinal) {
    returnConfirmFinal.addEventListener("click", handleReturnConfirmation);
  }
  if (viewMoreCloseBtn) {
    viewMoreCloseBtn.addEventListener("click", () =>
      closePopup("viewMoreOverlay")
    );
  }
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      const query = this.value.trim();
      filterTable(query);
    });
  }
  if (repairConfirmButton) {
    repairConfirmButton.addEventListener("click", function () {
      if (selectedRowData && selectedRowData.dist_id) {
        const repairReason = repairReasonTextarea.value.trim();
        if (repairReason) {
          fetch("../php/process_repair_request.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify({
              dist_id: selectedRowData.dist_id,
              repair_reason: repairReason,
            }),
          })
            .then((response) => response.json())
            .then((data) => {
              if (data.success) {
                alert("Repair request submitted successfully!");
                closePopup("repairOverlay");
                fetchBorrowerRecords(); // Refresh the table
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
          alert("Please provide a reason for the repair.");
        }
      } else {
        alert("No item selected for repair.");
      }
    });
  }

  // Initial fetch of borrower records
  fetchBorrowerRecords();
});
