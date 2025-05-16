document.addEventListener("DOMContentLoaded", initializePage);

const departmentDropdownId = "departmentId";
const registerFormId = "registerForm";
const togglePasswordId = "togglePassword";
const passwordInputId = "password";
const confirmPasswordInputId = "confirm_password";
const departmentApiPath = "../php/get_departments.php";
const registrationApiPath = "../php/register.php";

async function fetchJson(url, options = {}) {
  try {
    const response = await fetch(url, options);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const data = await response.json(); // Parse the JSON here
    return data; // Return the parsed JSON data
  } catch (error) {
    console.error(`Error fetching data from ${url}:`, error);
    throw error; // Re-throw the error
  }
}

async function populateDepartmentDropdown() {
  const departmentDropdown = document.getElementById(departmentDropdownId);

  try {
    const result = await fetchJson(departmentApiPath); // Fetch the entire object

    // Check if the result has the 'departments' property and if it's an array
    if (result && Array.isArray(result.departments)) {
      const departments = result.departments; // Access the departments array

      // Clear existing options (except the default one)
      while (departmentDropdown.options.length > 1) {
        departmentDropdown.remove(1);
      }

      // Add fetched departments to the dropdown
      departments.forEach((department) => {
        const option = document.createElement("option");
        option.value = department.department_id;
        option.textContent = department.department_name;
        departmentDropdown.appendChild(option);
      });
    } else {
      console.error(
        "Error: Fetched data does not contain a valid 'departments' array:",
        result
      );
      alert(
        "⚠️ Failed to load departments (invalid data structure). Please check the console."
      );
    }
  } catch (error) {
    console.error("Failed to populate department dropdown:", error);
    alert("⚠️ Failed to load departments. Please check the console.");
  }
}

async function handleRegistration(event) {
  event.preventDefault();

  const username = document.getElementById("username").value.trim();
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById(passwordInputId).value;
  const confirmPassword = document.getElementById(confirmPasswordInputId).value;
  const userType = document.getElementById("userType").value;
  const departmentId = document.getElementById(departmentDropdownId).value;

  if (password !== confirmPassword) {
    alert("Passwords do not match!");
    return;
  }

  const payload = {
    username,
    email,
    password,
    userType,
    departmentId,
  };

  try {
    const data = await fetchJson(registrationApiPath, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    if (data.success) {
      alert("✅ User registered successfully!");
      document.getElementById(registerFormId).reset();
      // Call the function to refresh the user table
      populateUserTableWithActions();
    } else {
      let errorMessage = `⚠️ ${data.message}`;
      if (Array.isArray(data.errors) && data.errors.length > 0) {
        errorMessage += "\n" + data.errors.join("\n");
      }
      alert(errorMessage);
    }
  } catch (error) {
    alert(
      "⚠️ Something went wrong during registration. Please check the console."
    );
  }
}

function togglePasswordVisibility() {
  const passwordInput = document.getElementById(passwordInputId);
  const confirmPasswordInput = document.getElementById(confirmPasswordInputId);
  const type = this.checked ? "text" : "password";
  passwordInput.type = type;
  confirmPasswordInput.type = type;
}

function initializePage() {
  populateDepartmentDropdown();
  document
    .getElementById(registerFormId)
    .addEventListener("submit", handleRegistration);
  document
    .getElementById(togglePasswordId)
    .addEventListener("change", togglePasswordVisibility);
}
