document.addEventListener("DOMContentLoaded", () => {
  const showPopupBtn = document.getElementById("scan-btn");
  const overlayContainer = document.getElementById("overlayContainer");
  const closePopupBtn = document.getElementById("closePopupBtn");
  const popup = document.querySelector(".popup");

  const searchInput = document.getElementById("searchbar");
  const tableBody = document.querySelector("#tblReturnLogs tbody");
  let allRepairLogs = [];

  // --- Scanner Module ---
  let html5QrcodeScanner = null;

  function startScanner() {
    const config = {
      fps: 10,
      qrbox: { width: 250, height: 250 },
    };

    html5QrcodeScanner = new Html5QrcodeScanner("reader", config, false);

    html5QrcodeScanner.render((decodedText, decodedResult) => {
      console.log(`Scan result: ${decodedText}`, decodedResult);
      document.getElementById("result").innerHTML = `Scanned: ${decodedText}`;
    });
  }

  function stopScanner() {
    if (html5QrcodeScanner) {
      html5QrcodeScanner
        .clear()
        .then(() => {
          html5QrcodeScanner = null;
        })
        .catch((error) => {
          console.error("Failed to clear scanner:", error);
        });
    }
  }

  // --- Popup Functions ---
  function showPopup() {
    overlayContainer.classList.add("active");
    startScanner();
  }

  function hidePopup() {
    overlayContainer.classList.remove("active");
    stopScanner();
  }

  // --- Popup Event Listeners ---
  showPopupBtn.addEventListener("click", (event) => {
    event.preventDefault();
    event.stopPropagation();
    showPopup();
  });

  closePopupBtn.addEventListener("click", (event) => {
    event.preventDefault();
    event.stopPropagation();
    hidePopup();
  });

  overlayContainer.addEventListener("click", (event) => {
    if (event.target === overlayContainer) {
      hidePopup();
    }
  });

  // --- Table Population ---
  function populateRepairLogsTable(data) {
    tableBody.innerHTML = "";

    data.forEach((record) => {
      const row = tableBody.insertRow();
      const boxNoCell = row.insertCell();
      const accountableCell = row.insertCell();
      const departmentCell = row.insertCell();
      const nameCell = row.insertCell();
      const sectionCell = row.insertCell();
      const itemNameCell = row.insertCell();
      const serialNoCell = row.insertCell();
      const repairDateCell = row.insertCell();
      const reasonCell = row.insertCell();
      const staffCell = row.insertCell();

      boxNoCell.textContent = record.box_number || "N/A";
      accountableCell.textContent = record.accountable || "N/A";
      departmentCell.textContent = record.department || "N/A";
      nameCell.textContent = record.receiver || "N/A";
      sectionCell.textContent = record.section || "N/A";
      itemNameCell.textContent = record.item_name || "N/A";
      serialNoCell.textContent = record.serial_number || "N/A";
      repairDateCell.textContent = record.repair_date
        ? new Date(record.repair_date).toLocaleDateString()
        : "N/A";
      reasonCell.textContent = record.reason || "N/A";
      staffCell.textContent = record.staff || "N/A";
    });
  }

  // --- Data Fetching ---
  function fetchRepairLogs() {
    fetch("../php/get_repair_logs.php")
      .then((response) => response.json())
      .then((data) => {
        allRepairLogs = data;
        populateRepairLogsTable(data);
      })
      .catch((error) => {
        console.error("Error fetching repair logs:", error);
        tableBody.innerHTML =
          '<tr><td colspan="11">Failed to load repair logs.</td></tr>';
      });
  }

  // --- Table Filtering ---
  function filterRepairLogsTable(query) {
    const lowerCaseQuery = query.toLowerCase();

    const filteredRecords = allRepairLogs.filter((record) => {
      const borrowerName = record.receiver?.toLowerCase() || "";
      const serialNo = record.serial_number?.toLowerCase() || "";
      const itemName = record.item_name?.toLowerCase() || "";
      const accountableName = record.accountable?.toLowerCase() || "";
      const reason = record.reason?.toLowerCase() || "";

      return (
        borrowerName.includes(lowerCaseQuery) ||
        serialNo.includes(lowerCaseQuery) ||
        itemName.includes(lowerCaseQuery) ||
        accountableName.includes(lowerCaseQuery) ||
        reason.includes(lowerCaseQuery)
      );
    });

    populateRepairLogsTable(filteredRecords);
  }

  searchInput.addEventListener("input", function () {
    const query = this.value.trim();
    filterRepairLogsTable(query);
  });

  // --- Initialize ---
  fetchRepairLogs();
});
