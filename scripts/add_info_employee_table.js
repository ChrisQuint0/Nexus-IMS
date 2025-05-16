(function () {
  // Get references to the employee table elements
  const empDataTable = document
    .getElementById("emp-data-table")
    .querySelector("tbody");
  const employeeSearchInput = document.getElementById(
    "employee-table-searchbar"
  );
  let allEmployeeData = [];
  let allDepartments = []; // To store fetched department data
  const employeeCategories = ["Teaching Staff", "Non-Teaching Staff"]; // Available categories

  // Function to fetch employee data and departments from the PHP backend
  function fetchEmployeeData() {
    fetch("../php/get_all_cols_employee.php") // Create this PHP file
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        allEmployeeData = data.employees; // Assuming your PHP returns { employees: [], departments: [] }
        allDepartments = data.departments;
        populateEmployeeTable(allEmployeeData);
      })
      .catch((error) => {
        console.error("Error fetching employee data:", error);
        alert("Failed to load employee data.");
      });
  }

  // Function to populate the employee table
  function populateEmployeeTable(employees) {
    empDataTable.innerHTML = "";

    employees.forEach((employee) => {
      const row = empDataTable.insertRow();

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

      // Add data cells with input fields or dropdowns
      createTableCell(row, employee.employee_id, "employee_id", true);
      createTableCell(row, employee.emp_fname, "emp_fname", true);
      createTableCell(row, employee.emp_minit, "emp_minit", true);
      createTableCell(row, employee.emp_lname, "emp_lname", true);
      createTableCell(row, employee.emp_suffix, "emp_suffix", true);
      createTableCell(row, employee.emp_email, "emp_email", true);
      createTableCell(
        row,
        employee.emp_contact_number,
        "emp_contact_number",
        true
      );
      createTableCell(row, employee.emp_address, "emp_address", true);
      createCategoryDropdownCell(row, employee.emp_category); // Category dropdown
      createDepartmentDropdownCell(
        row,
        employee.department_id,
        employee.department_name
      ); // Department dropdown

      // Add event listeners
      editButton.addEventListener("click", () =>
        toggleEditRow(row, editButton, saveButton)
      );
      saveButton.addEventListener("click", () =>
        saveEmployeeData(row, editButton, saveButton, employee.emp_rec_id)
      );
      deleteButton.addEventListener("click", () =>
        confirmDeleteEmployee(employee.emp_rec_id, row)
      );
    });
  }

  // Function to create a styled button
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

  // Function to create a table cell with an input field
  function createTableCell(row, value, name, editable) {
    const cell = row.insertCell();
    const input = document.createElement("input");
    input.type = "text";
    input.name = name;
    input.value = value || "";
    input.readOnly = true;
    applyInputStyles(input);
    cell.appendChild(input);
  }

  // Function to apply common input styles
  function applyInputStyles(input) {
    input.style.fontSize = "18px";
    input.style.fontFamily = "Nunito-Regular";
    input.style.border = "none";
    input.style.background = "transparent";
  }

  // Function to create the category dropdown cell
  function createCategoryDropdownCell(row, currentCategory) {
    const cell = row.insertCell();
    const select = document.createElement("select");
    select.name = "emp_category";
    select.style.pointerEvents = "none";
    applyInputStyles(select);
    select.style.padding = "0";
    select.tabIndex = -1;

    // Create default option
    const defaultOption = document.createElement("option");
    defaultOption.value = currentCategory;
    defaultOption.textContent = currentCategory;
    defaultOption.selected = true;
    select.appendChild(defaultOption);

    // Add other category options
    employeeCategories.forEach((category) => {
      if (category !== currentCategory) {
        const option = document.createElement("option");
        option.value = category;
        option.textContent = category;
        select.appendChild(option);
      }
    });

    cell.appendChild(select);
  }

  // Function to create the department dropdown cell
  function createDepartmentDropdownCell(
    row,
    currentDepartmentId,
    currentDepartmentName
  ) {
    const cell = row.insertCell();
    const select = document.createElement("select");
    select.name = "department_id";
    select.style.pointerEvents = "none";
    applyInputStyles(select);
    select.style.padding = "0";
    select.tabIndex = -1;

    // Create default option
    const defaultOption = document.createElement("option");
    defaultOption.value = currentDepartmentId;
    defaultOption.textContent = currentDepartmentName;
    defaultOption.selected = true;
    select.appendChild(defaultOption);

    // Add other department options
    allDepartments.forEach((dept) => {
      if (dept.department_id !== parseInt(currentDepartmentId)) {
        const option = document.createElement("option");
        option.value = dept.department_id;
        option.textContent = dept.department_name;
        select.appendChild(option);
      }
    });

    cell.appendChild(select);
  }

  // Function to toggle edit state of a row
  function toggleEditRow(row, editButton, saveButton) {
    const inputs = row.querySelectorAll("input[type='text']");
    const categorySelect = row.querySelector("select[name='emp_category']");
    const departmentSelect = row.querySelector("select[name='department_id']");

    inputs.forEach((input) => {
      input.readOnly = !input.readOnly;
      input.style.border = input.readOnly ? "none" : "solid 1px #0e2f56";
    });

    toggleDropdownEdit(categorySelect);
    toggleDropdownEdit(departmentSelect);

    editButton.style.display = "none";
    saveButton.style.display = "inline-block";
  }

  // Function to toggle dropdown edit state
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

  // Function to handle saving employee data
  // Function to handle saving employee data
  function saveEmployeeData(row, editButton, saveButton, empRecId) {
    const inputs = row.querySelectorAll("input[type='text']");
    const categorySelect = row.querySelector("select[name='emp_category']");
    const departmentSelect = row.querySelector("select[name='department_id']");
    const updatedData = {};

    inputs.forEach((input) => {
      updatedData[input.name] = input.value;
    });

    if (categorySelect) {
      updatedData.emp_category = categorySelect.value;
    }

    if (departmentSelect) {
      updatedData.department_id = departmentSelect.value;
      console.log("Selected department_id:", departmentSelect.value);
    }

    console.log("Data being sent to update_employee.php:", updatedData);

    updatedData.emp_rec_id = empRecId;

    console.log("Data being sent to update_employee.php:", updatedData);

    fetch("../php/update_employee.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(updatedData),
    })
      .then((response) => {
        if (!response.ok) {
          // If the HTTP status code is not in the 2xx range, it's an error
          return response.text().then((text) => {
            throw new Error(
              `HTTP error! status: ${response.status}, body: ${text}`
            );
          });
        }
        return response.json();
      })
      .then((data) => {
        console.log("Success:", data);
        inputs.forEach((input) => {
          input.readOnly = true;
          input.style.border = "none";
        });
        toggleDropdownEdit(categorySelect);
        toggleDropdownEdit(departmentSelect);

        // // Update displayed values (optional)
        // row.cells[9].textContent = categorySelect
        //   ? categorySelect.options[categorySelect.selectedIndex].textContent
        //   : "";
        // row.cells[10].textContent = departmentSelect
        //   ? departmentSelect.options[departmentSelect.selectedIndex].textContent
        //   : "";

        editButton.style.display = "inline-block";
        saveButton.style.display = "none";
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("Failed to save employee data: " + error); // Display the error message
      });
  }

  // Function to confirm delete action for employees
  function confirmDeleteEmployee(empRecId, row) {
    if (
      confirm(`Are you sure you want to delete employee with ID: ${empRecId}?`)
    ) {
      fetch("../php/delete_employee.php?emp_rec_id=" + empRecId, {
        // Create this PHP file
        method: "DELETE",
      })
        .then((response) => response.json())
        .then((data) => {
          console.log("Delete successful:", data);
          row.remove();
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("Failed to delete employee.");
        });
    }
  }

  // Function to handle the search for employees
  function handleEmployeeSearch() {
    const searchTerm = employeeSearchInput.value.toLowerCase();
    const filteredEmployees = allEmployeeData.filter((employee) =>
      employee.employee_id.toLowerCase().startsWith(searchTerm)
    );
    populateEmployeeTable(filteredEmployees);
  }

  // Add event listener to the search input
  employeeSearchInput.addEventListener("input", handleEmployeeSearch);

  // Call the function to fetch and populate employee data when the script loads
  fetchEmployeeData();
})();
