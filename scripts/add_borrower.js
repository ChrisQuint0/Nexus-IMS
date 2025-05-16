document.addEventListener("DOMContentLoaded", () => {
  const showPopupBtn = document.getElementById("scan-btn");
  const overlayContainer = document.getElementById("overlayContainer");
  const closePopupBtn = document.getElementById("closePopupBtn");

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
      document.getElementById("serial-number").value = decodedText;
      hidePopup();
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

  // Function to stop the scanner
  function stopScanner() {
    if (html5QrcodeScanner) {
      try {
        html5QrcodeScanner.clear();
      } catch (error) {
        console.error("Error stopping scanner:", error);
      }
    }
  }

  // Event listeners for main popup
  if (showPopupBtn) {
    showPopupBtn.addEventListener("click", function (event) {
      event.preventDefault();
      event.stopPropagation();
      showPopup();
    });
  }

  if (closePopupBtn) {
    closePopupBtn.addEventListener("click", function (event) {
      event.preventDefault();
      event.stopPropagation();
      hidePopup();
    });
  }

  // Close popup when clicking outside the popup content
  if (overlayContainer) {
    overlayContainer.addEventListener("click", (event) => {
      if (event.target === overlayContainer) {
        hidePopup();
      }
    });
  }

  // Camera handling variables
  let stream = null;
  let selectedCamera = null;
  const cameraPreview = document.getElementById("camera-preview");
  const photoCanvas = document.getElementById("photo-canvas");
  const capturedPhoto = document.getElementById("captured-photo");
  const cameraSelect = document.getElementById("camera-select");
  const captureBtn = document.getElementById("capture-btn");
  const retakeBtn = document.getElementById("retake-btn");

  // Function to get available cameras
  async function getCameras() {
    try {
      const devices = await navigator.mediaDevices.enumerateDevices();
      const videoDevices = devices.filter(
        (device) => device.kind === "videoinput"
      );

      if (cameraSelect) {
        // Clear existing options
        cameraSelect.innerHTML = '<option value="">Select Camera</option>';

        // Add cameras to select element
        videoDevices.forEach((device) => {
          const option = document.createElement("option");
          option.value = device.deviceId;
          option.text = device.label || `Camera ${cameraSelect.length + 1}`;
          cameraSelect.appendChild(option);
        });
      }
    } catch (error) {
      console.error("Error getting cameras:", error);
      alert(
        "Error accessing cameras. Please make sure you have granted camera permissions."
      );
    }
  }

  // Function to start camera stream
  async function startCamera(deviceId = null) {
    if (stream) {
      stream.getTracks().forEach((track) => track.stop());
    }

    const constraints = {
      video: deviceId ? { deviceId: { exact: deviceId } } : true,
    };

    try {
      stream = await navigator.mediaDevices.getUserMedia(constraints);
      if (cameraPreview) {
        cameraPreview.srcObject = stream;
        cameraPreview.style.display = "block";
      }
      if (capturedPhoto) {
        capturedPhoto.style.display = "none";
      }
      if (captureBtn) {
        captureBtn.style.display = "block";
      }
      if (retakeBtn) {
        retakeBtn.style.display = "none";
      }
    } catch (error) {
      console.error("Error accessing camera:", error);
      alert(
        "Error accessing camera. Please make sure you have granted camera permissions."
      );
    }
  }

  // Initialize camera if elements exist
  if (cameraPreview && cameraSelect) {
    getCameras();

    // Camera select change handler
    cameraSelect.addEventListener("change", (e) => {
      if (e.target.value) {
        startCamera(e.target.value);
        selectedCamera = e.target.value;
      }
    });
  }

  // Capture photo handler
  if (captureBtn && photoCanvas && cameraPreview && capturedPhoto) {
    captureBtn.addEventListener("click", () => {
      const context = photoCanvas.getContext("2d");
      photoCanvas.width = cameraPreview.videoWidth;
      photoCanvas.height = cameraPreview.videoHeight;
      context.drawImage(
        cameraPreview,
        0,
        0,
        photoCanvas.width,
        photoCanvas.height
      );

      // Display captured photo
      capturedPhoto.src = photoCanvas.toDataURL("image/jpeg");
      cameraPreview.style.display = "none";
      capturedPhoto.style.display = "block";
      captureBtn.style.display = "none";
      if (retakeBtn) {
        retakeBtn.style.display = "block";
      }
    });
  }

  // Retake photo handler
  if (retakeBtn) {
    retakeBtn.addEventListener("click", () => {
      startCamera(selectedCamera);
    });
  }

  const borrowerTypeSelect = document.getElementById("borrower-type");
  const receiverIdInput = document.getElementById("receiver-id");
  const idLabel = document.querySelector("#form-div-id label");
  const departmentSelect = document.getElementById("department");
  const firstNameInput = document.getElementById("first-name");
  const lastNameInput = document.getElementById("last-name");
  const middleNameInput = document.getElementById("middle-name");
  const suffixInput = document.getElementById("suffix-name");
  const genderSelect = document.getElementById("gender-select");
  const sectionInput = document.getElementById("section");
  const contactNumberInput = document.getElementById("contact-number");
  const emailInput = document.getElementById("email");
  const addressInput = document.getElementById("address");

  // Function to populate department dropdown on page load
  function populateDepartments() {
    fetch("../php/get_departments.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          data.departments.forEach((dept) => {
            const option = document.createElement("option");
            option.value = dept.department_id;
            option.textContent = dept.department_name;
            departmentSelect.appendChild(option);
          });
        } else {
          console.error("Error fetching departments:", data.message);
        }
      })
      .catch((error) => {
        console.error("Error fetching departments:", error);
      });
  }

  populateDepartments();

  borrowerTypeSelect.addEventListener("change", function () {
    const selectedType = this.value;
    if (selectedType === "student") {
      idLabel.textContent = "Student ID";
      document.querySelector('label[for="Middle Name"]').textContent =
        "Middle Name";
    } else if (selectedType === "employee") {
      idLabel.textContent = "Employee ID";
      document.querySelector('label[for="Middle Name"]').textContent =
        "Middle Initial";
    }
    // Clear fields when borrower type changes
    receiverIdInput.value = "";
    firstNameInput.value = "";
    lastNameInput.value = "";
    middleNameInput.value = "";
    suffixInput.value = "";
    genderSelect.value = "";
    sectionInput.value = "";
    contactNumberInput.value = "";
    emailInput.value = "";
    addressInput.value = "";
  });

  receiverIdInput.addEventListener("blur", function () {
    const receiverId = this.value.trim();
    const borrowerType = borrowerTypeSelect.value;

    if (receiverId && borrowerType) {
      fetch(
        `../php/get_borrower_info.php?borrower_type=${borrowerType}&receiver_id=${encodeURIComponent(
          receiverId
        )}`
      )
        .then((response) => response.json())
        .then((data) => {
          if (data.success && data.borrower) {
            const borrower = data.borrower;
            firstNameInput.value = borrower.first_name || "";
            lastNameInput.value = borrower.last_name || "";
            middleNameInput.value = borrower.middle_name || "";
            suffixInput.value = borrower.suffix || "";
            if (borrower.gender) {
              genderSelect.value =
                borrower.gender.charAt(0).toUpperCase() +
                borrower.gender.slice(1).toLowerCase();
            } else {
              genderSelect.value = "";
            }
            departmentSelect.value = borrower.department_id || "";
            sectionInput.value = borrower.section || "";

            // Handle contact number and address based on borrower type
            if (borrowerType === "student") {
              contactNumberInput.value = borrower.contact_number || "";
              addressInput.value = borrower.stud_address || "";
            } else if (borrowerType === "employee") {
              contactNumberInput.value = borrower.emp_contact_number || "";
              addressInput.value = borrower.emp_address || "";
            }

            emailInput.value = borrower.email || "";
          } else if (data.success && !data.borrower) {
            alert(`No ${borrowerType} found with ID: ${receiverId}`);
            // Optionally clear other fields here
            firstNameInput.value = "";
            lastNameInput.value = "";
            middleNameInput.value = "";
            suffixInput.value = "";
            genderSelect.value = "";
            sectionInput.value = "";
            contactNumberInput.value = "";
            emailInput.value = "";
            addressInput.value = "";
          } else {
            console.error("Error fetching borrower info:", data.message);
            alert("Error fetching borrower information.");
          }
        })
        .catch((error) => {
          console.error("Error fetching borrower info:", error);
          alert("Error fetching borrower information.");
        });
    }
  });

  const serialNumberInput = document.getElementById("serial-number");
  const boxNumberInput = document.getElementById("box-number");
  const accountableSelect = document.getElementById("accountable-select");
  const itemDescSelect = document.getElementById("item-desc");
  const bagTypeSelect = document.getElementById("bag-type");
  const wBagCheckbox = document.getElementById("wBagCheckbox");
  const receivedDateInput = document.getElementById("date");
  const imageUploadInput = document.getElementById("image_uploads");
  const submitButton = document.getElementById("submit");

  // Function to populate the Accountable dropdown
  function populateAccountableDropdown() {
    fetch("../php/get_employees.php")
      .then((response) => response.json())
      .then((data) => {
        // The data is now directly the array of employees
        data.forEach((employee) => {
          const option = document.createElement("option");
          option.value = employee.emp_rec_id; // Use emp_rec_id as the value
          option.textContent = `${employee.emp_fname} ${employee.emp_lname}`;
          accountableSelect.appendChild(option);
        });
      })
      .catch((error) => {
        console.error("Error fetching employees:", error);
        alert("Failed to load employee data.");
      });
  }

  // Function to populate the Item Description dropdown
  function populateItemDescriptionDropdown() {
    fetch("../php/get_item_descriptions.php")
      .then((response) => response.json())
      .then((data) => {
        data.forEach((item) => {
          const option = document.createElement("option");
          option.value = item.item_desc_id; // Use item_desc_id as the value
          option.textContent = item.item_name;
          itemDescSelect.appendChild(option);
        });
      })
      .catch((error) => {
        console.error("Error fetching item descriptions:", error);
        alert("Failed to load item descriptions.");
      });
  }

  // Function to populate the Bag dropdown
  function populateBagDropdown() {
    fetch("../php/get_bag_items.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          data.bags.forEach((bag) => {
            const option = document.createElement("option");
            option.value = bag.item_desc_id; // Use item_desc_id
            option.textContent = bag.item_name;
            bagTypeSelect.appendChild(option);
          });
        } else {
          console.error("Error fetching bags:", data.message);
          alert("Failed to load bag data.");
        }
      })
      .catch((error) => {
        console.error("Error fetching bags:", error);
        alert("Failed to load bag data.");
      });
  }

  // Event listener for serial number input
  serialNumberInput.addEventListener("blur", function () {
    const serialNumber = this.value.trim();

    if (serialNumber) {
      fetch(
        `../php/get_item_info.php?serial_number=${encodeURIComponent(
          serialNumber
        )}`
      )
        .then((response) => response.json())
        .then((data) => {
          if (data.success && data.item) {
            boxNumberInput.value = data.item.box_no || "";
            itemDescSelect.value = data.item.item_desc_id || "";
            // Populate the Accountable dropdown.
            if (data.item.accountable_id) {
              accountableSelect.value = data.item.accountable_id;
            } else {
              accountableSelect.value = "";
            }
          } else if (data.success && !data.item) {
            alert("No item found with this serial number.");
            boxNumberInput.value = "";
            itemDescSelect.value = "";
            accountableSelect.value = "";
          } else {
            console.error("Error fetching item info:", data.message);
            alert("Error fetching item information.");
          }
        })
        .catch((error) => {
          console.error("Error fetching item info:", error);
          alert("Error fetching item information.");
        });
    }
  });

  // Set received date to today
  const today = new Date().toISOString().split("T")[0];
  receivedDateInput.value = today;

  // Event listener for the "With Bag" checkbox
  wBagCheckbox.addEventListener("change", function () {
    bagTypeSelect.disabled = !this.checked;
    if (!this.checked) {
      bagTypeSelect.value = ""; // Clear selection if checkbox is unchecked
    }
  });

  // Populate dropdowns on page load
  populateItemDescriptionDropdown();
  populateAccountableDropdown();
  populateBagDropdown();

  // --- Submit Button Functionality ---
  submitButton.addEventListener("click", async function (event) {
    event.preventDefault();

    // Get form data
    const borrowerType = borrowerTypeSelect.value;
    const receiverId = receiverIdInput.value;
    const serialNumber = serialNumberInput.value;
    const accountableId = accountableSelect.value;
    const receivedDate = receivedDateInput.value;
    const hasBag = wBagCheckbox.checked;
    const bagItemDescId = bagTypeSelect.value;

    // Create FormData object
    const formData = new FormData();
    formData.append("borrower_type", borrowerType);
    formData.append("receiver_id", receiverId);
    formData.append("serial_no", serialNumber);
    formData.append("accountable-select", accountableId);
    formData.append("date", receivedDate);
    formData.append("wBagCheckbox", hasBag ? "1" : "0");
    formData.append("bag-type", bagItemDescId);

    // Add captured photo if available
    if (capturedPhoto.style.display !== "none") {
      // Convert canvas to blob
      const photoBlob = await new Promise((resolve) => {
        photoCanvas.toBlob(resolve, "image/jpeg", 0.8);
      });
      formData.append("image_uploads", photoBlob, "captured_photo.jpg");
    }

    try {
      const response = await fetch("../php/add_borrower.php", {
        method: "POST",
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        alert("Gadget distribution record updated successfully!");
        // Reset form and camera
        document.querySelector(".main-form").reset();
        receivedDateInput.value = new Date().toISOString().split("T")[0];
        startCamera(selectedCamera); // Reset camera view
      } else {
        alert(`Error updating record: ${data.message}`);
      }
    } catch (error) {
      console.error("Error submitting form:", error);
      alert("An error occurred while submitting the form.");
    }
  });

  const generateReceiptBtn = document.querySelector("#generate-receipt");

  // Generate Receipt functionality
  generateReceiptBtn.addEventListener("click", (event) => {
    event.preventDefault();
    console.log("Generate Receipt button clicked");

    // Collect borrower information
    const firstName = document.getElementById("first-name").value;
    const lastName = document.getElementById("last-name").value;
    const middleName = document.getElementById("middle-name").value;
    const suffix = document.getElementById("suffix-name").value;

    console.log("Collected borrower info:", {
      firstName,
      lastName,
      middleName,
      suffix,
    });

    // Format the full name
    const fullName = [
      lastName.toUpperCase(),
      firstName.toUpperCase(),
      middleName ? middleName.charAt(0).toUpperCase() + "." : "",
      suffix ? suffix.toUpperCase() : "",
    ]
      .filter(Boolean)
      .join(" ");

    // Get department and section
    const department =
      document.getElementById("department").options[
        document.getElementById("department").selectedIndex
      ].text;
    const section = document.getElementById("section").value;

    // Get contact information
    const email = document.getElementById("email").value;
    const contact = document.getElementById("contact-number").value;

    // Get item information
    const serialNumber = document.getElementById("serial-number").value;
    const boxNumber = document.getElementById("box-number").value;
    const itemName =
      document.getElementById("item-desc").options[
        document.getElementById("item-desc").selectedIndex
      ].text;
    const receivedDate = document.getElementById("date").value;

    // Check if with bag
    const withBag = document.getElementById("wBagCheckbox").checked;
    const bagID = withBag ? document.getElementById("bag-type").value : "";
    const bagItemName = withBag
      ? document.getElementById("bag-type").options[
          document.getElementById("bag-type").selectedIndex
        ].text
      : "";

    let bagDesc = ""; // Declare bagDesc in the outer scope

    function getBagDesc(bagID) {
      return fetch("../php/get_bag_items.php")
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            let foundBagDesc = "";
            data.bags.forEach((bag) => {
              if (bag.item_desc_id == bagID) {
                foundBagDesc = bag.item_specs;
              }
            });
            return foundBagDesc; // Return the bagDesc from the promise
          } else {
            console.error("Error fetching bags:", data.message);
            alert("Failed to pass bag data..");
            return ""; // Return an empty string on error
          }
        })
        .catch((error) => {
          console.error("Error fetching bags:", error);
          alert("Failed to load bag data.");
          return ""; // Return an empty string on error
        });
    }

    // Create a function to handle the rest of the receipt generation *after* bag details are fetched
    async function generateReceipt() {
      if (bagID) {
        console.log("Fetching bag description for bagID:", bagID);
        bagDesc = await getBagDesc(bagID);
        console.log("Retrieved bag description:", bagDesc);
      }

      // Create a serial number based unique identifier for QR code content
      const qrContent = `Item Name: ${itemName}
Box Number: ${boxNumber || "N/A"}
Serial No: ${serialNumber || "N/A"}
Purchase Date: ${receivedDate || "N/A"}`;

      console.log("QR Code Content:", qrContent); // Add debug logging

      // Build full item description
      const itemDescription = `${itemName} 
      ${serialNumber ? "SN: " + serialNumber : ""}
      ${boxNumber ? "BOX NUMBER: " + boxNumber : ""}`;

      console.log("Item description:", itemDescription);

      // Format the date for display
      const formattedDate = receivedDate
        ? new Date(receivedDate).toLocaleDateString("en-US", {
            month: "numeric",
            day: "numeric",
            year: "numeric",
          })
        : "";

      // Prepare receipt data
      const receiptData = {
        name: fullName,
        office: department,
        section: section,
        email: email,
        contact: contact,
        qrContent: qrContent,
        bagID: bagID,
        bagDesc: bagDesc,
        bagItemName: bagItemName,
        items: [
          {
            description: itemDescription,
            quantity: "1 UNIT/S",
            receivedDate: formattedDate,
            returnedDate: "",
            remarks: "",
          },
        ],
      };

      console.log("Receipt data being sent:", receiptData);

      // Create a FormData object to handle file uploads
      const formData = new FormData();

      // Append the JSON data
      formData.append("receiptData", JSON.stringify(receiptData));

      // Debug log the data being sent
      console.log(
        "Receipt Data being sent:",
        JSON.parse(formData.get("receiptData"))
      );

      // Add captured photo if available
      if (capturedPhoto && capturedPhoto.style.display !== "none") {
        console.log("Adding captured photo to form data");
        const photoBlob = await new Promise((resolve) => {
          photoCanvas.toBlob(resolve, "image/jpeg", 0.8);
        });
        formData.append("borrower_photo", photoBlob, "captured_photo.jpg");
      }

      try {
        console.log("Sending request to generate_receipt.php");
        const response = await fetch("../php/generate_receipt.php", {
          method: "POST",
          body: formData,
        });

        console.log("Response status:", response.status);
        console.log(
          "Response headers:",
          Object.fromEntries(response.headers.entries())
        );

        const contentType = response.headers.get("content-type");
        console.log("Response content type:", contentType);

        if (!response.ok) {
          const text = await response.text();
          console.error("Server error response:", text);
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        if (contentType && contentType.includes("application/pdf")) {
          const blob = await response.blob();
          const url = window.URL.createObjectURL(blob);
          window.open(url, "_blank");
        } else if (contentType && contentType.includes("application/json")) {
          const json = await response.json();
          console.error("Server error:", json);
          alert(json.error || "Error generating receipt. Please try again.");
        } else {
          const text = await response.text();
          console.error("Unexpected response:", text);
          alert("Error generating receipt. Please try again.");
        }
      } catch (error) {
        console.error("Error generating receipt:", error);
        alert("Error generating receipt. Please try again.");
      }
    }

    // Validate required fields before generating receipt
    if (
      !fullName ||
      !department ||
      !section ||
      !email ||
      !contact ||
      !serialNumber
    ) {
      alert(
        "Please fill in all required fields before generating the receipt."
      );
      return;
    }

    generateReceipt();
  });
});
