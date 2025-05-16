(function () {
  // Get references to the bag item table elements
  const bagItemDataTable = document
    .getElementById("bag-item-data-table")
    .querySelector("tbody");
  const bagItemSearchInput = document.getElementById(
    "bag-item-table-searchbar"
  );
  let allBagItemData = [];
  let allBagItemNames = []; // To store the bag item names for the dropdown

  // Function to fetch bag item data from the PHP backend
  function fetchBagItemData() {
    fetch("../php/get_all_cols_bag_item.php")
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        allBagItemData = data.bags;
        allBagItemNames = data.bag_item_names; // Get bag item names from the same response
        console.log("All Bag Item Data:", allBagItemData);
        console.log("All Bag Item Names:", allBagItemNames);
        populateBagItemTable(allBagItemData);
      })
      .catch((error) => {
        console.error("Error fetching bag item data:", error);
        alert("Failed to load bag item data.");
      });
  }

  // Function to populate the bag item table
  function populateBagItemTable(bags) {
    bagItemDataTable.innerHTML = "";

    bags.forEach((bag) => {
      const row = bagItemDataTable.insertRow();

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

      // Add data cells
      createTableCell(row, bag.box_no, "box_no", false); // Initially readonly
      createTableCell(row, bag.item_name, "item_name", false); // Initially readonly (will be replaced by dropdown on edit)
      createTableCell(row, bag.serial_no, "serial_no", false); // Initially readonly
      createPurchaseDateCell(
        row,
        formatDate(bag.purchase_date),
        "purchase_date"
      );

      // Add event listeners
      editButton.addEventListener("click", () =>
        toggleEditRow(
          row,
          editButton,
          saveButton,
          bag.item_name,
          bag.item_desc_id
        )
      );
      saveButton.addEventListener("click", () =>
        saveBagItemData(row, editButton, saveButton, bag.bag_item_id)
      );
      deleteButton.addEventListener("click", () =>
        confirmDeleteBagItem(bag.bag_item_id, row)
      );
    });
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
    let cellContent;
    if (editable && name === "item_name") {
      // Create a select dropdown
      const select = document.createElement("select");
      select.name = "item_desc_id"; // Important: Change the name to item_desc_id
      applyInputStyles(select);
      select.style.border = "solid 1px #0e2f56";
      select.style.pointerEvents = "auto";
      select.tabIndex = 0;
      cellContent = select;
    } else {
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
      cellContent = input;
    }
    cell.appendChild(cellContent);
    return cell;
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

  // Function to populate the item name dropdown
  function populateItemNameDropdown(
    selectElement,
    currentItemName,
    currentItemId
  ) {
    allBagItemNames.forEach((item) => {
      const option = document.createElement("option");
      option.value = item.item_desc_id;
      option.textContent = item.item_name;
      if (item.item_name === currentItemName) {
        option.selected = true;
      }
      selectElement.appendChild(option);
    });
  }

  // Function to toggle edit state of a row
  function toggleEditRow(
    row,
    editButton,
    saveButton,
    currentItemName,
    currentItemId
  ) {
    const cells = row.querySelectorAll("td");

    // Replace the Item Name cell with a dropdown
    const itemNameCell = cells[2];
    itemNameCell.innerHTML = "";
    const select = document.createElement("select");
    select.name = "item_desc_id"; // Important: Change the name to item_desc_id
    applyInputStyles(select);
    select.style.border = "solid 1px #0e2f56";
    select.style.pointerEvents = "auto";
    select.tabIndex = 0;
    populateItemNameDropdown(select, currentItemName, currentItemId);
    itemNameCell.appendChild(select);

    // Make other relevant cells editable
    const boxNoInput = cells[1].querySelector("input");
    boxNoInput.readOnly = false;
    boxNoInput.style.border = "solid 1px #0e2f56";
    boxNoInput.style.pointerEvents = "auto";
    boxNoInput.tabIndex = 0;

    const serialNoInput = cells[3].querySelector("input");
    serialNoInput.readOnly = false;
    serialNoInput.style.border = "solid 1px #0e2f56";
    serialNoInput.style.pointerEvents = "auto";
    serialNoInput.tabIndex = 0;

    const purchaseDateInput = cells[4].querySelector("input");
    purchaseDateInput.readOnly = false;
    purchaseDateInput.style.pointerEvents = "auto";
    purchaseDateInput.tabIndex = 0;
    purchaseDateInput.type = "date";

    editButton.style.display = "none";
    saveButton.style.display = "inline-block";
  }

  // Function to handle saving bag item data
  function saveBagItemData(row, editButton, saveButton, bagItemId) {
    const inputs = row.querySelectorAll(
      "input[type='text'], input[type='date']"
    );
    const select = row.querySelector("select[name='item_desc_id']"); // Get the selected item_desc_id
    const updatedData = {};

    inputs.forEach((input) => {
      updatedData[input.name] = input.value;
    });

    updatedData.item_desc_id = select.value; // Add the selected item_desc_id
    updatedData.bag_item_id = bagItemId;

    fetch("../php/update_bag_item.php", {
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
        console.log("Bag item update success:", data);
        const cells = row.querySelectorAll("td");

        // Update the displayed item name
        const selectedOption = select.options[select.selectedIndex];
        cells[2].textContent = selectedOption.textContent;

        // Make inputs readonly again
        cells.forEach((cell) => {
          const input = cell.querySelector("input");
          if (input) {
            input.readOnly = true;
            input.style.border = "none";
            input.style.pointerEvents = "none";
            input.type = "text"; // Reset type for display
            input.tabIndex = -1;
          }
        });
        cells[4].querySelector("input").type = "text"; // Ensure date displays as text

        editButton.style.display = "inline-block";
        saveButton.style.display = "none";
      })
      .catch((error) => {
        console.error("Error updating bag item data:", error);
        alert("Failed to save bag item data: " + error);
      });
  }

  // Function to confirm delete action for bag items
  function confirmDeleteBagItem(bagItemId, row) {
    if (
      confirm(`Are you sure you want to delete bag item with ID: ${bagItemId}?`)
    ) {
      fetch("../php/delete_bag_item.php?bag_item_id=" + bagItemId, {
        method: "DELETE",
      })
        .then((response) => {
          if (!response.ok) {
            return response.json().then((errorData) => {
              throw new Error(errorData.error || "Failed to delete bag item.");
            });
          }
          return response.json();
        })
        .then((data) => {
          console.log("Bag item delete response:", data);
          if (data.message) {
            alert(data.message);
            row.remove();
          } else if (data.error) {
            alert(`Delete Failed: ${data.error}`);
          } else {
            alert("Delete operation completed.");
            row.remove();
          }
        })
        .catch((error) => {
          console.error("Error deleting bag item:", error);
          alert(`Delete Failed: ${error}`);
        });
    }
  }

  // Function to handle the search for bag items
  function handleBagItemSearch() {
    const searchTerm = bagItemSearchInput.value.toLowerCase();
    const filteredBags = allBagItemData.filter((bag) => {
      return (
        String(bag.box_no).toLowerCase().startsWith(searchTerm) ||
        (bag.item_name && bag.item_name.toLowerCase().includes(searchTerm)) ||
        (bag.serial_no && bag.serial_no.toLowerCase().includes(searchTerm))
      );
    });
    populateBagItemTable(filteredBags);
  }

  // Add event listener to the search input
  bagItemSearchInput.addEventListener("input", handleBagItemSearch);

  // Call the function to fetch and populate bag item data when the script loads
  fetchBagItemData();

  const navButtons = document.querySelectorAll(".nav-container .nav-btn");
  const tableSections = {
    "nav-stud-btn": "students-table-section",
    "nav-emp-btn": "employees-table-section",
    "nav-items-btn": "items-table-section",
    "nav-bag-items-btn": "bag-items-table-section",
    "nav-dept-btn": "depts-table-section",
    "nav-cat-btn": "cats-table-section",
    "nav-statuses-btn": "statuses-table-section",
  };

  // Function to hide all table sections
  function hideAllSections() {
    for (const sectionId of Object.values(tableSections)) {
      const section = document.getElementById(sectionId);
      if (section) {
        section.style.display = "none";
      }
    }
  }

  // Function to show a specific table section and highlight the active button
  function showSection(buttonId) {
    hideAllSections();
    const sectionId = tableSections[buttonId];
    const section = document.getElementById(sectionId);
    const activeButton = document.querySelector(
      ".nav-container .nav-btn.active"
    );
    const clickedButton = document.querySelector(`.nav-container .${buttonId}`);

    if (section) {
      section.style.display = "block";
    }

    // Remove active class from the previously active button
    if (activeButton && activeButton !== clickedButton) {
      activeButton.classList.remove("active");
    }

    // Add active class to the clicked button
    if (clickedButton) {
      clickedButton.classList.add("active");
    }
  }

  // Add event listeners to each navigation button
  navButtons.forEach((button) => {
    button.addEventListener("click", function () {
      showSection(this.classList[1]); // The second class is the button ID (e.g., nav-stud-btn)
    });
  });

  // Initially show the "Student" section as it has the "active" class
  const initialActiveButton = document.querySelector(
    ".nav-container .nav-stud-btn.active"
  );
  if (initialActiveButton) {
    showSection(initialActiveButton.classList[1]);
  } else {
    // If no active class is set by default, you might want to show a default section
    hideAllSections();
    const defaultSection = document.getElementById("students-table-section");
    if (defaultSection) {
      defaultSection.style.display = "block";
      document
        .querySelector(".nav-container .nav-stud-btn")
        .classList.add("active");
    }
  }
})();
