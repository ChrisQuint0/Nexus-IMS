document.addEventListener("DOMContentLoaded", () => {
  const statusTableBody = document.querySelector(
    "#statuses-table-section .status-data-table tbody"
  );
  const searchInput = document.getElementById("status-table-searchbar");
  const addStatusInput = document.getElementById("new-status-name");
  const addStatusButton = document.getElementById("add-status");

  let editingRow = null; // Keep track of the row being edited

  // Function to fetch and display statuses
  const loadStatuses = async () => {
    try {
      const response = await fetch("../php/display_statuses.php?action=load");
      const data = await response.json();
      if (data.success) {
        populateTable(data.statuses);
      } else {
        console.error("Error loading statuses:", data.message);
        statusTableBody.innerHTML =
          '<tr><td colspan="2">Error loading statuses.</td></tr>';
      }
    } catch (error) {
      console.error("Network error:", error);
      statusTableBody.innerHTML =
        '<tr><td colspan="2">Network error loading statuses.</td></tr>';
    }
  };

  // Function to populate the table
  const populateTable = (statuses) => {
    statusTableBody.innerHTML = "";
    statuses.forEach((status) => {
      const row = statusTableBody.insertRow();
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
      nameCell.textContent = status.status_name;
      nameCell.dataset.statusId = status.status_id; // Store status ID

      editButton.addEventListener("click", () =>
        toggleEditRow(row, nameCell, editButton, saveButton)
      );
      saveButton.addEventListener("click", () =>
        saveStatus(row, nameCell, editButton, saveButton, status.status_id)
      );
      deleteButton.addEventListener("click", () =>
        confirmDeleteStatus(status.status_id, row)
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

  // Function to toggle edit state of a status row
  function toggleEditRow(row, nameCell, editButton, saveButton) {
    const currentName = nameCell.textContent;
    nameCell.innerHTML = `<input type="text" value="${currentName}" style="font-size: 18px; font-family: Nunito-Regular; border: solid 1px #0e2f56; padding: 5px;">`;
    editButton.style.display = "none";
    saveButton.style.display = "inline-block";
  }

  // Function to save the edited status name
  async function saveStatus(row, nameCell, editButton, saveButton, statusId) {
    const inputField = nameCell.querySelector('input[type="text"]');
    const newName = inputField.value.trim();

    if (newName) {
      try {
        const response = await fetch(
          "../php/display_statuses.php?action=edit",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `id=${statusId}&name=${encodeURIComponent(newName)}`,
          }
        );
        const data = await response.json();
        if (data.success) {
          nameCell.textContent = newName;
          editButton.style.display = "inline-block";
          saveButton.style.display = "none";
        } else {
          alert(`Error updating status: ${data.message}`);
        }
      } catch (error) {
        console.error("Network error updating status:", error);
        alert("Network error updating status.");
      }
    } else {
      alert("Status name cannot be empty.");
    }
  }

  // Function to confirm delete action for a status
  function confirmDeleteStatus(statusId, row) {
    if (
      confirm(`Are you sure you want to delete status with ID: ${statusId}?`)
    ) {
      fetch("../php/display_statuses.php?action=delete", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `id=${statusId}`,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            row.remove();
          } else {
            alert(`Error deleting status: ${data.message}`);
          }
        })
        .catch((error) => {
          console.error("Error deleting status:", error);
          alert("Failed to delete status.");
        });
    }
  }

  // Search functionality
  searchInput.addEventListener("input", async () => {
    const searchTerm = searchInput.value.trim();
    if (searchTerm) {
      try {
        const response = await fetch(
          `../php/display_statuses.php?action=search&name=${searchTerm}`
        );
        const data = await response.json();
        if (data.success) {
          populateTable(data.statuses);
        } else {
          console.error("Error searching statuses:", data.message);
          statusTableBody.innerHTML =
            '<tr><td colspan="2">No matching statuses found.</td></tr>';
        }
      } catch (error) {
        console.error("Network error during search:", error);
        statusTableBody.innerHTML =
          '<tr><td colspan="2">Network error during search.</td></tr>';
      }
    } else {
      loadStatuses(); // Reload all statuses if search bar is empty
    }
  });

  // Add status functionality
  addStatusButton.addEventListener("click", async () => {
    const newStatusName = addStatusInput.value.trim();
    if (newStatusName) {
      try {
        const response = await fetch("../php/display_statuses.php?action=add", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: `name=${encodeURIComponent(newStatusName)}`,
        });
        const data = await response.json();
        if (data.success) {
          loadStatuses(); // Reload statuses after adding
          addStatusInput.value = ""; // Clear the input field
        } else {
          alert(`Error adding status: ${data.message}`);
        }
      } catch (error) {
        console.error("Network error adding status:", error);
        alert("Network error adding status.");
      }
    } else {
      alert("Please enter a status name.");
    }
  });

  // Initial load of statuses
  loadStatuses();
});
