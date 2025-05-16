document.addEventListener("DOMContentLoaded", populateUserTableWithActions);

const userTableBodyId = "userTableBody";
const usersApiPath = "../php/get_users.php";
const departmentsApiPath = "../php/get_departments.php";
const editUserApiPath = "../php/edit_user.php";
const deleteUserApiPath = "../php/delete_user.php";
let editingRow = null;
let departmentsCache = [];

async function fetchJson(url, options = {}) {
  try {
    const response = await fetch(url, options);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const data = await response.json();
    return data;
  } catch (error) {
    console.error(`Error fetching data from ${url}:`, error);
    return null;
  }
}

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

async function fetchDepartments() {
  if (departmentsCache.length > 0) {
    return departmentsCache;
  }
  const result = await fetchJson(departmentsApiPath);
  if (result && result.success && Array.isArray(result.departments)) {
    departmentsCache = result.departments;
    return departmentsCache;
  } else {
    console.error("Failed to fetch departments:", result);
    return [];
  }
}

function createRoleDropdown(currentUserRole) {
  const select = document.createElement("select");
  select.disabled = false; // Enable for editing
  const adminOption = document.createElement("option");
  adminOption.value = "admin";
  adminOption.textContent = "Admin";
  adminOption.selected = currentUserRole === "admin";
  select.appendChild(adminOption);
  const deptHeadOption = document.createElement("option");
  deptHeadOption.value = "dept_head";
  deptHeadOption.textContent = "Department Head";
  deptHeadOption.selected = currentUserRole === "dept_head";
  select.appendChild(deptHeadOption);
  return select;
}

async function createDepartmentDropdown(currentUserDepartment) {
  const select = document.createElement("select");
  select.disabled = false; // Enable for editing
  const departments = await fetchDepartments();
  departments.forEach((dept) => {
    const option = document.createElement("option");
    option.value = dept.department_id;
    option.textContent = dept.department_name;
    option.selected = currentUserDepartment === dept.department_name;
    select.appendChild(option);
  });
  return select;
}

function createAccountStatusDropdown(currentStatus) {
  const select = document.createElement("select");
  select.disabled = false; // Enable for editing
  const activatedOption = document.createElement("option");
  activatedOption.value = "activated";
  activatedOption.textContent = "Activated";
  activatedOption.selected = currentStatus === "activated";
  select.appendChild(activatedOption);
  const deactivatedOption = document.createElement("option");
  deactivatedOption.value = "deactivated";
  deactivatedOption.textContent = "Deactivated";
  deactivatedOption.selected = currentStatus === "deactivated";
  select.appendChild(deactivatedOption);
  return select;
}

function toggleEditRow(row) {
  if (editingRow && editingRow !== row) {
    // If another row is being edited, revert it
    const cells = editingRow.querySelectorAll("td");
    cells[1].textContent = cells[1].querySelector("input").value;
    cells[2].textContent = cells[2].querySelector("input").value;
    cells[3].textContent = cells[3].querySelector("input").value; // Revert password (now an input)
    cells[4].textContent = cells[4].querySelector("select").value;
    cells[5].textContent = cells[5].querySelector("select")
      ? cells[5].querySelector("select").options[
          cells[5].querySelector("select").selectedIndex
        ]?.textContent
      : cells[5].textContent;
    cells[6].textContent = cells[6].querySelector("select")
      ? cells[6].querySelector("select").options[
          cells[6].querySelector("select").selectedIndex
        ]?.textContent
      : cells[6].textContent === "activated"
      ? "Activated"
      : "Deactivated";
    const actionCell = editingRow.querySelector("td:first-child");
    actionCell.querySelector(".edit-button").style.display = "inline-block";
    actionCell.querySelector(".save-button").style.display = "none";
  }
  editingRow = row;
  const cells = row.querySelectorAll("td");
  const currentUsername = cells[1].textContent;
  const currentEmail = cells[2].textContent;
  const currentPassword = cells[3].textContent; // Get current hashed password
  const currentRole = cells[4].textContent;
  const currentDepartment = cells[5].textContent;
  const currentAccountStatus = cells[6].textContent.toLowerCase();

  // Replace username with input
  const usernameInput = document.createElement("input");
  usernameInput.type = "text";
  usernameInput.value = currentUsername;
  usernameInput.style.width = `${cells[1].offsetWidth}px`;
  cells[1].innerHTML = "";
  cells[1].appendChild(usernameInput);

  // Replace email with input
  const emailInput = document.createElement("input");
  emailInput.type = "email";
  emailInput.value = currentEmail;
  emailInput.style.width = `${cells[2].offsetWidth}px`;
  cells[2].innerHTML = "";
  cells[2].appendChild(emailInput);

  const passwordInput = document.createElement("input");
  passwordInput.type = "text";
  passwordInput.placeholder = "Leave blank to keep current password";
  passwordInput.value = ""; // NEVER prefill
  passwordInput.style.width = `${cells[3].offsetWidth}px`;
  cells[3].innerHTML = "";
  cells[3].appendChild(passwordInput);

  // Replace role with dropdown
  const roleDropdown = createRoleDropdown(currentRole);
  roleDropdown.style.width = "200px";
  cells[4].innerHTML = "";
  cells[4].appendChild(roleDropdown);

  // Replace department with dropdown
  createDepartmentDropdown(currentDepartment).then((deptDropdown) => {
    deptDropdown.style.width = "370px";
    cells[5].innerHTML = "";
    cells[5].appendChild(deptDropdown);
  });

  // Replace account status with dropdown
  const accountStatusDropdown =
    createAccountStatusDropdown(currentAccountStatus);
  accountStatusDropdown.style.width = `${cells[6].offsetWidth}px`;
  cells[6].innerHTML = "";
  cells[6].appendChild(accountStatusDropdown);

  const actionCell = row.querySelector("td:first-child");
  actionCell.querySelector(".edit-button").style.display = "none";
  actionCell.querySelector(".save-button").style.display = "inline-block";
}

async function saveUser(row) {
  const userId = parseInt(row.dataset.userId, 10);
  const cells = row.querySelectorAll("td");
  const username = cells[1].querySelector("input").value;
  const email = cells[2].querySelector("input").value;
  const password = cells[3].querySelector("input").value; // Get the potentially new password
  const roleSelect = cells[4].querySelector("select");
  const departmentSelect = cells[5].querySelector("select");
  const accountStatusSelect = cells[6].querySelector("select");

  const roleValue = roleSelect ? roleSelect.value : cells[4].textContent;
  const departmentId = departmentSelect ? departmentSelect.value : null;
  const accountStatusValue = accountStatusSelect
    ? accountStatusSelect.value
    : cells[6].textContent.toLowerCase();
  const roleText = roleSelect
    ? roleSelect.options[roleSelect.selectedIndex].textContent
    : cells[4].textContent;
  const departmentText = departmentSelect
    ? departmentSelect.options[departmentSelect.selectedIndex].textContent
    : cells[5].textContent;
  const accountStatusText = accountStatusSelect
    ? accountStatusSelect.options[accountStatusSelect.selectedIndex].textContent
    : cells[6].textContent;

  const payload = {
    user_id: userId,
    username: username,
    email: email,
    password: password, // Include the potentially new password
    role: roleValue,
    department_id: departmentId,
    account_status: accountStatusValue,
  };

  console.log("Payload being sent:", payload);

  try {
    const result = await fetchJson(editUserApiPath, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    if (result && result.success) {
      const actionCell = row.querySelector("td:first-child");
      actionCell.querySelector(".edit-button").style.display = "inline-block";
      actionCell.querySelector(".save-button").style.display = "none";
      editingRow = null;

      // Revert the row to its non-editable state
      cells[1].textContent = username;
      cells[2].textContent = email;
      cells[3].textContent = password; // Display the (potentially new) hashed password
      // cells[4].textContent = roleText;
      // cells[5].textContent = departmentText;
      // cells[6].textContent = accountStatusText;

      // Disable the dropdowns (only if they exist)
      if (roleSelect) {
        roleSelect.disabled = true;
      }
      if (departmentSelect) {
        departmentSelect.disabled = true;
      }
      if (accountStatusSelect) {
        accountStatusSelect.disabled = true;
      }

      alert("User updated successfully!");
      populateUserTableWithActions();
    } else {
      alert(`Error updating user: ${result?.error || "Unknown error"}`);
    }
  } catch (error) {
    console.error("Error updating user:", error);
    alert("Failed to update user.");
  }
}

async function deleteUser(row) {
  const userId = parseInt(row.dataset.userId, 10);
  const usernameToDelete = row.querySelectorAll("td")[1].textContent;

  if (confirm(`Are you sure you want to delete user: ${usernameToDelete}?`)) {
    try {
      console.log("Deleting user ID:", userId);

      const payload = JSON.stringify({ user_id: userId });
      console.log("Delete Payload:", payload);

      const result = await fetchJson(deleteUserApiPath, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: payload,
      });

      console.log("Delete result:", result);

      if (result && result.success) {
        row.remove();
        alert("User deleted successfully!");
      } else {
        alert(`Error deleting user: ${result?.error || "Unknown error"}`);
      }
    } catch (error) {
      console.error("Error deleting user:", error);
      alert("Failed to delete user.");
    }
  }
}

async function populateUserTableWithActions() {
  const tableBody = document.querySelector(`#${userTableBodyId}`);
  const userTable = tableBody.closest("table");

  if (!userTable) {
    console.error("Error: Could not find the user table element.");
    return;
  }

  tableBody.innerHTML = "";

  try {
    const result = await fetchJson(usersApiPath);

    if (result && result.success && Array.isArray(result.users)) {
      const users = result.users;

      for (const user of users) {
        const row = tableBody.insertRow();
        row.dataset.userId = user.user_id;

        const actionCell = row.insertCell();
        const editButton = createStyledButton(
          "Edit",
          "inline-block",
          "white",
          "#0e2f56"
        );
        editButton.classList.add("edit-button");
        const saveButton = createStyledButton(
          "Save",
          "none",
          "#0e2f56",
          "white",
          "5px"
        );
        saveButton.classList.add("save-button");
        const deleteButton = createStyledButton(
          "Delete",
          "inline-block",
          "white",
          "#dc3545",
          "5px"
        );
        deleteButton.style.borderColor = "#dc3545";

        actionCell.appendChild(editButton);
        actionCell.appendChild(saveButton);
        actionCell.appendChild(deleteButton);

        editButton.addEventListener("click", () => toggleEditRow(row));
        saveButton.addEventListener("click", () => saveUser(row));
        deleteButton.addEventListener("click", function () {
          const rowToDelete = this.closest("tr");
          deleteUser(rowToDelete);
        });

        const usernameCell = row.insertCell();
        usernameCell.textContent = user.username;

        const emailCell = row.insertCell();
        emailCell.textContent = user.email;

        const passwordCell = row.insertCell();
        passwordCell.textContent = user.password;

        const roleCell = row.insertCell();
        roleCell.textContent = user.role; // Just set the text initially

        const departmentCell = row.insertCell();
        departmentCell.textContent = user.department; // Just set the text initially

        const accountStatusCell = row.insertCell();
        accountStatusCell.textContent = user.account_status; // Just set the text initially
      }
    } else {
      console.error("Failed to load user data:", result);
      tableBody.innerHTML =
        '<tr><td colspan="7">Failed to load user data.</td></tr>';
    }
  } catch (error) {
    console.error("Error populating user table:", error);
    tableBody.innerHTML =
      '<tr><td colspan="7">Error loading user data.</td></tr>';
  }
}
