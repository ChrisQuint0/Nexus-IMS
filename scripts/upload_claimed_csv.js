//Upload Claimed
const downloadClaimedTemplateButton = document.getElementById(
  "downloadClaimedTemplateButton"
);

if (downloadClaimedTemplateButton) {
  downloadClaimedTemplateButton.addEventListener("click", (event) => {
    event.preventDefault(); // Prevent the default link behavior

    // Trigger download for item_csv_column_descriptions.pdf
    const pdfLink = document.createElement("a");
    pdfLink.href = "../resources/add_claimed_csv_description.pdf";
    pdfLink.download = "add_claimed_csv_description.pdf";
    document.body.appendChild(pdfLink);
    pdfLink.click();
    document.body.removeChild(pdfLink);

    // Trigger download for item_csv_template.csv after a short delay
    setTimeout(() => {
      const csvLink = document.createElement("a");
      csvLink.href = "../resources/add_claimed_template.csv";
      csvLink.download = "add_claimed_template.csv";
      document.body.appendChild(csvLink);
      csvLink.click();
      document.body.removeChild(csvLink);
    }, 250); // Adjust the delay (in milliseconds) if needed
  });
}

const uploadClaimedCSVButton = document.getElementById(
  "uploadClaimedCSVButton"
);
const claimedCSVInput = document.getElementById("claimedCSVFile");

uploadClaimedCSVButton.addEventListener("click", () => {
  claimedCSVInput.click();
});

claimedCSVInput.addEventListener("change", function (event) {
  const file = event.target.files[0];
  if (!file) return;

  Papa.parse(file, {
    header: true, // Assumes the first row is the header row
    skipEmptyLines: true,
    complete: function (results) {
      const data = results.data;
      processClaimedData(data); // Function to handle the parsed data
    },
    error: function (error) {
      alert("Error parsing CSV: " + error.message);
    },
  });
});

async function fetchCategoryByItemName(itemName) {
  try {
    const response = await fetch("../php/get_category_by_item_name.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `itemName=${encodeURIComponent(itemName)}`,
    });
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const data = await response.json();
    return data.category_id; // Assuming the PHP returns { category_id: 3 }
  } catch (error) {
    console.error("Error fetching category:", error);
    alert("Error fetching item category.");
    return null;
  }
}

async function processClaimedData(data) {
  const recordsToUpdate = [];

  for (let i = 0; i < data.length; i++) {
    const row = data[i];
    let borrowerType = row["Borrower Type"]?.trim().toLowerCase();
    let studentId = row["Student ID"]?.trim();
    let receiverId = row["Receiver ID"]?.trim();
    let itemName = row["Item Name"]?.trim();
    let serialNumber = row["Serial Number"]?.trim();
    let receivedDate = row["Received Date"]?.trim();

    let isValid = true;
    let errorMessage = "";

    if (
      !borrowerType ||
      (borrowerType !== "student" && borrowerType !== "employee")
    ) {
      isValid = false;
      errorMessage = `Row ${
        i + 2
      }: Invalid Borrower Type. Must be 'student' or 'employee'.`;
    } else if (borrowerType === "student" && !studentId) {
      isValid = false;
      errorMessage = `Row ${
        i + 2
      }: Student ID is required for student borrowers.`;
    } else if (borrowerType === "student" && receiverId) {
      isValid = false;
      errorMessage = `Row ${
        i + 2
      }: Receiver ID must be null for student borrowers.`;
    } else if (borrowerType === "employee" && !receiverId) {
      isValid = false;
      errorMessage = `Row ${
        i + 2
      }: Receiver ID is required for employee borrowers.`;
    } else if (borrowerType === "employee" && studentId) {
      isValid = false;
      errorMessage = `Row ${
        i + 2
      }: Student ID must be null for employee borrowers.`;
    } else if (!itemName) {
      isValid = false;
      errorMessage = `Row ${i + 2}: Item Name is required.`;
    } else if (!serialNumber) {
      isValid = false;
      errorMessage = `Row ${i + 2}: Serial Number is required.`;
    } else if (!receivedDate) {
      isValid = false;
      errorMessage = `Row ${i + 2}: Received Date is required.`;
    } else if (isNaN(new Date(receivedDate))) {
      isValid = false;
      errorMessage = `Row ${i + 2}: Received Date is invalid format`;
    }

    if (!isValid) {
      alert(errorMessage);
      return;
    }

    const categoryId = await fetchCategoryByItemName(itemName);
    if (categoryId === null) {
      return; // Stop if category fetch fails
    }

    const record = {
      borrowerType: borrowerType,
      studentId: studentId,
      receiverId: receiverId,
      itemName: itemName,
      serialNumber: serialNumber,
      receivedDate: receivedDate,
      categoryId: categoryId, // Include the category ID
    };
    recordsToUpdate.push(record);
  }
  sendDataToServer(recordsToUpdate);
}

function sendDataToServer(data) {
  fetch("../php/add_claimed.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(data),
  })
    .then((response) => {
      if (response.ok) {
        alert("Data uploaded successfully!");
        claimedCSVInput.value = "";
      } else {
        return response.json().then((errorData) => {
          let errorMessage = "Error uploading data: ";
          if (errorData && errorData.message) {
            errorMessage += errorData.message; // Use the error message from the server
          } else {
            errorMessage += "Server error.";
          }
          alert(errorMessage);
        });
      }
    })
    .catch((error) => {
      alert("Network error: " + error.message);
    });
}
