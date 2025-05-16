(function () {
  // Get references to the item table elements
  const itemDataTable = document
    .getElementById("item-data-table")
    .querySelector("tbody");
  const itemSearchInput = document.getElementById("item-table-searchbar");
  let allItemData = [];
  let allItemDescriptions = []; // To store fetched item descriptions

  // Function to fetch item data and item descriptions from the PHP backend
  function fetchItemData() {
    Promise.all([
      fetch("../php/get_all_cols_item.php").then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      }),
      fetch("../php/get_all_item_desc.php").then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      }),
    ])
      .then(([itemData, itemDescData]) => {
        allItemData = itemData.items; // Assuming your PHP returns { items: [] }
        allItemDescriptions = itemDescData.item_descriptions; // Assuming your PHP returns { item_descriptions: [] }
        console.log("All Item Descriptions:", allItemDescriptions);
        populateItemTable(allItemData);
      })
      .catch((error) => {
        console.error("Error fetching item data:", error);
        alert("Failed to load item data.");
      });
  }

  // Function to populate the item table
  function populateItemTable(items) {
    itemDataTable.innerHTML = "";

    items.forEach((item) => {
      const row = itemDataTable.insertRow();

      // Action column
      const actionCell = row.insertCell();
      const editButton = createStyledButton("Edit");
      const saveButton = createStyledButton("Save", "none"); // Initially hidden
      const deleteButton = createStyledButton(
        "Delete",
        "inline-block",
        "#0e2f56",
        "white",
        "10px"
      );

      actionCell.appendChild(editButton);
      actionCell.appendChild(saveButton);
      actionCell.appendChild(deleteButton);

      console.log(
        "Initial Load - Item ID:",
        item.item_id,
        "Item Desc ID:",
        item.item_desc_id,
        "Current Item Name:",
        getItemName(item.item_desc_id)
      );

      // Add data cells with input fields or dropdowns
      createTableCell(row, item.box_no, "box_no", false); // Initially readonly
      createItemNameDropdownCell(
        row,
        item.item_desc_id,
        getItemName(item.item_desc_id)
      );
      createTableCell(row, item.serial_no, "serial_no", false); // Initially readonly
      createPurchaseDateCell(
        row,
        formatDate(item.purchase_date),
        "purchase_date"
      );

      // Add event listeners
      editButton.addEventListener("click", () =>
        toggleEditRow(row, editButton, saveButton)
      );
      saveButton.addEventListener("click", () =>
        saveItemData(row, editButton, saveButton, item.item_id)
      );
      deleteButton.addEventListener("click", () =>
        confirmDeleteItem(item.item_id, row)
      );
    });
  }

  // Function to get item name from item_desc_id
  function getItemName(itemDescId) {
    const parsedItemId = parseInt(itemDescId);
    const itemDesc = allItemDescriptions.find(
      (desc) => parseInt(desc.item_desc_id) === parsedItemId
    );
    return itemDesc ? itemDesc.item_name : "";
  }

  // Function to format date to YYYY-MM-DD for input[type="date"]
  function formatDate(dateTimeString) {
    if (!dateTimeString) return "";
    const datePart = dateTimeString.split(" ")[0];
    return datePart;
  }

  // Function to create a styled button (reused from your other script)
  function createStyledButton(
    text,
    display = "inline-block",
    backgroundColor = "white",
    color = "#0e2f56",
    marginLeft = ""
  ) {
    const button = document.createElement("button");
    button.textContent = text;
    button.style.padding = "5px 20px";
    button.style.borderRadius = "15px";
    button.style.border = "solid 1px #0e2f56";
    button.style.fontSize = "15px";
    button.style.color = color;
    button.style.backgroundColor = backgroundColor;
    button.style.marginLeft = marginLeft;
    button.style.display = display;
    return button;
  }

  // Function to apply common input styles (reused)
  function applyInputStyles(input) {
    input.style.fontSize = "18px";
    input.style.fontFamily = "Nunito-Regular";
    input.style.border = "none";
    input.style.background = "transparent";
  }

  // Function to create a standard table cell with an input field
  function createTableCell(row, value, name, editable = false) {
    const cell = row.insertCell();
    const input = document.createElement("input");
    input.type = "text";
    input.name = name;
    input.value = value || "";
    input.readOnly = !editable;
    applyInputStyles(input);
    input.style.pointerEvents = editable ? "auto" : "none"; // Control interaction
    input.tabIndex = editable ? 0 : -1; // Control focus
    if (editable) {
      input.style.border = "solid 1px #0e2f56";
    }
    cell.appendChild(input);
  }

  // Function to create the "Item Name" dropdown cell
  function createItemNameDropdownCell(row, currentItemDescId, currentItemName) {
    const cell = row.insertCell();
    const select = document.createElement("select");
    select.name = "item_desc_id";
    select.style.pointerEvents = "none";
    applyInputStyles(select);
    select.style.padding = "0";
    select.tabIndex = -1;

    // Create default option
    const defaultOption = document.createElement("option");
    defaultOption.value = currentItemDescId;
    defaultOption.textContent = currentItemName;
    defaultOption.selected = true;
    select.appendChild(defaultOption);

    // Add other item name options
    allItemDescriptions.forEach((desc) => {
      if (desc.item_desc_id !== parseInt(currentItemDescId)) {
        const option = document.createElement("option");
        option.value = desc.item_desc_id;
        option.textContent = desc.item_name;
        select.appendChild(option);
      }
    });

    cell.appendChild(select);
  }

  // Function to create the "Purchase Date" cell with input[type="date"]
  function createPurchaseDateCell(row, purchaseDate, name) {
    const cell = row.insertCell();
    const input = document.createElement("input");
    input.type = "date";
    input.name = name;
    input.value = purchaseDate || "";
    input.readOnly = true;
    applyInputStyles(input);
    input.style.pointerEvents = "none"; // Initially non-interactive
    input.tabIndex = -1; // Initially non-focusable
    cell.appendChild(input);
  }

  // Function to toggle edit state of a row
  function toggleEditRow(row, editButton, saveButton) {
    const inputs = row.querySelectorAll(
      "input[type='text'], input[type='date']"
    );
    const itemNameSelect = row.querySelector("select[name='item_desc_id']");

    inputs.forEach((input) => {
      input.readOnly = !input.readOnly;
      input.style.border = input.readOnly ? "none" : "solid 1px #0e2f56";
      input.style.pointerEvents = input.readOnly ? "none" : "auto";
      input.tabIndex = input.readOnly ? -1 : 0;
    });

    toggleDropdownEdit(itemNameSelect);

    editButton.style.display = "none";
    saveButton.style.display = "inline-block";
  }

  // Function to toggle dropdown edit state (reused)
  function toggleDropdownEdit(selectElement) {
    if (selectElement) {
      selectElement.style.pointerEvents =
        selectElement.style.pointerEvents === "none" ? "auto" : "none";
      selectElement.style.border =
        selectElement.style.pointerEvents === "none"
          ? "none"
          : "solid 1px #0e2f56";
      selectElement.tabIndex =
        selectElement.style.pointerEvents === "none" ? -1 : 0;
    }
  }

  // Function to handle saving item data
  function saveItemData(row, editButton, saveButton, itemId) {
    const inputs = row.querySelectorAll(
      "input[type='text'], input[type='date']"
    );
    const itemNameSelect = row.querySelector("select[name='item_desc_id']");
    const updatedData = {};

    inputs.forEach((input) => {
      updatedData[input.name] = input.value;
    });

    if (itemNameSelect) {
      updatedData.item_desc_id = itemNameSelect.value;
    }

    updatedData.item_id = itemId;

    fetch("../php/update_item.php", {
      // Create this PHP file
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(updatedData),
    })
      .then((response) => {
        if (!response.ok) {
          return response.text().then((text) => {
            throw new Error(
              `HTTP error! status: ${response.status}, body: ${text}`
            );
          });
        }
        return response.json();
      })
      .then((data) => {
        console.log("Item update success:", data);
        inputs.forEach((input) => {
          input.readOnly = true;
          input.style.border = "none";
          input.style.pointerEvents = "none";
          input.tabIndex = -1;
        });
        toggleDropdownEdit(itemNameSelect);

        // Update displayed values
        const boxNoInput = row.cells[1].querySelector("input");
        if (boxNoInput) boxNoInput.readOnly = true;
        const serialNoInput = row.cells[3].querySelector("input");
        if (serialNoInput) serialNoInput.readOnly = true;
        const purchaseDateInput =
          row.cells[4].querySelector('input[type="date"]');
        if (purchaseDateInput) {
          purchaseDateInput.readOnly = true;
          purchaseDateInput.style.pointerEvents = "none";
          purchaseDateInput.tabIndex = -1;
        }

        editButton.style.display = "inline-block";
        saveButton.style.display = "none";
      })
      .catch((error) => {
        console.error("Error updating item data:", error);
        alert("Failed to save item data: " + error);
      });
  }

  // Function to confirm delete action for items
  // Function to confirm delete action for items
  function confirmDeleteItem(itemId, row) {
    if (confirm(`Are you sure you want to delete item with ID: ${itemId}?`)) {
      fetch("../php/delete_item.php?item_id=" + itemId, {
        method: "DELETE",
      })
        .then((response) => {
          if (!response.ok) {
            return response.json().then((errorData) => {
              throw new Error(errorData.error || "Failed to delete item.");
            });
          }
          return response.json();
        })
        .then((data) => {
          console.log("Item delete response:", data);
          if (data.message) {
            alert(data.message); // Optionally show a success message
            row.remove(); // Only remove the row on successful deletion
          } else if (data.error) {
            alert(`Delete Failed: ${data.error}`); // Show the error message from the backend
          } else {
            alert("Delete operation completed."); // Generic success message if no specific message
            row.remove();
          }
        })
        .catch((error) => {
          console.error("Error deleting item:", error);
          alert(`Delete Failed: ${error}`); // Display the error message to the user
        });
    }
  }
  // Function to handle the search for items
  function handleItemSearch() {
    const searchTerm = itemSearchInput.value.toLowerCase();
    const filteredItems = allItemData.filter((item) => {
      const itemName = getItemName(item.item_desc_id).toLowerCase();
      return (
        String(item.box_no).toLowerCase().startsWith(searchTerm) ||
        itemName.includes(searchTerm) ||
        (item.serial_no && item.serial_no.toLowerCase().includes(searchTerm))
      );
    });
    populateItemTable(filteredItems);
  }

  // Add event listener to the search input
  itemSearchInput.addEventListener("input", handleItemSearch);

  // Call the function to fetch and populate item data when the script loads
  fetchItemData();
})();
