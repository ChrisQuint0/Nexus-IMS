document.addEventListener("DOMContentLoaded", () => {
  const deptTableBody = document.querySelector(
    "#depts-table-section .dept-data-table tbody"
  );
  const searchInput = document.getElementById("dept-table-searchbar");
  const addDeptInput = document.getElementById("new-dept-name");
  const addDeptButton = document.getElementById("add-department");

  let editingRow = null; // Keep track of the row being edited

  // Function to fetch and display departments
  const loadDepartments = async () => {
    try {
      const response = await fetch(
        "../php/../php/display_dept_table.php?action=load"
      );
      const data = await response.json();
      if (data.success) {
        populateTable(data.departments);
      } else {
        console.error("Error loading departments:", data.message);
        deptTableBody.innerHTML =
          '<tr><td colspan="2">Error loading departments.</td></tr>';
      }
    } catch (error) {
      console.error("Network error:", error);
      deptTableBody.innerHTML =
        '<tr><td colspan="2">Network error loading departments.</td></tr>';
    }
  };

  // Function to populate the table
  const populateTable = (departments) => {
    deptTableBody.innerHTML = "";
    departments.forEach((dept) => {
      const row = deptTableBody.insertRow();
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
      nameCell.textContent = dept.department_name;
      nameCell.dataset.deptId = dept.department_id; // Store department ID

      editButton.addEventListener("click", () =>
        toggleEditRow(row, nameCell, editButton, saveButton)
      );
      saveButton.addEventListener("click", () =>
        saveDepartment(
          row,
          nameCell,
          editButton,
          saveButton,
          dept.department_id
        )
      );
      deleteButton.addEventListener("click", () =>
        confirmDeleteDepartment(dept.department_id, row)
      );
    });
  };

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

  // Function to toggle edit state of a department row
  function toggleEditRow(row, nameCell, editButton, saveButton) {
    const currentName = nameCell.textContent;
    nameCell.innerHTML = `<input type="text" value="${currentName}" style="font-size: 18px; font-family: Nunito-Regular; border: solid 1px #0e2f56; padding: 5px;">`;
    editButton.style.display = "none";
    saveButton.style.display = "inline-block";
  }

  // Function to save the edited department name
  async function saveDepartment(row, nameCell, editButton, saveButton, deptId) {
    const inputField = nameCell.querySelector('input[type="text"]');
    const newName = inputField.value.trim();

    if (newName) {
      try {
        const response = await fetch(
          "../php/display_dept_table.php?action=edit",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `id=${deptId}&name=${encodeURIComponent(newName)}`,
          }
        );
        const data = await response.json();
        if (data.success) {
          nameCell.textContent = newName;
          editButton.style.display = "inline-block";
          saveButton.style.display = "none";
        } else {
          alert(`Error updating department: ${data.message}`);
        }
      } catch (error) {
        console.error("Network error updating department:", error);
        alert("Network error updating department.");
      }
    } else {
      alert("Department name cannot be empty.");
    }
  }

  // Function to confirm delete action for a department
  function confirmDeleteDepartment(deptId, row) {
    if (
      confirm(`Are you sure you want to delete department with ID: ${deptId}?`)
    ) {
      fetch("../php/display_dept_table.php?action=delete", {
        method: "POST", // Change method to POST
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `id=${deptId}`, // Include the id in the request body
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            row.remove();
          } else {
            alert(`Error deleting department: ${data.message}`);
          }
        })
        .catch((error) => {
          console.error("Error deleting department:", error);
          alert("Failed to delete department.");
        });
    }
  }

  // Search functionality
  searchInput.addEventListener("input", async () => {
    const searchTerm = searchInput.value.trim();
    if (searchTerm) {
      try {
        const response = await fetch(
          `../php/display_dept_table.php?action=search&name=${searchTerm}`
        );
        const data = await response.json();
        if (data.success) {
          populateTable(data.departments);
        } else {
          console.error("Error searching departments:", data.message);
          deptTableBody.innerHTML =
            '<tr><td colspan="2">No matching departments found.</td></tr>';
        }
      } catch (error) {
        console.error("Network error during search:", error);
        deptTableBody.innerHTML =
          '<tr><td colspan="2">Network error during search.</td></tr>';
      }
    } else {
      loadDepartments(); // Reload all departments if search bar is empty
    }
  });

  // Add department functionality (remains the same)
  addDeptButton.addEventListener("click", async () => {
    const newDeptName = addDeptInput.value.trim();
    if (newDeptName) {
      try {
        const response = await fetch(
          "../php/display_dept_table.php?action=add",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `name=${encodeURIComponent(newDeptName)}`,
          }
        );
        const data = await response.json();
        if (data.success) {
          loadDepartments(); // Reload departments after adding
          addDeptInput.value = ""; // Clear the input field
        } else {
          alert(`Error adding department: ${data.message}`);
        }
      } catch (error) {
        console.error("Network error adding department:", error);
        alert("Network error adding department.");
      }
    } else {
      alert("Please enter a department name.");
    }
  });

  // Initial load of departments
  loadDepartments();
});
