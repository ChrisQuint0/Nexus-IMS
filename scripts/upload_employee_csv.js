const downloadEmployeeTemplateButton = document.getElementById(
  "downloadEmployeeTemplateButton"
);

if (downloadEmployeeTemplateButton) {
  downloadEmployeeTemplateButton.addEventListener("click", (event) => {
    event.preventDefault(); // Prevent the default link behavior

    // Trigger download for item_csv_column_descriptions.pdf
    const pdfLink = document.createElement("a");
    pdfLink.href = "../resources/employee_csv_column_descriptions.pdf";
    pdfLink.download = "employee_csv_column_descriptions.pdf";
    document.body.appendChild(pdfLink);
    pdfLink.click();
    document.body.removeChild(pdfLink);

    // Trigger download for item_csv_template.csv after a short delay
    setTimeout(() => {
      const csvLink = document.createElement("a");
      csvLink.href = "../resources/add_employee_template.csv";
      csvLink.download = "add_employee_template.csv";
      document.body.appendChild(csvLink);
      csvLink.click();
      document.body.removeChild(csvLink);
    }, 250); // Adjust the delay (in milliseconds) if needed
  });
}

const uploadEmployeeCSVBtn = document.getElementById("uploadEmployeeCSVButton");
const employeeCSVFile = document.getElementById("employeeCSVFile");

uploadEmployeeCSVBtn.addEventListener("click", () => {
  employeeCSVFile.click();
});

employeeCSVFile.addEventListener("change", (event) => {
  const file = event.target.files[0];
  if (!file) return;

  Papa.parse(file, {
    header: true,
    skipEmptyLines: true,
    complete: function (results) {
      let data = results.data;

      if (
        data.length > 0 &&
        Object.values(data[data.length - 1]).every((v) => v === "")
      ) {
        data.pop();
      }

      const getDepartmentId = (departmentName) => {
        return fetch(
          `../php/get_department_id.php?department_name=${encodeURIComponent(
            departmentName
          )}`
        )
          .then((response) => response.json())
          .then((deptData) => {
            if (deptData.success) {
              return deptData.department_id;
            } else {
              console.error(
                `Department "${departmentName}" not found:`,
                deptData.message
              );
              return null;
            }
          })
          .catch((error) => {
            console.error("Error fetching department ID:", error);
            return null;
          });
      };

      const processedEmployeeData = Promise.all(
        data.map((row) => {
          const employeeData = {
            employee_id: row["Employee ID"] ?? null,
            emp_category: row["Category"] ?? null, // Corrected key
            emp_fname: row["First Name"] ?? null,
            emp_lname: row["Last Name"] ?? null,
            emp_minit: row["Middle Initial"] ?? null,
            emp_suffix: row["Suffix"] ?? null,
            emp_email: row["Email"] ?? null,
            emp_department: row["Department"] ?? null, // You might not need this directly for insertion
            emp_contact_number: row["Contact Number"] ?? null, // Added key
            emp_address: row["Address"] ?? null, // Added key
            department_id: null,
          };

          return getDepartmentId(row["Department"]).then((deptId) => {
            employeeData.department_id = deptId;
            return employeeData;
          });
        })
      );

      processedEmployeeData.then((finalData) => {
        fetch("../php/add_employees_from_csv.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(finalData),
        })
          .then((response) => response.json())
          .then((response) => {
            // Improved error handling in the response processing
            let message = "Upload successful!";
            if (!response.success) {
              message = "Upload failed: ";
              if (
                response.individual_messages &&
                response.individual_messages.length > 0
              ) {
                message = response.individual_messages
                  .map((item) => item.message)
                  .join("\n");
              } else {
                message = response.message; // Show general error
              }
            }
            alert(message);

            if (response.success) {
              employeeCSVFile.value = "";
            }
          })
          .catch((error) => {
            console.error("Fetch error:", error);
            alert(
              "Employee information upload failed. Check for duplicate IDs and missing data."
            );
            employeeCSVFile.value = "";
          });
      });
    },
    error: function (error) {
      console.error("Error parsing CSV:", error.message);
      alert("Error parsing CSV file: " + error.message);
      employeeCSVFile.value = "";
    },
  });
});
