// Get references to the student table elements
const studDataTable = document
  .getElementById("stud-data-table")
  .querySelector("tbody");
const studentSearchInput = document.getElementById("student-table-searchbar");
let allStudentData = [];
let allDepartments = []; // To store all fetched department data

// Function to fetch student data and departments from the PHP backend
function fetchStudentData() {
  fetch("../php/get_students.php")
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      allStudentData = data.students; // Assuming your PHP returns { students: [], departments: [] }
      allDepartments = data.departments;
      populateStudentTable(allStudentData);
    })
    .catch((error) => {
      console.error("Error fetching student data:", error);
      alert("Failed to load student data.");
    });
}

// Function to populate the student table
function populateStudentTable(students) {
  studDataTable.innerHTML = "";

  students.forEach((student) => {
    const row = studDataTable.insertRow();

    // Action column
    const actionCell = row.insertCell();
    const editButton = document.createElement("button");
    editButton.textContent = "Edit";
    editButton.style.padding = "5px 20px";
    editButton.style.borderRadius = "15px";
    editButton.style.border = "solid 1px #0e2f56";
    editButton.style.fontSize = "15px";
    editButton.style.color = "#0e2f56";
    editButton.style.backgroundColor = "white";

    const saveButton = document.createElement("button");
    saveButton.textContent = "Save";
    saveButton.style.display = "none"; // Initially hidden
    saveButton.style.padding = "5px 20px";
    saveButton.style.borderRadius = "15px";
    saveButton.style.border = "solid 1px #0e2f56";
    saveButton.style.fontSize = "15px";
    saveButton.style.color = "#0e2f56";
    saveButton.style.backgroundColor = "white";

    const deleteButton = document.createElement("button");
    deleteButton.textContent = "Delete";
    deleteButton.style.padding = "5px 20px";
    deleteButton.style.borderRadius = "15px";
    deleteButton.style.border = "solid 1px #0e2f56";
    deleteButton.style.fontSize = "15px";
    deleteButton.style.color = "white";
    deleteButton.style.backgroundColor = "#0e2f56";
    deleteButton.style.marginLeft = "10px";

    actionCell.appendChild(editButton);
    actionCell.appendChild(saveButton);
    actionCell.appendChild(deleteButton);

    // Add data cells with input fields or dropdown
    createTableCell(row, student.student_id, "student_id", true);
    createTableCell(row, student.first_name, "first_name", true);
    createTableCell(row, student.middle_name, "middle_name", true);
    createTableCell(row, student.last_name, "last_name", true);
    createTableCell(row, student.suffix, "suffix", true);
    createTableCell(row, student.gender, "gender", true);
    createTableCell(row, student.section, "section", true);
    createDepartmentDropdownCell(
      row,
      student.department_id,
      student.department_name
    ); // Create dropdown
    createTableCell(row, student.contact_number, "contact_number", true);
    createTableCell(row, student.email, "email", true);
    createTableCell(row, student.stud_address, "address", true);

    // Add event listeners for edit, save, and delete
    editButton.addEventListener("click", () =>
      toggleEdit(row, editButton, saveButton)
    );
    saveButton.addEventListener("click", () =>
      saveRowData(row, editButton, saveButton, student.stud_rec_id)
    );
    deleteButton.addEventListener("click", () =>
      confirmDelete(student.stud_rec_id, row)
    );
  });
}

// Function to create a table cell with an input field
function createTableCell(row, value, name, editable) {
  const cell = row.insertCell();
  const input = document.createElement("input");
  input.type = "text";
  input.name = name;
  input.value = value || "";
  input.readOnly = true; // Initially set to readonly
  input.style.fontSize = "18px";
  input.style.fontFamily = "Nunito-Regular";
  input.style.border = "none";
  input.style.background = "transparent";
  cell.appendChild(input);
}

// Function to create the department dropdown cell
function createDepartmentDropdownCell(
  row,
  currentDepartmentId,
  currentDepartmentName
) {
  const cell = row.insertCell();
  const select = document.createElement("select");
  select.name = "department_id"; // Important for capturing the value
  select.style.pointerEvents = "none";

  select.style.fontSize = "18px";
  select.style.fontFamily = "Nunito-Regular";
  select.style.border = "none";
  select.style.background = "transparent";
  select.style.padding = "0"; // Adjust padding as needed

  // Create default option
  const defaultOption = document.createElement("option");
  defaultOption.value = currentDepartmentId; // Set the current ID as the initial value
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
function toggleEdit(row, editButton, saveButton) {
  const inputs = row.querySelectorAll("input[type='text']");
  const departmentSelect = row.querySelector("select[name='department_id']");

  inputs.forEach((input) => {
    input.readOnly = !input.readOnly; // Toggle readonly property
    input.style.border = input.readOnly ? "none" : "solid 1px #0e2f56"; // Toggle border
  });

  if (departmentSelect) {
    departmentSelect.style.pointerEvents =
      departmentSelect.style.pointerEvents === "none" ? "auto" : "none";
    departmentSelect.readonly = !departmentSelect.readonly; // Enable the dropdown
    departmentSelect.style.border = departmentSelect.readOnly
      ? "none"
      : "solid 1px #0e2f56";
    departmentSelect.tabIndex =
      departmentSelect.style.pointerEvents === "none" ? -1 : 0;
  }

  editButton.style.display = "none";
  saveButton.style.display = "inline-block";
}

// Function to handle saving row data (needs backend integration)
function saveRowData(row, editButton, saveButton, studentRecId) {
  const inputs = row.querySelectorAll("input[type='text']");
  const departmentSelect = row.querySelector("select[name='department_id']");
  const updatedData = {};

  inputs.forEach((input) => {
    updatedData[input.name] = input.value;
  });

  if (departmentSelect) {
    updatedData.department_id = departmentSelect.value; // Get the selected department ID
  }

  // Include the student's record ID for backend identification
  updatedData.stud_rec_id = studentRecId;

  // --- AJAX call to your backend to update the database ---
  fetch("../php/update_student.php", {
    // Create this PHP file
    method: "POST", // Or 'PUT' depending on your backend logic
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(updatedData),
  })
    .then((response) => response.json())
    .then((data) => {
      console.log("Success:", data);
      // Optionally update the UI based on the response (e.g., show message)
      inputs.forEach((input) => {
        input.readOnly = true; // Disable editing after saving
        input.style.border = "none"; // Hide border after saving
      });
      if (departmentSelect) {
        departmentSelect.style.pointerEvents = "none"; // Disable the dropdown after saving
        departmentSelect.style.border = "none";
        // Update the displayed department name (optional)
        // const selectedOption =
        //   departmentSelect.options[departmentSelect.selectedIndex];
        // const departmentCell = row.cells[8]; // Assuming Department is the 9th cell (index 8)
        // departmentCell.textContent = selectedOption
        //   ? selectedOption.textContent
        //   : "";
      }
      editButton.style.display = "inline-block";
      saveButton.style.display = "none";
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Failed to save data.");
    });
}

// Function to confirm delete action
function confirmDelete(studentRecId, row) {
  if (
    confirm(`Are you sure you want to delete student with ID: ${studentRecId}?`)
  ) {
    // --- AJAX call to your backend to delete the record ---
    fetch("../php/delete_student.php?stud_rec_id=" + studentRecId, {
      method: "DELETE",
    })
      .then((response) => response.json())
      .then((data) => {
        console.log("Delete successful:", data);
        row.remove(); // Remove the row from the UI
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("Failed to delete student.");
      });
  }
}

// Function to handle the search
function handleStudentSearch() {
  const searchTerm = studentSearchInput.value.toLowerCase();
  const filteredStudents = allStudentData.filter((student) =>
    student.student_id.toLowerCase().startsWith(searchTerm)
  );
  populateStudentTable(filteredStudents);
}

// Add event listener to the search input
studentSearchInput.addEventListener("input", handleStudentSearch);

// Call the function to fetch and populate student data when the script loads
fetchStudentData();
