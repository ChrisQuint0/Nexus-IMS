document.addEventListener("DOMContentLoaded", () => {
  const downloadItemsTemplateButton = document.getElementById(
    "downloadItemsTemplateButton"
  );

  if (downloadItemsTemplateButton) {
    downloadItemsTemplateButton.addEventListener("click", (event) => {
      event.preventDefault(); // Prevent the default link behavior

      // Trigger download for item_csv_column_descriptions.pdf
      const pdfLink = document.createElement("a");
      pdfLink.href = "../resources/item_csv_column_descriptions.pdf";
      pdfLink.download = "item_csv_column_descriptions.pdf";
      document.body.appendChild(pdfLink);
      pdfLink.click();
      document.body.removeChild(pdfLink);

      // Trigger download for item_csv_template.csv after a short delay
      setTimeout(() => {
        const csvLink = document.createElement("a");
        csvLink.href = "../resources/item_csv_template.csv";
        csvLink.download = "item_csv_template.csv";
        document.body.appendChild(csvLink);
        csvLink.click();
        document.body.removeChild(csvLink);
      }, 250); // Adjust the delay (in milliseconds) if needed
    });
  }

  const itemsCSVFile = document.getElementById("itemsCSVFile");
  const uploadItemsCSVButton = document.querySelector(
    "#upload-box-items .filled-btn"
  ); // Target the specific upload button for items

  if (uploadItemsCSVButton) {
    uploadItemsCSVButton.addEventListener("click", () => {
      itemsCSVFile.click();
    });
  }

  if (itemsCSVFile) {
    itemsCSVFile.addEventListener("change", (event) => {
      const file = event.target.files[0];
      if (!file) {
        alert("Please select a CSV file to upload items.");
        return;
      }
      processCsvFile(file);
    });
  }

  async function fetchItemNames() {
    try {
      const response = await fetch("../php/get_item_descriptions.php");
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      const data = await response.json();
      return data;
    } catch (error) {
      console.error("Error fetching item names:", error);
      alert("Error fetching item names from the server.");
      return [];
    }
  }

  async function fetchEmployeeNames() {
    try {
      const response = await fetch("../php/get_employee_names.php");
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      const data = await response.json();
      return data;
    } catch (error) {
      console.error("Error fetching employee names:", error);
      alert("Error fetching employee names from the server.");
      return [];
    }
  }

  async function processCsvFile(file) {
    const reader = new FileReader();

    reader.onload = async (event) => {
      const csvText = event.target.result;
      const parsedResult = Papa.parse(csvText, {
        header: true,
        skipEmptyLines: true,
        trimHeaders: true,
      });

      if (parsedResult.errors.length > 0) {
        console.error("CSV Parsing Errors:", parsedResult.errors);
        alert("Error parsing CSV file. Please check the file format.");
        return;
      }

      if (parsedResult.data && parsedResult.data.length > 0) {
        // Remove empty rows at the end if any
        let data = parsedResult.data;
        while (
          data.length > 0 &&
          Object.values(data[data.length - 1]).every((v) => v === "")
        ) {
          data.pop();
        }
        validateAndUploadCsvData(data);
      } else {
        alert("The CSV file is empty or contains no valid data.");
      }
    };

    reader.onerror = () => {
      alert("Error reading the CSV file.");
    };

    reader.readAsText(file);
  }

  async function validateAndUploadCsvData(data) {
    const itemNames = await fetchItemNames();
    const employees = await fetchEmployeeNames();
    const validationErrors = [];
    const validItemsForUpload = [];

    for (const row of data) {
      const {
        "Box No": boxNoRaw,
        "Item Name": itemNameRaw,
        "Serial No": serialNo,
        Accountable: accountableRaw,
        "Purchase Date": purchaseDateRaw,
      } = row;

      const box_no = parseInt(boxNoRaw);
      if (isNaN(box_no)) {
        validationErrors.push(
          `Invalid Box No: "${boxNoRaw}". Must be a number.`
        );
        continue;
      }

      const itemName = itemNameRaw ? itemNameRaw.trim() : "";
      const foundItem = itemNames.find(
        (item) => item.item_name.trim() === itemName
      );
      if (!foundItem) {
        validationErrors.push(
          `Invalid Item Name: "${itemName}". Does not exist in the system.`
        );
        continue;
      }

      const accountable = accountableRaw ? accountableRaw.trim() : "";
      const matchingEmployees = employees.filter(
        (emp) => emp.fullNameFirstLastOnly.trim() === accountable
      );
      if (matchingEmployees.length !== 1) {
        validationErrors.push(
          `Invalid Accountable: "${accountable}". Employee not found or multiple matches.`
        );
        continue;
      }

      const purchaseDate = purchaseDateRaw ? purchaseDateRaw.trim() : null;
      if (purchaseDate && isNaN(new Date(purchaseDate).getTime())) {
        validationErrors.push(
          `Invalid Purchase Date: "${purchaseDateRaw}". Must be a valid date format (YYYY-MM-DD, etc.).`
        );
        continue;
      }

      validItemsForUpload.push({
        box_no: box_no,
        item_desc_id: foundItem.item_desc_id,
        serial_no: serialNo ? serialNo.trim() : null,
        accountable_id: matchingEmployees[0].emp_rec_id,
        purchase_date: purchaseDate,
      });
    }

    if (validationErrors.length > 0) {
      alert(
        "Data validation failed. Please correct the following errors:\n" +
          validationErrors.join("\n")
      );
      console.error("Item CSV Validation Errors:", validationErrors);
    } else if (validItemsForUpload.length > 0) {
      uploadItemsToServer(validItemsForUpload);
    } else {
      alert("No valid item data found in the CSV file.");
    }
  }

  async function uploadItemsToServer(items) {
    try {
      const response = await fetch("../php/upload_items_csv.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(items),
      });

      const result = await response.json();

      if (result.success) {
        alert(`Successfully uploaded ${items.length} items.`);
      } else {
        let errorMessage = "Failed to upload items. ";
        if (result.message) {
          errorMessage += result.message;
        } else if (result.error) {
          errorMessage += result.error;
        }
        alert(errorMessage);
        console.error("Item CSV Upload Error:", result);
      }
    } catch (error) {
      alert("Upload Unsuccessful. Check for Duplicates or Missing Data");
    }
  }
});
