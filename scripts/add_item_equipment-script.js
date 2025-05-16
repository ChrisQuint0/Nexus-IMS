document.addEventListener("DOMContentLoaded", () => {
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
      document.getElementById("serial_no").value = decodedText;
      document.getElementById("result").innerHTML = `Scanned: ${decodedText}`;
      hidePopup();
    });
  }

  function stopScanner() {
    if (html5QrcodeScanner) {
      try {
        html5QrcodeScanner.clear();
      } catch (error) {
        console.error("Failed to stop scanner:", error);
      }
    }
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

  const itemNameDropdown = document.getElementById("item_name");
  const itemSpecsTextarea = document.getElementById("item-specs");
  const accountableDropdown = document.getElementById("accountable");
  const submitBtn = document.getElementById("submit");
  const boxNoInput = document.getElementById("box_no");
  const serialNoInput = document.getElementById("serial_no");

  // Function to fetch item descriptions and populate the dropdown
  function populateItemNameDropdown() {
    fetch("../php/get_item_descriptions.php")
      .then((response) => response.json())
      .then((data) => {
        data.forEach((item) => {
          const option = document.createElement("option");
          option.value = item.item_desc_id;
          option.textContent = item.item_name;
          itemNameDropdown.appendChild(option);
        });
      })
      .catch((error) => {
        console.error("Error fetching item descriptions:", error);
      });
  }

  // Function to fetch employee names and populate the accountable dropdown
  function populateAccountableDropdown() {
    fetch("../php/get_employees.php")
      .then((response) => response.json())
      .then((data) => {
        data.forEach((employee) => {
          const option = document.createElement("option");
          option.value = employee.emp_rec_id;
          // option.textContent = `${employee.emp_fname} ${
          //   employee.emp_minit || ""
          // } ${employee.emp_lname} ${employee.emp_suffix || ""}`.trim();

          option.textContent = `${employee.emp_lname}, ${employee.emp_fname} ${
            employee.emp_minit || ""
          } ${employee.emp_suffix || ""}`.trim();
          accountableDropdown.appendChild(option);
        });
      })
      .catch((error) => {
        console.error("Error fetching employees:", error);
      });
  }

  // Event listener for item name dropdown to update item specs
  itemNameDropdown.addEventListener("click", function () {
    const selectedItemId = this.value;
    fetch(`../php/get_item_description.php?id=${selectedItemId}`)
      .then((response) => response.json())
      .then((data) => {
        if (data && data.item_specs) {
          itemSpecsTextarea.value = data.item_specs;
        } else {
          itemSpecsTextarea.value = "";
        }
      })
      .catch((error) => {
        console.error("Error fetching item specification:", error);
        itemSpecsTextarea.value = "";
      });
  });

  // Event listener for the submit button
  submitBtn.addEventListener("click", function (event) {
    event.preventDefault(); // Prevent the default form submission

    const boxNo = boxNoInput.value.trim();
    const itemDescId = itemNameDropdown.value;
    const serialNo = serialNoInput.value.trim();
    const accountableId = accountableDropdown.value;
    const purchaseDateInput = document.getElementById("purchase-date"); // Get the purchase date input element
    const purchaseDate = purchaseDateInput.value; // Get the value of the purchase date

    if (!boxNo || !itemDescId || !serialNo || !accountableId || !purchaseDate) {
      alert(
        "Please fill out all the required fields, including Purchase Date."
      );
      return;
    }

    const newItemData = {
      box_no: boxNo,
      item_desc_id: itemDescId,
      serial_no: serialNo,
      accountable_id: accountableId,
      purchase_date: purchaseDate, // Include the purchase date
    };

    fetch("../php/add_new_item.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(newItemData),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Item added successfully!"); // Optionally clear the form fields after successful submission
          boxNoInput.value = "";
          serialNoInput.value = "";
          itemSpecsTextarea.value = "";
          purchaseDateInput.value = ""; // Clear the purchase date input // You might want to reset the dropdowns to their default states
        } else {
          alert("Error adding item: " + (data.error || "Unknown error"));
        }
      })
      .catch((error) => {
        console.error("Error adding new item:", error);
        alert("An error occurred while adding the item.");
      });
  });

  populateAccountableDropdown();

  const addCategoryButton = document.getElementById("add-category-btn");
  const addItemCategoryDiv = document.getElementById("add-item-category-div");
  const closeNewCategory = document.getElementById("close-new-category");

  addCategoryButton.addEventListener("click", () => {
    addItemCategoryDiv.style.display = "block";
  });

  closeNewCategory.addEventListener("click", () => {
    addItemCategoryDiv.style.display = "none";
  });

  async function populateItemDescriptionTable() {
    const itemDescriptionsTableBody = document.querySelector(
      "#item-descriptions tbody"
    );

    try {
      const response = await fetch(
        "../php/get_item_descriptions_with_category.php"
      );
      const data = await response.json();

      itemDescriptionsTableBody.innerHTML = ""; // Clear existing table rows

      data.forEach((item) => {
        const row = itemDescriptionsTableBody.insertRow();
        row.dataset.itemId = item.item_desc_id; // Store item_desc_id as a data attribute

        // Action column with buttons
        const actionCell = row.insertCell();
        actionCell.innerHTML = `
        <button class="edit-btn">Edit</button>
        <button class="save-btn" style="display:none;">Save</button>
        <button class="delete-btn">Delete</button>
      `;

        // Item Name column (readonly input)
        const itemNameCell = row.insertCell();
        itemNameCell.innerHTML = `<input type="text" class="item-name-input" value="${item.item_name}" readonly>`;

        // Category column
        const categoryCell = row.insertCell();
        categoryCell.textContent = item.category_name;

        // Item Specification column (readonly textarea)
        const itemSpecsCell = row.insertCell();
        itemSpecsCell.innerHTML = `<textarea class="item-specs-textarea" readonly>${item.item_specs}</textarea>`;
      });
    } catch (error) {
      console.error("Error fetching item descriptions with category:", error);
      itemDescriptionsTableBody.innerHTML = `<tr><td colspan="4">Error loading item descriptions.</td></tr>`;
    }
  }

  populateItemNameDropdown();
  populateItemDescriptionTable(); // Call this function to populate the table

  async function populateCategoryDropdown() {
    const categoryDropdown = document.getElementById("add-item-category");

    try {
      const response = await fetch("../php/get_categories.php");
      const categories = await response.json();

      // Clear existing options
      categoryDropdown.innerHTML = '<option value="">Select Category</option>';

      categories.forEach((category) => {
        const option = document.createElement("option");
        option.value = category.category_id;
        option.textContent = category.category_name;
        categoryDropdown.appendChild(option);
      });
    } catch (error) {
      console.error("Error fetching categories:", error);
      const errorOption = document.createElement("option");
      errorOption.value = "";
      errorOption.textContent = "Error loading categories";
      categoryDropdown.appendChild(errorOption);
    }
  }

  populateCategoryDropdown();

  const addCategoryBtn = document.getElementById("add-new-item-category");
  const newCategoryInput = document.getElementById("add-item-category-input");

  if (addCategoryBtn && newCategoryInput) {
    addCategoryBtn.addEventListener("click", async () => {
      const categoryName = newCategoryInput.value.trim();

      if (categoryName) {
        try {
          const response = await fetch("../php/add_category.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify({ categoryName: categoryName }),
          });

          const data = await response.json();

          if (data.success) {
            console.log("Category added:", data.success);
            // Refresh the category dropdown
            await populateCategoryDropdown();
            // Optionally clear the input and hide the div
            newCategoryInput.value = "";
            if (addItemCategoryDiv) {
              addItemCategoryDiv.style.display = "none";
            }
          } else if (data.error) {
            console.error("Error adding category:", data.error);
            alert("Error adding category: " + data.error);
          }
        } catch (error) {
          console.error("Fetch error:", error);
          alert("An error occurred while adding the category.");
        }
      } else {
        alert("Please enter a category name.");
      }
    });
  }

  const addItemDescBtn = document.getElementById("add-item-description-btn");
  const addItemNameInput = document.getElementById("add-item-name");
  const addItemCategorySelect = document.getElementById("add-item-category");
  const addItemSpecsTextarea = document.getElementById(
    "add-item-specifications"
  );

  if (addItemDescBtn) {
    addItemDescBtn.addEventListener("click", async () => {
      const itemName = addItemNameInput.value.trim();
      const categoryId = addItemCategorySelect.value;
      const itemSpecs = addItemSpecsTextarea.value.trim();

      if (itemName && categoryId) {
        try {
          const response = await fetch("../php/add_item_description.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify({
              itemName: itemName,
              itemSpecs: itemSpecs,
              categoryId: categoryId,
            }),
          });

          const data = await response.json();

          if (data.success) {
            console.log("Item description added:", data.success);
            alert("Item description added successfully!");
            // Refresh the item name dropdown and the item description table
            await populateItemDescriptionTable();
            window.location.reload();
            // Clear the input fields
            addItemNameInput.value = "";
            addItemSpecsTextarea.value = "";
            addItemCategorySelect.value = ""; // Reset the category dropdown
          } else if (data.error) {
            console.error("Error adding item description:", data.error);
            alert("Error adding item description: " + data.error);
          }
        } catch (error) {
          console.error("Fetch error:", error);
          alert("An error occurred while adding the item description.");
        }
      } else {
        alert("Please enter an item name and select a category.");
      }
    });
  }

  const itemDescriptionsTableBody = document.querySelector(
    "#item-descriptions tbody"
  );

  if (itemDescriptionsTableBody) {
    itemDescriptionsTableBody.addEventListener("click", async (event) => {
      const clickedElement = event.target;

      // Handle Edit button click
      if (clickedElement.classList.contains("edit-btn")) {
        const row = clickedElement.closest("tr");
        if (row) {
          const editBtn = row.querySelector(".edit-btn");
          const saveBtn = row.querySelector(".save-btn");
          const itemNameInput = row.querySelector(".item-name-input");
          const itemSpecsTextarea = row.querySelector(".item-specs-textarea");

          if (editBtn) editBtn.style.display = "none";
          if (saveBtn) saveBtn.style.display = "inline-block";
          if (itemNameInput) itemNameInput.readOnly = false;
          if (itemSpecsTextarea) itemSpecsTextarea.readOnly = false;
        }
      }

      // Handle Save button click
      if (clickedElement.classList.contains("save-btn")) {
        const row = clickedElement.closest("tr");
        if (row) {
          const itemId = row.dataset.itemId;
          const itemNameInput = row.querySelector(".item-name-input");
          const itemSpecsTextarea = row.querySelector(".item-specs-textarea");
          const editBtn = row.querySelector(".edit-btn");
          const saveBtn = row.querySelector(".save-btn");

          const updatedItemName = itemNameInput.value.trim();
          const updatedItemSpecs = itemSpecsTextarea.value.trim();

          if (updatedItemName) {
            try {
              const response = await fetch(
                "../php/update_item_description.php",
                {
                  method: "POST",
                  headers: {
                    "Content-Type": "application/json",
                  },
                  body: JSON.stringify({
                    itemId: itemId,
                    itemName: updatedItemName,
                    itemSpecs: updatedItemSpecs,
                  }),
                }
              );

              const data = await response.json();

              if (data.success) {
                console.log("Item description updated:", data.success);
                alert("Item description updated successfully!");
                if (editBtn) editBtn.style.display = "inline-block";
                if (saveBtn) saveBtn.style.display = "none";
                if (itemNameInput) itemNameInput.readOnly = true;
                if (itemSpecsTextarea) itemSpecsTextarea.readOnly = true;
                // Optionally update the category display if it was changed (we didn't enable editing for category yet)
              } else if (data.error) {
                console.error("Error updating item description:", data.error);
                alert("Error updating item description: " + data.error);
              }
            } catch (error) {
              console.error("Fetch error:", error);
              alert("An error occurred while updating the item description.");
            }
          } else {
            alert("Item Name cannot be empty.");
          }
        }
      }

      // Handle Delete button click
      if (clickedElement.classList.contains("delete-btn")) {
        const row = clickedElement.closest("tr");
        if (row) {
          const itemId = row.dataset.itemId;

          if (
            confirm("Are you sure you want to delete this item description?")
          ) {
            try {
              const response = await fetch(
                "../php/delete_item_description.php",
                {
                  method: "POST",
                  headers: {
                    "Content-Type": "application/json",
                  },
                  body: JSON.stringify({ itemId: itemId }),
                }
              );

              const data = await response.json();

              if (data.success) {
                console.log("Item description deleted:", data.success);
                alert("Item description deleted successfully!");
                window.location.reload();
                row.remove(); // Remove the row from the table in the browser
              } else if (data.error) {
                console.error("Error deleting item description:", data.error);
                alert(data.error);
              }
            } catch (error) {
              console.error("Fetch error:", error);
              alert(
                "This item cannot be deleted because it is currently assigned to other items/equipment."
              );
            }
          }
        }
      }
    });
  }
});
