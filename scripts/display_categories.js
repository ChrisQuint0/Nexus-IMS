document.addEventListener("DOMContentLoaded", () => {
  const catTableBody = document.querySelector(
    "#cats-table-section .cat-data-table tbody"
  );
  const searchInput = document.getElementById("cat-table-searchbar");
  const addCatInput = document.getElementById("new-cat-name");
  const addCatButton = document.getElementById("add-category");

  let editingRow = null; // Keep track of the row being edited

  // Function to fetch and display categories
  const loadCategories = async () => {
    try {
      const response = await fetch("../php/display_categories.php?action=load");
      const data = await response.json();
      if (data.success) {
        populateTable(data.categories);
      } else {
        console.error("Error loading categories:", data.message);
        catTableBody.innerHTML =
          '<tr><td colspan="2">Error loading categories.</td></tr>';
      }
    } catch (error) {
      console.error("Network error:", error);
      catTableBody.innerHTML =
        '<tr><td colspan="2">Network error loading categories.</td></tr>';
    }
  };

  // Function to populate the table
  const populateTable = (categories) => {
    catTableBody.innerHTML = "";
    categories.forEach((cat) => {
      const row = catTableBody.insertRow();
      const actionCell = row.insertCell();
      const nameCell = row.insertCell();

      const editButton = createStyledButton("Edit");
      const saveButton = createStyledButton("Save", "none");
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
      nameCell.textContent = cat.category_name;
      nameCell.dataset.catId = cat.category_id; // Store category ID

      editButton.addEventListener("click", () =>
        toggleEditRow(row, nameCell, editButton, saveButton)
      );
      saveButton.addEventListener("click", () =>
        saveCategory(row, nameCell, editButton, saveButton, cat.category_id)
      );
      deleteButton.addEventListener("click", () =>
        confirmDeleteCategory(cat.category_id, row)
      );
    });
  };

  // Function to create a styled button (reused)
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

  // Function to toggle edit state of a category row
  function toggleEditRow(row, nameCell, editButton, saveButton) {
    const currentName = nameCell.textContent;
    nameCell.innerHTML = `<input type="text" value="${currentName}" style="font-size: 18px; font-family: Nunito-Regular; border: solid 1px #0e2f56; padding: 5px;">`;
    editButton.style.display = "none";
    saveButton.style.display = "inline-block";
  }

  // Function to save the edited category name
  async function saveCategory(row, nameCell, editButton, saveButton, catId) {
    const inputField = nameCell.querySelector('input[type="text"]');
    const newName = inputField.value.trim();

    if (newName) {
      try {
        const response = await fetch(
          "../php/display_categories.php?action=edit",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `id=${catId}&name=${encodeURIComponent(newName)}`,
          }
        );
        const data = await response.json();
        if (data.success) {
          nameCell.textContent = newName;
          editButton.style.display = "inline-block";
          saveButton.style.display = "none";
        } else {
          alert(`Error updating category: ${data.message}`);
        }
      } catch (error) {
        console.error("Network error updating category:", error);
        alert("Network error updating category.");
      }
    } else {
      alert("Category name cannot be empty.");
    }
  }

  // Function to confirm delete action for a category
  function confirmDeleteCategory(catId, row) {
    if (
      confirm(`Are you sure you want to delete category with ID: ${catId}?`)
    ) {
      fetch("../php/display_categories.php?action=delete", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `id=${catId}`,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            row.remove();
          } else {
            alert(`Error deleting category: ${data.message}`);
          }
        })
        .catch((error) => {
          console.error("Error deleting category:", error);
          alert("Failed to delete category.");
        });
    }
  }

  // Search functionality
  searchInput.addEventListener("input", async () => {
    const searchTerm = searchInput.value.trim();
    if (searchTerm) {
      try {
        const response = await fetch(
          `../php/display_categories.php?action=search&name=${searchTerm}`
        );
        const data = await response.json();
        if (data.success) {
          populateTable(data.categories);
        } else {
          console.error("Error searching categories:", data.message);
          catTableBody.innerHTML =
            '<tr><td colspan="2">No matching categories found.</td></tr>';
        }
      } catch (error) {
        console.error("Network error during search:", error);
        catTableBody.innerHTML =
          '<tr><td colspan="2">Network error during search.</td></tr>';
      }
    } else {
      loadCategories(); // Reload all categories if search bar is empty
    }
  });

  // Add category functionality
  addCatButton.addEventListener("click", async () => {
    const newCatName = addCatInput.value.trim();
    if (newCatName) {
      try {
        const response = await fetch(
          "../php/display_categories.php?action=add",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `name=${encodeURIComponent(newCatName)}`,
          }
        );
        const data = await response.json();
        if (data.success) {
          loadCategories(); // Reload categories after adding
          addCatInput.value = ""; // Clear the input field
        } else {
          alert(`Error adding category: ${data.message}`);
        }
      } catch (error) {
        console.error("Network error adding category:", error);
        alert("Network error adding category.");
      }
    } else {
      alert("Please enter a category name.");
    }
  });

  // Initial load of categories
  loadCategories();
});
