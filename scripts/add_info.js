document.addEventListener("DOMContentLoaded", function () {
  // Get references to the elements
  let studentDepartmentSelect = document.querySelector(
    "#new-std-info #student-department"
  );
  let employeeDepartmentSelect = document.getElementById("employee-department");

  const infoTypeSelect = document.getElementById("info-type");
  const studentFormWrapper = document.querySelector(
    ".new-student-info-form-wrapper"
  );
  const employeeFormWrapper = document.querySelector(
    ".new-employee-info-form-wrapper"
  );
  const studIdContainer = document.getElementById("stud-id-container");
  const empIdContainer = document.getElementById("emp-id-container");
  const employeeDeptContainer = document.getElementById(
    "employee-dept-container"
  );
  const employeeCatContainer = document.getElementById(
    "employee-cat-container"
  );

  // Function to toggle visibility based on selection
  function toggleForms() {
    const selectedType = infoTypeSelect.value;

    if (selectedType === "Student") {
      // Show student form and button, hide employee form and button
      studentFormWrapper.style.display = "block";
      employeeFormWrapper.style.display = "none";

      studIdContainer.style.display = "block";
      empIdContainer.style.display = "none";
      employeeDeptContainer.style.display = "none";
      employeeCatContainer.style.display = "none";
    } else if (selectedType === "Employee") {
      // Show employee form and button, hide student form and button
      studentFormWrapper.style.display = "none";
      employeeFormWrapper.style.display = "block";

      studIdContainer.style.display = "none";
      empIdContainer.style.display = "block";
      employeeDeptContainer.style.display = "block";
      employeeCatContainer.style.display = "flex";
    }
  }

  // Set initial state
  toggleForms();

  // Add event listener for select change
  infoTypeSelect.addEventListener("change", toggleForms);

  populateDepartments();

  const newStudentForm = document.querySelector("#new-std-info");
  const submitNewStudentInfoButton = document.querySelector(
    "#submit-new-std-info"
  );

  submitNewStudentInfoButton.addEventListener("click", function (event) {
    event.preventDefault();
    const studentData = {
      studentID: document.getElementById("studentID").value,
      "first-name": document.getElementById("first-name").value,
      "middle-name": document.getElementById("middle-name").value,
      "last-name": document.getElementById("last-name").value,
      "suffix-name": document.getElementById("suffix-name").value,
      gender: document.getElementById("gender-select").value,
      section: document.getElementById("section").value,
      department: document.getElementById("student-department").value,
      contact_number: document.getElementById("contact-number").value,
      email: document.getElementById("email").value,
      address: document.getElementById("address").value,
    };
    console.log("Student Data being sent:", studentData);
    fetch("../php/add_student.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(studentData),
    })
      .then((response) => response.json())
      .then((data) => {
        console.log("Response from add_student.php:", data);
        if (data.success) {
          alert(data.message);
          newStudentForm.reset();
        } else {
          alert(data.message);
        }
      })
      .catch((error) => {
        console.error("Fetch error:", error);
        alert(
          "Student information insertion failed. Check for duplicate IDs and missing data."
        );
      });
  });

  function populateDepartments() {
    fetch("../php/get_departments.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.error) {
          console.log("Fetched data:", data);
          populateDepartments(data);
          console.log(response.json());
        }

        studentDepartmentSelect = document.querySelector(
          "#new-std-info #student-department"
        );
        employeeDepartmentSelect = document.getElementById(
          "employee-department"
        );

        data.departments.forEach((department) => {
          const option = document.createElement("option");
          option.value = department.department_id; // Use department_id as the value
          option.textContent = department.department_name;
          if (studentDepartmentSelect) {
            studentDepartmentSelect.appendChild(option.cloneNode(true));
          } else {
            console.error("Student department select element not found!");
          }
          if (employeeDepartmentSelect) {
            employeeDepartmentSelect.appendChild(option);
          } else {
            console.error("Employee department select element not found!");
          }
        });
      })
      .catch((error) => {
        console.error("Fetch error:", error);
      });
  }

  const newEmployeeForm = document.querySelector("#new-emp-info");
  const submitNewEmployeeInfoButton = document.querySelector(
    "#submit-new-emp-info"
  );

  submitNewEmployeeInfoButton.addEventListener("click", function (event) {
    event.preventDefault();

    const employeeData = {
      employee_id: document.getElementById("emp-id").value,
      "emp-category": document.getElementById("emp-category").value,
      emp_fname: document.getElementById("emp-first-name").value,
      emp_lname: document.getElementById("emp-last-name").value,
      emp_minit: document.getElementById("middle-initial").value,
      emp_suffix: document.getElementById("suffix").value,
      emp_email: document.getElementById("emp-email").value,
      emp_contact_number: document.getElementById("emp-contact").value,
      emp_address: document.getElementById("emp-address").value,
      department_id: document.getElementById("employee-department").value,
    };

    console.log("Employee Data being sent:", employeeData);
    console.log(
      "Department ID value:",
      document.getElementById("employee-department").value
    );
    console.log(
      "Department select element:",
      document.getElementById("employee-department")
    );

    fetch("../php/add_employee.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(employeeData),
    })
      .then((response) => response.json())
      .then((data) => {
        console.log("Response from add_employee.php:", data);
        if (data.success) {
          alert(data.message);
          newEmployeeForm.reset();
        } else {
          let errorMessage = data.message + "\n\n";
          if (data.errors && Array.isArray(data.errors)) {
            errorMessage += data.errors.join("\n");
          }
          alert(errorMessage);
          console.error("Validation errors:", data);
        }
      })
      .catch((error) => {
        console.error("Fetch error:", error);
        alert(
          "Employee information insertion failed. Please check the console for errors."
        );
      });
  });
});
