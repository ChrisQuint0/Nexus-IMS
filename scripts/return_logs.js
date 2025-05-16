document.addEventListener("DOMContentLoaded", function () {
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

    html5QrcodeScanner.render(
      function (decodedText, decodedResult) {
        console.log(`Scan result: ${decodedText}`, decodedResult);
        // document.getElementById("result").innerHTML = `Scanned: ${decodedText}`;

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

  function gotoReturnLogs() {
    window.location.href = "/pages/return_logs.html";
  }

  const tableBody = document.querySelector("#tblReturnLogs tbody");
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
  const searchInput = document.getElementById("searchbar");
  let allReturnLogs = [];
  let selectedLogRowData = null;

  function populateTable(data) {
    console.log("populateTable called with data:", data);
    tableBody.innerHTML = ""; // Clear existing table rows
    data.forEach((log) => {
      const row = tableBody.insertRow();
      row.style.cursor = "pointer"; // Indicate row is clickable
      row.addEventListener("click", function () {
        const currentlySelected = document.querySelector(
          "#tblReturnLogs tbody tr.selected"
        );
        if (currentlySelected) {
          currentlySelected.classList.remove("selected");
        }
        this.classList.add("selected");
        selectedLogRowData = log;
        console.log("Selected log data:", selectedLogRowData);
      });

      // Action column with "View More" link
      const actionCell = row.insertCell();
      const viewMoreLink = document.createElement("a");
      viewMoreLink.style.color = "#1C2143";
      viewMoreLink.href = "#";
      viewMoreLink.textContent = "View More";
      viewMoreLink.addEventListener("click", function (event) {
        event.preventDefault();
        openViewMoreOverlay(log);
        event.stopPropagation();
      });
      actionCell.appendChild(viewMoreLink);

      const boxNoCell = row.insertCell();
      boxNoCell.textContent = log.box_no || "N/A";

      const accountableCell = row.insertCell();
      accountableCell.textContent = log.accountable_name || "N/A";

      const departmentCell = row.insertCell();
      departmentCell.textContent = log.borrower_department || "N/A";

      const nameCell = row.insertCell();
      nameCell.textContent = log.borrower_name || "N/A";

      const sectionCell = row.insertCell();
      sectionCell.textContent = log.section || "N/A";

      const itemNameCell = row.insertCell();
      itemNameCell.textContent = log.item_name || "N/A";

      const serialNoCell = row.insertCell();
      serialNoCell.textContent = log.serial_no || "N/A";

      const receivedDateCell = row.insertCell();
      receivedDateCell.textContent = log.received_date || "N/A";

      const returnDateCell = row.insertCell();
      returnDateCell.textContent = log.return_date || "N/A";

      const conditionCell = row.insertCell(); // ADD THIS
      conditionCell.textContent = log.item_condition || "N/A"; // ADD THIS

      const remarksCell = row.insertCell(); // ADD THIS
      remarksCell.textContent = log.remarks || "N/A"; // ADD THIS
    });
  }

  function openViewMoreOverlay(record) {
    console.log("View More clicked for log:", record);
    viewMoreId.textContent = `ID: ${record.student_id || "N/A"}`;
    viewMoreName.textContent = `Name: ${record.borrower_name || "N/A"}`;
    viewMoreGender.textContent = `Gender: ${record.gender || "N/A"}`;
    viewMoreSection.textContent = `Section: ${record.section || "N/A"}`;
    viewMoreDepartment.textContent = `Department: ${
      record.borrower_department || "N/A"
    }`;
    viewMoreContact.textContent = `Contact: ${record.contact_number || "N/A"}`;
    viewMoreEmail.textContent = `Email: ${record.email || "N/A"}`;
    viewMoreAddress.textContent = `Address: ${record.stud_address || "N/A"}`;

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

  function closePopup(overlayId) {
    document.getElementById(overlayId).style.display = "none";
  }
  window.closePopup = closePopup;

  function filterTable(query) {
    const lowerCaseQuery = query.toLowerCase();
    const filteredLogs = allReturnLogs.filter((log) => {
      const borrowerName = log.borrower_name
        ? log.borrower_name.toLowerCase()
        : "";
      const studentId = log.student_id ? log.student_id.toLowerCase() : "";
      const serialNo = log.serial_no ? log.serial_no.toLowerCase() : "";
      const itemName = log.item_name ? log.item_name.toLowerCase() : "";
      const accountableName = log.accountable_name
        ? log.accountable_name.toLowerCase()
        : "";

      return (
        borrowerName.includes(lowerCaseQuery) ||
        studentId.includes(lowerCaseQuery) ||
        serialNo.includes(lowerCaseQuery) ||
        itemName.includes(lowerCaseQuery) ||
        accountableName.includes(lowerCaseQuery)
      );
    });
    populateTable(filteredLogs);
  }

  searchInput.addEventListener("input", function () {
    const query = this.value.trim();
    filterTable(query);
  });

  fetch("../php/get_return_logs.php")
    .then((response) => response.json())
    .then((data) => {
      allReturnLogs = data;
      populateTable(data);
    })
    .catch((error) => {
      console.error("Error fetching return logs:", error);
      tableBody.innerHTML =
        '<tr><td colspan="11">Failed to load return logs.</td></tr>';
    });

  // Close the view more overlay when the close button is clicked
  const closeViewMoreBtn = document.querySelector(
    "#viewMoreOverlay .close-btn"
  );
  closeViewMoreBtn.addEventListener("click", function () {
    closePopup("viewMoreOverlay");
  });

  // Close the view more overlay if the user clicks outside of it
  window.addEventListener("click", function (event) {
    if (event.target === viewMoreOverlay) {
      closePopup("viewMoreOverlay");
    }
  });
});
